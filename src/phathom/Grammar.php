<?php

namespace pharos\phathom
{
    final class Grammar
    {
        private ?string $file      = null; /* location of grammar */
        private array   $rules     = [];   /* raw rules from grammar file parse       */
        private array   $compiled  = [];   /* desugared rules used by the Earley loop */
        private array   $terminals = [];   /* terminal name => true                   */
        private array   $synthetic = [];   /* name => 'star'|'plus'|'opt'             */
        private string  $start     = '';
        private string  $type      = Node::class;
        private ?Lexer  $lexer     = null;

        public function __construct(string $file) {
            $this->file = $file;

            if (!\file_exists($this->file)) {
                throw new \Exception(
                    "$this->file does not exist");
            }

            $lines = \file($this->file);

            if (\count($lines) === 0) {
                throw new \Exception(
                    "$this->file does not contain valid grammar (empty)");
            }

            $this->parse(
                $this->tokenize(
                    $this->buffer(
                        $this->file,
                        $lines)));

            if ($this->lexer === null) {
                throw new \Exception("$this->file does not declare a lexer");
            }

            if (empty($this->rules)) {
                throw new \Exception("$this->file does not contain any rules");
            }

            $this->compile();
        }

        private function buffer(string $file, array $lines): string {
            $lines  = $this->include($file, $lines);
            $buffer = '';

            foreach (\array_map('trim', $lines) as $line) {
                if (empty($line) ||
                    \str_starts_with($line, '#')) {
                    continue;
                }

                if (\preg_match('/^lexer:\s+(.+)/', $line, $location)) {
                    $this->setLexer($file, \trim($location[1]));
                    continue;
                }

                if (\preg_match('/^type:\s+(.+)/', $line, $class)) {
                    $this->setType(\trim($class[1]));
                    continue;
                }

                $buffer .= ' ' . $line;
            }

            return \trim($buffer);
        }

        private function include(string $file, array $lines): array {
            $processed = [];

            foreach ($lines as $line) {
                $line = \trim($line);

                if (\preg_match('/^include:\s+(.+)/', $line, $path)) {
                    $include = \sprintf(
                        '%s%s%s',
                            \dirname($file),
                            \DIRECTORY_SEPARATOR,
                            \trim($path[1]));

                    if (!\file_exists($include)) {
                        throw new \Exception("$include does not exist");
                    }

                    if (($additional = \file($include))) {
                        $processed = \array_merge(
                            $processed,
                            $this->include(
                                $include, $additional));
                    }
                } else {
                    $processed[] = $line;
                }
            }

            return $processed;
        }

        private function tokenize(string $buffer): array {
            $tokens   = [];
            $length   = \strlen($buffer);
            $position = 0;
            $balance  = 
                function(
                    string $buffer,
                    int    $position,
                    string $open,
                    string $close) use($length): array {
                $depth   = 1;
                $content = '';
                $position++;

                while ($position < $length && $depth > 0) {
                    if ($buffer[$position] === $open)  $depth++;
                    if ($buffer[$position] === $close) $depth--;
                    if ($depth > 0) {
                        $content .= $buffer[$position];
                    }
                    $position++;
                }

                if ($depth !== 0) {
                    throw new \Exception(
                        "$this->file contains unmatched $open in \"$content\", missing $close");
                }

                return [\trim($content), $position];
            };

            while ($position < $length) {
                if (\ctype_space($buffer[$position])) {
                    $position++;
                    continue;
                }

                switch ($buffer[$position]) {
                    case ':':
                        $tokens[] = [
                            'type' =>
                            'COLON'
                        ];
                        $position++;
                        break;

                    case '|':
                        $tokens[] = [
                            'type' =>
                            'PIPE'
                        ];
                        $position++;
                        break;

                    case '(':
                        [$content, $position] = $balance($buffer, $position, '(', ')');
                        $tokens[]      = [
                            'type' => 'PAREN',
                            'value' => $content
                        ];
                        break;

                    case '{':
                        [$content, $position] = $balance($buffer, $position, '{', '}');
                        $tokens[]      = [
                            'type' => 'BRACE',
                            'value' => $content
                        ];
                        break;

                    default:
                        if (\ctype_alpha($buffer[$position]) || $buffer[$position] === '_') {
                            $ident = '';
                            while (($position < $length) &&
                                   (
                                        \ctype_alnum($buffer[$position]) ||
                                        $buffer[$position] === '_' ||
                                        $buffer[$position] === '\\'
                                    )) {
                                $ident .= $buffer[$position++];
                            }

                            $tokens[] = [
                                'type' => 'IDENT',
                                'value' => $ident
                            ];

                            /* Attach quantifier if it directly follows the identifier. */
                            if (($position < $length) && 
                                (
                                    $buffer[$position] === '*' ||
                                    $buffer[$position] === '+' ||
                                    $buffer[$position] === '?'
                                )) {
                                $tokens[] = [
                                    'type' => 'QUANTIFIER',
                                    'value' => $buffer[$position++]
                                ];
                            }
                        } else {
                            $position++;
                        }
                }
            }

            return $tokens;
        }

