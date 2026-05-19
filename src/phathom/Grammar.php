<?php
namespace pharos\phathom
{
    final class Grammar
    {
        private ?string $file      = null; /* location of grammar */
        private array   $rules     = [];   /* raw rules from grammar file parse       */
        private array   $compiled  = [];   /* desugared rules used by the Earley loop */
        private array   $terminals = [];   /* terminal name => true                   */
        private array   $patterns  = [];   /* pattern terminal name => true           */
        private array   $synthetic = [];   /* name => 'star'|'plus'|'opt'             */
        private string  $start     = '';
        private string  $type      = Node::class;
        private ?Lexer  $lexer     = null;

        public function __construct(string $file) {
            $this->file = $file;

            $lexer =
                new Grammar\Lexer($this->file);
            $parser = new Grammar\Parser($this);
            $parser->parse(
                $lexer->tokenize());

            if ($this->lexer === null) {
                throw new \Exception(
                    "$this->file does not declare a lexer");
            }

            if (empty($this->rules)) {
                throw new \Exception(
                    "$this->file does not contain any rules");
            }

            $this->compile();
        }

        public function complexRule(string $rule, array $symbols, int|false $priority, ?string $action = null): void {
            $this->rules[$rule][] = [
                'symbols' => \array_map(function($symbol) {
                    return [
                        'name'       => $symbol['value'],
                        'type'       => $symbol['type'],
                        'quantifier' => $symbol['quantifier'] ?? null,
                        'location'   => $symbol['location'],
                    ];
                }, $symbols),
                'priority' => $priority,
                'action' =>
                    ($action !== null) ?
                        \trim($action) : null,
            ];
        }

        /* Single IDENT or PATTERN (optionally quantified). */
        public function expressionRule(string $rule, array $token, ?string $quantifier = null): void {
            $this->rules[$rule][] = [
                'symbols' => [[
                    'name'       => \trim($token['value']),
                    'type'       => $token['type'],
                    'quantifier' => $quantifier,
                    'location'   => $token['location'],
                ]],
                'priority' => false,
                'action' => null,
            ];
        }

        private function compile(): void {
            $this->compiled = $this->rules;
            $this->desugarQuantifiers();

            $this->start = isset($this->rules['unit'])
                ? 'unit'
                : \array_key_last($this->rules);

            $this->identifyTerminals();
            $this->identifySymbols();

            if (\count($this->patterns)) {
                $this->lexer->add(
                    \array_keys(
                        $this->patterns));
            }
        }

        /* Rewrite quantified symbols into synthetic nullable/recursive rules,
         * replacing each quantified reference with the synthetic rule name.
         *
         *   A*  →  __A_star__ :  ε  |  __A_star__ A     (left-recursive)
         *   A+  →  __A_plus__ :  A  |  __A_plus__ A     (left-recursive)
         *   A?  →  __A_opt__  :  ε  |  A
         */
        private function desugarQuantifiers(): void {
            $synthetic = [];

            foreach ($this->compiled as &$alternatives) {
                foreach ($alternatives as &$alternative) {
                    foreach ($alternative['symbols'] as &$symbol) {
                        if ($symbol['quantifier'] === null) {
                            continue;
                        }

                        $base  = $symbol['name'];
                        $type  = $symbol['quantifier'];
                        $kind  = match ($type) {
                            '*'     => 'star',
                            '+'     => 'plus',
                            '?'     => 'opt',
                        };
                        $name =
                            \sprintf("__%s_%s__",
                                $base, $kind);

                        if (!isset($synthetic[$name])) {
                            $self = [[
                                'name'       => $name,
                                'type'       => 'IDENT',
                                'quantifier' => null
                            ]];

                            $one = [[
                                'name'       => $base,
                                'type'       => $symbol['type'],
                                'quantifier' => null
                            ]];

                            $synthetic[$name] = match ($kind) {
                                'star' => [
                                    [
                                        'symbols'  => [],
                                        'priority' => false,
                                        'action'   => null
                                    ], /* ε */
                                    [
                                        'symbols'  => \array_merge($self, $one),
                                        'priority' => $alternative['priority'],
                                        'action'   => null
                                    ], /* A* A */
                                ],
                                'plus' => [
                                    [
                                        'symbols'  => $one,
                                        'priority' => $alternative['priority'],
                                        'action'   => null
                                    ], /* A */
                                    [
                                        'symbols'  => \array_merge($self, $one),
                                        'priority' => $alternative['priority'],
                                        'action'   => null
                                    ], /* A+ A */
                                ],
                                'opt' => [
                                    [
                                        'symbols'  => [],
                                        'priority' => false,
                                        'action'   => null
                                    ], /* ε */
                                    [
                                        'symbols'  => $one,
                                        'priority' => $alternative['priority'],
                                        'action'   => null
                                    ], /* A */
                                ],
                            };
                            $this->synthetic[$name] = $kind;
                        }

                        $symbol['name']       = $name;
                        $symbol['quantifier'] = null;
                    }
                }
            }

            $this->compiled = \array_merge($this->compiled, $synthetic);
        }

