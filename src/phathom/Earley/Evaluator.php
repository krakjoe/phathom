<?php
namespace pharos\phathom\Earley {
    use \pharos\phathom\Context;
    use \pharos\phathom\Grammar\Quantifier;

    use \pharos\phathom\Exception\Ambiguity as AmbiguityException;
    use \pharos\phathom\Exception\Execute   as ExecuteException;

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
            $selected    = $backs[0];
            $prioritized =
                $this->priority($selected);

            if ($prioritized === false) {
                if (\count($backs) > 1) {
                    $child = $selected->child;
                    throw AmbiguityException::range(
                        $this->context,
                        $child->rule,
                        $tokens,
                        $child->origin,
                        $child->pos - 1);
                }
                return $backs[0];
            }

            foreach ($backs as $back) {
                $priority =
                    $this->priority($back);

                if ($priority > $prioritized) {
                    $selected     = $back;
                    $prioritized  = $priority;
                }
            }

            return $selected;
        }

        private function priority(Back $back) : int|false {
            if ($back->child === null) {
                return false;
            }

            return $back->child->alternative->priority;
        }

        private function execute(Item $item) : mixed {
            $select = $this->select(...);
            $apply  = $this->apply(...);
            $stack  = [Frame::select($item)];
            $heap   = [];

            while (($frame = \array_pop($stack))) {
                $frame(
                    $select,
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
                    throw AmbiguityException::range(
                        $this->context,
                        $this->start,
                        $this->tokens,
                        0,
                        $this->limit - 1);
                } elseif ($nalt->priority > $prioritized) {
                    $item        = $nitem;
                    $alt         = $nalt;
                    $prioritized = $nalt->priority;
                }
            }

            if ($item === null) {
                throw ExecuteException::nomatch(
                    $this->context,
                    $this->start,
                    $this->tokens);
            }

            return $this->execute($item);
        }
    }
}
?>