        /* Parse a space-separated symbol sequence from a PAREN body, each symbol
         * optionally followed by a *, +, or ? quantifier.
         *
         *   "LEFT_BRACKET KEY RIGHT_BRACKET"  → no quantifiers
         *   "type IDENTIFIER block?"          → block has quantifier '?'
         */
        private function parseSymbols(string $sequence): array {
            $symbols = [];
            \preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)([*+?])?/', $sequence, $matches, \PREG_SET_ORDER);
            foreach ($matches as $match) {
                $symbols[] = [
                    'name'       => $match[1],
                    'quantifier' => ($match[2] ?? '') !== '' ? $match[2] : null,
                ];
            }
            return $symbols;
        }

        /*
         * ── Grammar file parsing ─────────────────────────────────────────────────
         *
         *   grammar     := rule*
         *   rule        := IDENT COLON alternative (PIPE alternative)*
         *   alternative := PAREN BRACE            (action:     (expr) { code })
         *                | PAREN                  (sequence:   (A B? C*))
         *                | IDENT QUANTIFIER?       (expression: rule[*+?])
         */

        private function parse(array $tokens): void {
            $position = 0;
            $count    = \count($tokens);

            $peek = function () use (&$position, $tokens, $count): ?array {
                return $position < $count ? $tokens[$position] : null;
            };

            $consume = function () use (&$position, $tokens, $count): ?array {
                return $position < $count ? $tokens[$position++] : null;
            };

            $expect = function (string $type) use (&$position, &$consume): array {
                $token = $consume();
                if ($token === null ||
                    $token['type'] !== $type) {
                    $got =
                        $token ?
                            "{$token['type']}(" . ($token['value'] ?? '') . ")" :
                            'EOF';
                    throw new \Exception("Expected $type, got $got near token " . ($position - 1));
                }
                return $token;
            };

            while ($position < $count) {
                $name = $expect('IDENT');
                $expect('COLON');

                while (true) {
                    $next = $peek();

                    if ($next === null) {
                        throw new \Exception("Unexpected EOF in rule '{$name['value']}'");
                    }

                    if ($next['type'] === 'PAREN') {
                        $paren = $consume();
                        $ahead = $peek();
                        if ($ahead && $ahead['type'] === 'BRACE') {
                            $consume();
                            $this->actionRule($name['value'], $paren['value'], $ahead['value']);
                        } else {
                            /* Bare parens: a multi-symbol sequence without an action. */
                            $this->sequenceRule($name['value'], $paren['value']);
                        }
                    } elseif ($next['type'] === 'IDENT') {
                        $ident = $consume();
                        /* Consume a quantifier directly attached to this symbol. */
                        $ahead      = $peek();
                        $quantifier = ($ahead && $ahead['type'] === 'QUANTIFIER')
                                        ? $consume()['value']
                                            : null;
                        $this->expressionRule($name['value'], $ident['value'], $quantifier);
                    } else {
                        throw new \Exception(
                            "Unexpected {$next['type']} in rule '{$name['value']}'");
                    }

                    $next = $peek();
                    if ($next === null || $next['type'] !== 'PIPE') {
                        break;
                    }
                    $consume();
                }
            }
        }

        private function actionRule(string $rule, string $expression, string $action): void {
            $this->rules[$rule][] = [
                'symbols' => $this->parseSymbols($expression),
                'action'  => \trim($action),
            ];
        }

        /* Bare PAREN without BRACE: sequence rule, parsed symbol-by-symbol. */
        private function sequenceRule(string $rule, string $expression): void {
            $this->rules[$rule][] = [
                'symbols' => $this->parseSymbols($expression),
                'action'  => null,
            ];
        }

        /* Single IDENT (optionally quantified) referencing another rule. */
        private function expressionRule(string $rule, string $symbol, ?string $quantifier = null): void {
            $this->rules[$rule][] = [
                'symbols' => [[
                    'name'       => \trim($symbol),
                    'quantifier' => $quantifier,
                ]],
                'action' => null,
            ];
        }

        private function compile(): void {
            $this->compiled = $this->rules;
            $this->desugarQuantifiers();

            $this->start = isset($this->compiled['unit'])
                ? 'unit'
                : \array_key_last($this->compiled);

            $this->identifyTerminals();
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
                foreach ($alternatives as &$alt) {
                    foreach ($alt['symbols'] as &$sym) {
                        if ($sym['quantifier'] === null) {
                            continue;
                        }

                        $base  = $sym['name'];
                        $type  = $sym['quantifier'];
                        $kind  = match ($type) {
                            '*'     => 'star',
                            '+'     => 'plus',
                            '?'     => 'opt',
                        };
                        $name =
                            \sprintf("__%s_%s__",
                                $base, $kind);

                        if (!isset($synthetic[$name])) {
                            $self = [['name' => $name, 'quantifier' => null]];
                            $one  = [['name' => $base, 'quantifier' => null]];

                            $synthetic[$name] = match ($kind) {
                                'star' => [
                                    ['symbols' => [],                        'action' => null], /* ε */
                                    ['symbols' => \array_merge($self, $one), 'action' => null], /* A* A */
                                ],
                                'plus' => [
                                    ['symbols' => $one,                      'action' => null], /* A */
                                    ['symbols' => \array_merge($self, $one), 'action' => null], /* A+ A */
                                ],
                                'opt' => [
                                    ['symbols' => [],    'action' => null], /* ε */
                                    ['symbols' => $one,  'action' => null], /* A */
                                ],
                            };
                            $this->synthetic[$name] = $kind;
                        }

                        $sym['name']       = $name;
                        $sym['quantifier'] = null;
                    }
                }
            }