        /* A terminal is any symbol that is not itself a defined rule. */
        private function identifyTerminals(): void {
            foreach ($this->compiled as $alternatives) {
                foreach ($alternatives as $alternative) {
                    foreach ($alternative['symbols'] as $symbol) {
                        if (!\array_key_exists($symbol['name'], $this->compiled)) {
                            switch ($symbol['type']) {
                                case 'PATTERN':
                                    $this->patterns[$symbol['name']] = true;
                                break;

                                default:
                                    $this->terminals[$symbol['name']] = true;
                            }
                        }
                    }
                }
            }
        }

        private function identifySymbols() : void {
            foreach ($this->compiled as $rule => $alternatives) {
                foreach ($alternatives as $alternative) {
                    foreach ($alternative['symbols'] as $symbol) {
                        if ($symbol['type'] !== 'IDENT') {
                            continue;
                        }

                        if (isset($this->compiled[$symbol['name']])) {
                            continue;
                        }

                        if (!$this->lexer->known($symbol['name'])) {
                            throw new \Exception(
                                "Unknown symbol '{$symbol['name']}' "
                                    ."at '{$rule}' in {$this->file}");
                        }
                    }
                }
            }
        }

        public function execute(Parser $parser, Node $node): Node {
            $file = $parser->getFile();
            $tokens =
                $this->lexer
                    ->tokenize($file);
            $limit = \count($tokens);

            [$chart, $items] = $this->buildChart($tokens, $limit);

            $root     = null;
            $rootPriority = false;

            foreach ($chart[$limit] as $id) {
                $item = $items[$id];
                $alt  = $this->compiled[$item['rule']][$item['alt']];

                if ($item['rule']   === $this->start &&
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
                throw new \Exception(
                    "{$file->getPath()} does not match '{$this->start}' in {$this->file}");
            }

            $this->evalItem($root, $tokens, $items, $node);

            return $node;
        }

        private function buildChart(array $tokens, int $limit): array {
            $items = [];
            $index = [];
            $chart = \array_fill(0, $limit + 1, []);

            $add = function (int $pos, array $item) use (&$items, &$index, &$chart): void {
                $key = "{$pos}/{$item['rule']}/{$item['alt']}/{$item['dot']}/{$item['origin']}";

                if (!isset($index[$key])) {
                    $item['pos']     = $pos;
                    $id              = \count($items);
                    $items[]         = $item;
                    $index[$key]     = $id;
                    $chart[$pos][]   = $id;
                } elseif (!empty($item['backs'])) {
                    foreach ($item['backs'] as $back) {
                        $items[$index[$key]]['backs'][] = $back;
                    }
                    /* Propagate priority upward — if the incoming completion
                     * carries higher priority, update the existing item so
                     * the priority is visible at root selection time. */
                    $incoming = $item['priority'];
                    $existing = $items[$index[$key]]['priority'];

                    if ($incoming === false) {
                        return;
                    }

                    if ($existing === false || $incoming > $existing) {
                        $items[$index[$key]]['priority'] = $incoming;
                    }
                }
            };

            foreach ($this->compiled[$this->start] as $altIdx => $_) {
                $add(0, [
                    'rule'     => $this->start,
                    'alt'      => $altIdx,
                    'dot'      => 0,
                    'origin'   => 0,
                    'priority' => false,
                    'backs'    => []]);
            }

            for ($i = 0; $i <= $limit; $i++) {
                $j = 0;
                while ($j < \count($chart[$i])) {
                    $itemId = $chart[$i][$j++];
                    $item   = $items[$itemId];
                    $alt    = $this->compiled[$item['rule']][$item['alt']];
                    $dotted = $alt['symbols'][$item['dot']] ?? null;

                    if ($dotted === null) {
                        /* Complete — propagate the completing alternative's
                         * priority into the parent item so it bubbles up.
                         * Also reflect the alt's own declared priority back
                         * into the completing item so selectBack can use it
                         * when choosing between competing backs. */
                        $itemPriority = $alt['priority'];

                        if ($itemPriority !== false) {
                            $existing = $items[$itemId]['priority'];
                            if ($existing === false || $itemPriority > $existing) {
                                $items[$itemId]['priority'] = $itemPriority;
                            }
                        }

                        foreach ($chart[$item['origin']] as $prevId) {
                            $prev    = $items[$prevId];
                            $prevAlt = $this->compiled[$prev['rule']][$prev['alt']];
                            $prevSym = $prevAlt['symbols'][$prev['dot']] ?? null;

                            if ($prevSym !== null && $prevSym['name'] === $item['rule']) {
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

                                $add($i, [
                                    'rule'     => $prev['rule'],
                                    'alt'      => $prev['alt'],
                                    'dot'      => $prev['dot'] + 1,
                                    'origin'   => $prev['origin'],
                                    'priority' => $advancePriority,
                                    'backs'    => [[
                                        'prev'  => $prevId,
                                        'child' => $itemId
                                    ]],
                                ]);
                            }
                        }
                    } elseif (isset($this->terminals[$dotted['name']]) ||
                              isset($this->patterns[$dotted['name']])) {
                        /* Scan */
                        if ($i < $limit && $tokens[$i]['type'] === $dotted['name']) {
                            $add($i + 1, [
                                'rule'     => $item['rule'],
                                'alt'      => $item['alt'],
                                'dot'      => $item['dot'] + 1,
                                'origin'   => $item['origin'],
                                'priority' => $item['priority'],
                                'backs'    => [[
                                    'prev'  => $itemId,
                                    'token' => $i
                                ]],
                            ]);
                        }
                    } else {
                        /* Predict */
                        foreach ($this->compiled[$dotted['name']] as $altIdx => $_) {
                            $add($i, [
                                'rule'     => $dotted['name'],
                                'alt'      => $altIdx,
                                'dot'      => 0,
                                'origin'   => $i,
                                'priority' => false,
                                'backs'    => [],
                            ]);
                        }
                    }
                }
            }

            return [$chart, $items];
        }

        private function evalItem(int $itemId, array $tokens, array $items, Node $node): mixed {
            $item = $items[$itemId];
            $alt  = $this->compiled[$item['rule']][$item['alt']];

            $synthesized = $this->synthetic[$item['rule']] ?? false;

            if (empty($alt['symbols'])) {
                return $synthesized ? [] : null;
            }

            $values = $this->collectValues($itemId, $tokens, $items, $node);

            if ($alt['action'] !== null) {
                return $this->callAction($alt['action'], $values, $node);
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
            $alt      = $this->compiled[$item['rule']][$item['alt']];
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

        private function bindValues(array $values) : array {
            $bound = [];

            foreach ($values as $index => $value) {
                $bound[
                    \sprintf(
                        "__sym%d__", $index + 1)
                ] = $value;
            }

            return $bound;
        }

        private function callAction(string $action, array $values, Node $node): mixed {
            $closure = \Closure::bind(
                function (array $__vars__) use ($action): mixed {
                    \extract($__vars__);
                    return eval(
                        \preg_replace_callback(
                            '/\$(\d+)/',
                            function ($match) {
                                return \sprintf(
                                    "\$__sym%s__",
                                    $match[1]
                                );
                            }, $action));
                },
                $node,
                \get_class($node)
            );

            return $closure(
                $this->bindValues($values));
        }

        public function setLexer(string $location): void {
            $this->lexer = new Lexer($this->file, $location);
        }

        public function setType(string $type): void {
            $this->type = $type;
        }

        public function getFile() : string {
            return $this->file;
        }

        public function factory(Parser $parser): Node {
            return new $this->type($parser);
        }
    }
}