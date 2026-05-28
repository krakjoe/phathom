<?php
namespace pharos\phathom\Earley {
    use \pharos\phathom\Context;

    final class Evaluator {
        public function __construct(
            private array $rules,
            private array $synthetic,
            private array $chart,
            private array $items) {}

        private function evalItem(Context $context, int $itemId, array $tokens): mixed {
            $item = $this->items[$itemId];
            $alt  = $this->rules[$item->rule][$item->alt];

            $synthesized =
                $this->synthetic[
                    $item->rule] ?? false;

            if (empty($alt['symbols'])) {
                return $synthesized ? [] : null;
            }

            $values = $this->collectValues($context, $itemId, $tokens);

            if ($alt['action'] !== null) {
                $method =
                    \sprintf(
                        '__action_%s_%d__',
                        $item->rule,
                        $item->alt);
                return $context->$method(...$values);
            }

            switch ($synthesized) {
                case 'star':
                case 'plus':
                    if (\count($alt['symbols']) === 2) {
                        $list   = $values[0];
                        $list[] = $values[1];
                        return $list;
                    }
                    return [$values[0]];

                case 'opt':
                    return $values[0];
            }

            return \count($values) === 1 ? $values[0] : $values;
        }

        private function selectPriority(Back $back) : int {
            if ($back->child !== null) {
                $priority = $this->items[
                    $back->child
                ]->priority;

                if ($priority !== false) {
                    return $priority;
                }

                return \PHP_INT_MIN;
            }

            $priority = $this->items[
                $back->prev
            ]->priority;

            if ($priority !== false) {
                return $priority;
            }

            return \PHP_INT_MIN;
        }

        private function selectBack(array $backs) : Back {
            $selected = $backs[0];

            $best = $this->selectPriority($selected);

            foreach ($backs as $back) {
                $priority =
                    $this->selectPriority($back);

                if ($priority > $best) {
                    $selected = $back;
                    $best     = $priority;
                }
            }

            return $selected;
        }

        private function collectValues(Context $context, int $itemId, array $tokens): array {
            $item     = $this->items[$itemId];
            $alt      = $this->rules[$item->rule][$item->alt];
            $nSymbols = \count($alt['symbols']);

            /* Walk the backs chain right-to-left to collect (pos → back) pairs,
             * then evaluate left-to-right so side-effects fire in document
             * order rather than in reverse. */
            $backs = [];

            $cur   = $itemId;
            for ($pos = $nSymbols - 1; $pos >= 0; $pos--) {
                $back =
                    $this->selectBack(
                        $this->items[$cur]->backs);
                $backs[$pos] = $back;
                $cur         = $back->prev;
            }

            $values = \array_fill(0, $nSymbols, null);
            for ($pos = 0; $pos < $nSymbols; $pos++) {
                $back         = $backs[$pos];
                $values[$pos] = $back->token !== null
                    ? $tokens[$back->token]
                    : $this->evalItem(
                            $context,
                            $back->child,
                            $tokens);
            }

            return $values;
        }

        public function enter(Context $context, string $start, array $tokens, int $limit) : bool {
            $root     = null;
            $rootPriority = false;

            foreach ($this->chart[$limit] as $id) {
                $item = $this->items[$id];
                $alt  = $this->rules[$item->rule][$item->alt];

                if ($item->rule   === $start &&
                    $item->origin === 0 &&
                    $item->dot    === \count($alt['symbols'])) {

                    $itemPriority = $item->priority;

                    if ($root === null) {
                        $root         = $id;
                        $rootPriority = $itemPriority;
                    } elseif ($itemPriority !== false &&
                              ($rootPriority === false ||
                               $itemPriority > $rootPriority)) {
                        $root         = $id;
                        $rootPriority = $itemPriority;
                    }
                }
            }

            if ($root === null) {
                return false;
            }

            $this->evalItem($context, $root, $tokens);

            return true;
        }
    }
}
?>