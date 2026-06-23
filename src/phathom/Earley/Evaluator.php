<?php declare(strict_types=1);

namespace pharos\phathom\Earley {
    use \pharos\phathom\Context;
    use \pharos\phathom\Exception;
    use \pharos\phathom\Grammar\Frame;
    use \pharos\phathom\Grammar\Quantifier;

    final class Evaluator {
        private string      $start;
        private array       $tokens;
        private int         $limit;
        private array       $path;
        private array       $actions = [[]];
        private Context     $context;

        public function __construct(Chart $chart, Context $context) {
            $this->start   = $chart->start;
            $this->tokens  = $chart->tokens;
            $this->limit   = $chart->limit;
            $this->path    = $chart->path;
            $this->context = $context;
        }

        private function apply(Item $item, array $values): mixed {
            $alt = $item->alternative;

            if ($alt->action !== null) {
                $action =
                    $this->actions
                        [$item->rule]
                        [$item->alt]
                            ?? null;
                if ($action === null) {
                    $method =
                        \sprintf(
                            '__action_%s_%d__',
                            $item->rule,
                            $item->alt);
                    $action =
                        $this->actions
                            [$item->rule]
                            [$item->alt] =
                                $this->context
                                    ->$method(...);
                }
                return $action(...$values);
            }

            return match ($alt->synthetic) {
                Quantifier::STAR,
                Quantifier::PLUS     =>
                    \count($alt->symbols) === 2
                        ? [...$values[0], $values[1]]
                        : [$values[0]],
                Quantifier::OPTIONAL => $values[0],
                default              =>
                    \count($values) === 1
                        ? $values[0]
                        : $values,
            };
        }

        private function select(array $backs, array $tokens) : Back {
            $selected    = $backs[0]->child;
            $prioritized =
                $this->priority($backs[0]);

            if ($prioritized === false) {
                if (\count($backs) > 1) {
                    throw Exception\Ambiguity::range(
                        $this->context,
                        $selected->rule,
                        $tokens,
                        $selected->origin,
                        $selected->pos - 1);
                }
                return $backs[0];
            }

            foreach ($backs as $back) {
                $priority = $this->priority($back);

                if ($priority > $prioritized) {
                    $selected    = $back->child;
                    $prioritized = $priority;
                } elseif ($priority === $prioritized) {
                    $selected = $this->resolve($selected, $back->child);
                }
            }

            foreach ($backs as $back) {
                if ($back->child === $selected) {
                    return $back;
                }
            }

            /* unreachable by construction, any code here is dead */
        }

        private function resolve(Item $a, Item $b) : Item {
            if ($a->alternative->associativity ===
                    \pharos\phathom\Grammar\Associativity::LEFT) {
                return $a->origin >= $b->origin ? $a : $b;
            }

            return $a->origin <= $b->origin ? $a : $b;
        }

        private function priority(Back $back) : int|false {
            if ($back->child === null) {
                return false;
            }

            return $back->child->alternative->priority;
        }

        private function setup() : array {
            $select = $this->select(...);

            return [
                function(mixed $item, array $tokens) use ($select) : array {
                    $limit   = \count($item->alternative->symbols);
                    $partial = \array_fill(0, $limit, null);
                    $slots   = [];
                    $nodes   = [];

                    $cursor = $item;
                    for ($pos = $limit - 1; $pos >= 0; $pos--) {
                        $back = $select($cursor->backs, $tokens);
                        if ($back->token !== null) {
                            $partial[$pos] = $tokens[$back->token];
                        } else {
                            $slots[] = $pos;
                            $nodes[] = $back->child;
                        }
                        $cursor = $back->prev;
                    }

                    return [$partial, $slots, $nodes];
                },
                $this->apply(...),
            ];
        }

        private function execute(Item $item) : mixed {
            [
                $children,
                $apply
            ] = $this->setup();

            $stack = [Frame::select($item)];
            $heap  = [];

            while (($frame = \array_pop($stack))) {
                $frame(
                    $children,
                    $apply,
                    $stack,
                    $heap,
                    $this->tokens);
            }

            return \array_pop($heap);
        }

        public function __invoke() : mixed {
            $item        = null;
            $alt         = null;
            $prioritized = false;

            foreach ($this->path[$this->limit] as $nitem) {
                $nalt  = $nitem->alternative;

                if ($nitem->rule   !== $this->start  ||
                    $nitem->origin !== 0              ||
                    $nitem->dot    !== \count($nalt->symbols)) {
                    continue;
                }

                if ($item === null) {
                    $item        = $nitem;
                    $alt         = $nalt;
                    $prioritized = $nalt->priority;
                } elseif ($prioritized === false) {
                    throw Exception\Ambiguity::range(
                        $this->context,
                        $this->start,
                        $this->tokens,
                        0,
                        $this->limit - 1);
                } elseif ($nalt->priority > $prioritized) {
                    $item        = $nitem;
                    $alt         = $nalt;
                    $prioritized = $nalt->priority;
                } elseif ($nalt->priority === $prioritized) {
                    $item        = $this->resolve($item, $nitem);
                    $alt         = $item->alternative;
                }
            }

            if ($item === null) {
                throw Exception\Execute::nomatch(
                    $this->context,
                    $this->start,
                    $this->tokens);
            }

            return $this->execute($item);
        }
    }
}
?>