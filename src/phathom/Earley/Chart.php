<?php
namespace pharos\phathom\Earley {
    final class Chart {
        public function __construct(
            private array  $rules,
            private array  $terminals,
            private array  $patterns,
            private string $start,
            private array  $tokens,
            private int    $limit) {}

        public function build(): array {
            $items = [];
            $index = [];
            $chart = \array_fill(0, $this->limit + 1, []);

            $add = function (int $pos, Item $item) use (&$items, &$index, &$chart): void {
                $slot = &$index[$pos][$item->rule][$item->alt][$item->dot][$item->origin];

                if (!isset($slot)) {
                    $item->pos     = $pos;
                    $id            = \count($items);
                    $items[]       = $item;
                    $slot          = $id;
                    $chart[$pos][] = $id;
                } elseif (!empty($item->backs)) {
                    foreach ($item->backs as $back) {
                        $items[$slot]->backs[] = $back;
                    }
                }
            };

            foreach ($this->rules[$this->start] as $altIdx => $_) {
                $add(0, new Item(
                    rule:     $this->start,
                    alt:      $altIdx,
                    dot:      0,
                    origin:   0,
                    backs:    []));
            }

            for ($i = 0; $i <= $this->limit; $i++) {
                $j = 0;
                while ($j < \count($chart[$i])) {
                    $itemId = $chart[$i][$j++];
                    $item   = $items[$itemId];
                    $alt    = $this->rules[$item->rule][$item->alt];
                    $dotted = $alt->symbols[$item->dot] ?? null;

                    if ($dotted === null) {
                        /* Complete */
                        foreach ($chart[$item->origin] as $prevId) {
                            $prev    = $items[$prevId];
                            $prevAlt = $this->rules[$prev->rule][$prev->alt];
                            $prevSym = $prevAlt->symbols[$prev->dot] ?? null;

                            if ($prevSym !== null && $prevSym->name === $item->rule) {
                                $add($i, new Item(
                                    rule:     $prev->rule,
                                    alt:      $prev->alt,
                                    dot:      $prev->dot + 1,
                                    origin:   $prev->origin,
                                    backs:    [new Back(
                                        prev:  $prevId,
                                        child: $itemId,
                                        token: null)]));
                            }
                        }
                    } elseif (isset($this->terminals[$dotted->name]) ||
                              isset($this->patterns[$dotted->name])) {
                        /* Scan */
                        $scanning =
                            $this->terminals[$dotted->name] ??
                            $this->patterns[$dotted->name];
                        if ($i < $this->limit && $this->tokens[$i]->type === $scanning) {
                            $add($i + 1, new Item(
                                rule:     $item->rule,
                                alt:      $item->alt,
                                dot:      $item->dot + 1,
                                origin:   $item->origin,
                                backs:    [new Back(
                                    prev:  $itemId,
                                    child: null,
                                    token: $i)]));
                        }
                    } else {
                        /* Predict */
                        foreach ($this->rules[$dotted->name] as $altIdx => $_) {
                            $add($i, new Item(
                                rule:     $dotted->name,
                                alt:      $altIdx,
                                dot:      0,
                                origin:   $i,
                                backs:    []));
                        }
                    }
                }
            }

            return [$chart, $items];
        }
    }
}
?>