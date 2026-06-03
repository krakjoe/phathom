<?php
namespace pharos\phathom\Earley {
    use \pharos\phathom\Context;
    use \pharos\phathom\Grammar\Alternative;
    use \pharos\phathom\Grammar\Quantifier;

    use \pharos\phathom\Exception\Ambiguity as AmbiguityException;
    use \pharos\phathom\Exception\Execute   as ExecuteException;

    final class Evaluator {
        public function __construct(
            private Context $context,
            private array   $chart) {}

        private function evalItem(Item $item, Alternative $alt, array $tokens): mixed {
            if (empty($alt->symbols)) {
                return $alt->synthetic !== Quantifier::NONE ? [] : null;
            }

            $values = $this->collectValues($item, $alt, $tokens);

            if ($alt->action !== null) {
                $method =
                    \sprintf(
                        '__action_%s_%d__',
                        $item->rule,
                        $item->alt);
                return $this->context->$method(...$values);
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

        private function selectPriority(Back $back) : int|false {
            if ($back->child === null) {
                return false;
            }

            return $back->child->alternative->priority;
        }

        private function selectBack(array $backs, array $tokens) : Back {
            $selected    = $backs[0];
            $prioritized =
                $this->selectPriority($selected);

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
                    $this->selectPriority($back);

                if ($priority > $prioritized) {
                    $selected     = $back;
                    $prioritized  = $priority;
                }
            }

            return $selected;
        }

        private function collectValues(Item $item, Alternative $alt, array $tokens): array {
            /* Walk the backs chain right-to-left to collect (pos → back) pairs,
             * then evaluate left-to-right so side-effects fire in document
             * order rather than in reverse. */
            $limit = \count($alt->symbols);
            $backs = [];

            $cur   = $item;
            for ($pos = $limit - 1; $pos >= 0; $pos--) {
                $back =
                    $this->selectBack(
                        $cur->backs, $tokens);
                $backs[$pos] = $back;
                $cur         = $back->prev;
            }

            $values = \array_fill(0, $limit, null);
            for ($pos = 0; $pos < $limit; $pos++) {
                $back = $backs[$pos];
                if ($back->token !== null) {
                    $values[$pos] =
                        $tokens[$back->token];
                } else {
                    $nitem = $back->child;
                    $values[$pos] =
                        $this->evalItem(
                            $nitem,
                            $nitem->alternative,
                            $tokens);
                }
            }

            return $values;
        }

        public function enter(string $start, array $tokens, int $limit) : mixed {
            $item        = null;
            $alt         = null;
            $prioritized = false;

            foreach ($this->chart[$limit] as $nitem) {
                $nalt  = $nitem->alternative;

                if ($nitem->rule   !== $start               ||
                    $nitem->origin !== 0                    ||
                    $nitem->dot    !== \count($nalt->symbols)) {
                    continue;
                }

                if ($item === null) {
                    $item        = $nitem;
                    $alt         = $nalt;
                    $prioritized =
                        $nalt->priority;
                } elseif ($prioritized === false) {
                    throw AmbiguityException::range(
                        $this->context,
                        $start,
                        $tokens,
                        0,
                        $limit - 1);
                } elseif ($nalt->priority > $prioritized) {
                    $item        = $nitem;
                    $alt         = $nalt;
                    $prioritized = $nalt->priority;
                }
            }

            if ($item === null) {
                throw ExecuteException::nomatch(
                    $this->context,
                    $start,
                    $tokens);
            }

            return $this->evalItem($item, $alt, $tokens);
        }
    }
}
?>