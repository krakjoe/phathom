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
                    /* Propagate priority upward — if the incoming completion
                     * carries higher priority, update the existing item so
                     * the priority is visible at root selection time. */
                    $incoming = $item->priority;
                    $existing = $items[$slot]->priority;

                    if ($incoming === false) {
                        return;
                    }

                    if ($existing === false || $incoming > $existing) {
                        $items[$slot]->priority = $incoming;
                    }
                }
            };

            foreach ($this->rules[$this->start] as $altIdx => $_) {
                $add(0, new Item(
                    rule:     $this->start,
                    alt:      $altIdx,
                    dot:      0,
                    origin:   0,
                    priority: false,
                    backs:    []));
            }

            for ($i = 0; $i <= $this->limit; $i++) {
                $j = 0;
                while ($j < \count($chart[$i])) {
                    $itemId = $chart[$i][$j++];
                    $item   = $items[$itemId];
                    $alt    = $this->rules[$item->rule][$item->alt];
                    $dotted = $alt['symbols'][$item->dot] ?? null;

                    if ($dotted === null) {
                        /* Complete — propagate the completing alternative's
                         * priority into the parent item so it bubbles up.
                         * Also reflect the alt's own declared priority back
                         * into the completing item so selectBack can use it
                         * when choosing between competing backs. */
                        $itemPriority = $alt['priority'];

                        if ($itemPriority !== false) {
                            $existing = $items[$itemId]->priority;
                            if ($existing === false || $itemPriority > $existing) {
                                $items[$itemId]->priority = $itemPriority;
                            }
                        }

                        foreach ($chart[$item->origin] as $prevId) {
                            $prev    = $items[$prevId];
                            $prevAlt = $this->rules[$prev->rule][$prev->alt];
                            $prevSym = $prevAlt['symbols'][$prev->dot] ?? null;

                            if ($prevSym !== null && $prevSym->name === $item->rule) {
                                /* When the parent alternative has an explicit
                                 * declared priority, it is a floor: if the
                                 * completing child brings no priority (e.g. a
                                 * synthetic quantifier rule whose alts carry
                                 * false) or a lower one, use the parent's
                                 * declaration so root-selection still sees the
                                 * correct priority. */
                                $parentPriority = $prevAlt['priority'];
                                $advancePriority =
                                    ($parentPriority !== false &&
                                        ($itemPriority === false || $parentPriority > $itemPriority))
                                    ? $parentPriority
                                    : $itemPriority;

                                $add($i, new Item(
                                    rule:     $prev->rule,
                                    alt:      $prev->alt,
                                    dot:      $prev->dot + 1,
                                    origin:   $prev->origin,
                                    priority: $advancePriority,
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
                                priority: $item->priority,
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
                                priority: false,
                                backs:    []));
                        }
                    }
                }
            }

            return [$chart, $items];
        }
    }
}