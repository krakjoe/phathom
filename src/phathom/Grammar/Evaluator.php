<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\Node;

    final class Evaluator {
        public function __construct(
            private array $rules,
            private array $synthetic,
            private array $chart,
            private array $items) {}

        private function evalItem(int $itemId, array $tokens, array $items, Node $node): mixed {
            $item = $items[$itemId];
            $alt  = $this->rules[$item['rule']][$item['alt']];

            $synthesized =
                $this->synthetic[
                    $item['rule']] ?? false;

            if (empty($alt['symbols'])) {
                return $synthesized ? [] : null;
            }

            $values = $this->collectValues(
                $itemId, $tokens, $items, $node);

            if ($alt['action'] !== null) {
                $method =
                    \sprintf(
                        '__action_%s_%d__',
                        $item['rule'],
                        $item['alt']);
                return $node->$method(...$values);
            }

            switch ($synthesized) {
                case 'star':
                case 'plus':
                    $list =
                        \is_array($values[0]) ?
                            $values[0] : [];
                    $list[] = end($values);
                    return $list;

                case 'opt':
                    return $values[0];
            }

            return \count($values) === 1 ? $values[0] : $values;
        }

        private function selectPriority(array $back, array $items) : int {
            if (isset($back['child'])) {
                $priority = $items[$back['child']]['priority'];

                if ($priority !== false) {
                    return $priority;
                }

                return \PHP_INT_MIN;
            }

            $priority = $items[$back['prev']]['priority'];

            if ($priority !== false) {
                return $priority;
            }

            return \PHP_INT_MIN;
        }

        private function selectBack(array $backs, array $items) : array {
            $selected = $backs[0];

            $best =
                $this->selectPriority(
                    $selected, $items);

            foreach ($backs as $back) {
                $priority =
                    $this->selectPriority(
                        $back, $items);

                if ($priority > $best) {
                    $selected = $back;
                    $best     = $priority;
                }
            }

            return $selected;
        }

        private function collectValues(int $itemId, array $tokens, array $items, Node $node): array {
            $item     = $items[$itemId];
            $alt      = $this->rules[$item['rule']][$item['alt']];
            $nSymbols = \count($alt['symbols']);

            /* Walk the backs chain right-to-left to collect (pos → back) pairs,
             * then evaluate left-to-right so side-effects fire in document
             * order rather than in reverse. */
            $backs = [];

            $cur   = $itemId;
            for ($pos = $nSymbols - 1; $pos >= 0; $pos--) {
                $back =
                    $this->selectBack(
                        $items[$cur]['backs'], $items);
                $backs[$pos] = $back;
                $cur         = $back['prev'];
            }

            $values = \array_fill(0, $nSymbols, null);
            for ($pos = 0; $pos < $nSymbols; $pos++) {
                $back         = $backs[$pos];
                $values[$pos] = isset($back['token'])
                    ? (
                        $tokens[$back['token']]['value'] ??
                        $tokens[$back['token']]['type']
                    ) : $this->evalItem(
                            $back['child'],
                            $tokens, $items, $node);
            }

            return $values;
        }

        public function enter(string $start, array $tokens, int $limit, Node $node) : Node|false {
            $root     = null;
            $rootPriority = false;

            foreach ($this->chart[$limit] as $id) {
                $item = $this->items[$id];
                $alt  = $this->rules[$item['rule']][$item['alt']];

                if ($item['rule']   === $start &&
                    $item['origin'] === 0 &&
                    $item['dot']    === \count($alt['symbols'])) {

                    $itemPriority = $item['priority'];

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

            $this->evalItem(
                $root,
                $tokens,
                $this->items,
                $node);

            return $node;
        }
    }
}
?>