            $this->compiled = \array_merge($this->compiled, $synthetic);
        }

        /* A terminal is any symbol that is not itself a defined rule. */
        private function identifyTerminals(): void {
            $ruleNames = \array_keys($this->compiled);

            foreach ($this->compiled as $alternatives) {
                foreach ($alternatives as $alt) {
                    foreach ($alt['symbols'] as $sym) {
                        if (!\in_array($sym['name'], $ruleNames, true)) {
                            $this->terminals[$sym['name']] = true;
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

            $root = null;
            foreach ($chart[$limit] as $id) {
                $item = $items[$id];

                $alt  = $this->compiled[
                    $item['rule']
                ][$item['alt']];

                if ($item['rule']   === $this->start &&
                    $item['origin'] === 0 &&
                    $item['dot']    === \count($alt['symbols'])) {
                    $root = $id;
                    break;
                }
            }

            if ($root === null) {
                throw new \Exception(
                    "{$file->getPath()} does not match Grammar: at '{$this->start}'");
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
                }
            };

            foreach ($this->compiled[$this->start] as $altIdx => $_) {
                $add(0, [
                    'rule'   => $this->start,
                    'alt'    => $altIdx,
                    'dot'    => 0,
                    'origin' => 0,
                    'backs'  => []]);
            }

            for ($i = 0; $i <= $limit; $i++) {
                $j = 0;
                while ($j < \count($chart[$i])) {
                    $itemId = $chart[$i][$j++];
                    $item   = $items[$itemId];
                    $alt    = $this->compiled[$item['rule']][$item['alt']];
                    $dotted = $alt['symbols'][$item['dot']] ?? null;

                    if ($dotted === null) {
                        /* Complete */
                        foreach ($chart[$item['origin']] as $prevId) {
                            $prev    = $items[$prevId];
                            $prevAlt = $this->compiled[$prev['rule']][$prev['alt']];
                            $prevSym = $prevAlt['symbols'][$prev['dot']] ?? null;

                            if ($prevSym !== null && $prevSym['name'] === $item['rule']) {
                                $add($i, [
                                    'rule'   => $prev['rule'],
                                    'alt'    => $prev['alt'],
                                    'dot'    => $prev['dot'] + 1,
                                    'origin' => $prev['origin'],
                                    'backs'  => [
                                        [
                                            'prev' => $prevId,
                                            'child' => $itemId
                                        ]
                                    ],
                                ]);
                            }
                        }
                    } elseif (isset($this->terminals[$dotted['name']])) {
                        /* Scan */
                        if ($i < $limit && $tokens[$i]['type'] === $dotted['name']) {
                            $add($i + 1, [
                                'rule'   => $item['rule'],
                                'alt'    => $item['alt'],
                                'dot'    => $item['dot'] + 1,
                                'origin' => $item['origin'],
                                'backs'  => [
                                    [
                                        'prev' => $itemId,
                                        'token' => $i
                                    ]
                                ],
                            ]);
                        }
                    } else {
                        /* Predict */
                        if (!isset($this->compiled[$dotted['name']])) {
                            throw new \Exception(
                                "Unknown symbol '{$dotted['name']}' in Grammar: {$this->file} at rule '{$item['rule']}'");
                        }
                        foreach ($this->compiled[$dotted['name']] as $altIdx => $_) {
                            $add($i, [
                                'rule'   => $dotted['name'],
                                'alt'    => $altIdx,
                                'dot'    => 0,
                                'origin' => $i,
                                'backs'  => [],
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
                if ($alt['action'] !== null) {
                    return $this->callAction($alt['action'], [], $node);
                }

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
                if (empty($items[$cur]['backs'])) {
                    break;
                }
                $back        = $items[$cur]['backs'][0];
                $backs[$pos] = $back;
                $cur         = $back['prev'];
            }

            $values = \array_fill(0, $nSymbols, null);
            for ($pos = 0; $pos < $nSymbols; $pos++) {
                if (!isset($backs[$pos])) {
                    continue;
                }
                $back         = $backs[$pos];
                $values[$pos] = isset($back['token'])
                    ? ($tokens[$back['token']]['value'] ?? $tokens[$back['token']]['type'])
                    : $this->evalItem($back['child'], $tokens, $items, $node);
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

        private function setLexer(string $grammar, string $location): void {
            $this->lexer = new Lexer($grammar, $location);
        }

        private function setType(string $type): void {
            $this->type = $type;
        }

        public function factory(Parser $parser): Node {
            return new $this->type($parser);
        }
    }
}