<?php
namespace pharos\phathom
{
    final class Grammar
    {
        public private(set) File    $file;
        private             string  $assets;
        private             array   $rules     = [];   /* raw rules from grammar file parse       */
        private             array   $compiled  = [];   /* desugared rules used by the Earley loop */
        private             array   $terminals = [];   /* terminal name => true                   */
        private             array   $patterns  = [];   /* pattern terminal name => true           */
        private             array   $synthetic = [];   /* name => 'star'|'plus'|'opt'             */
        private             ?string $start     = null;
        private             ?string $type      = null;
        private             ?Lexer  $lexer     = null;

        public function __construct(File $file) {
            $this->file = $file;

            new Grammar\Parser($this);

            if ($this->lexer === null) {
                throw new \Exception(
                    "$this->file does not declare a lexer");
            }

            if ($this->type === null) {
                throw new \Exception(
                    "$this->file does not declare a type");
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

        public function simpleRule(string $rule, array $token, ?string $quantifier = null): void {
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
            $compiler =
                new Grammar\Compiler(
                    $this->file,
                    $this->lexer,
                    $this->rules);
            [
                $this->compiled,
                $this->synthetic,
                $this->terminals,
                $this->patterns
            ] = $compiler->compile();

            $this->type = (string)
                new Grammar\Generator(
                    $this->type, $this->compiled);

            $this->start = isset($this->rules['unit'])
                ? 'unit'
                : \array_key_last($this->rules);
        }

        public function execute(Parser $parser, Node $node): Node {
            $tokens =
                $this->lexer
                    ->tokenize(
                        $parser->file);
            $limit = \count($tokens);

            $builder =
                new Grammar\Chart(
                    $this->compiled,
                    $this->terminals,
                    $this->patterns,
                    $this->start,
                    $tokens, $limit);

            [$chart, $items] = $builder->build();

            $evaluator =
                new Grammar\Evaluator(
                    $this->compiled,
                    $this->synthetic,
                    $chart, $items);

            if (!$evaluator->enter(
                    $this->start, $tokens, $limit, $node)) {
                throw new \Exception(
                    "{$parser->file} does not match ".
                        "'{$this->start}' in {$this->file}");
            }

            return $node;
        }

        public function setLexer(string $location): void {
            $this->lexer = new Lexer(
                $this->file->relative($location));
        }

        public function setType(string $type): void {
            $this->type = $type;
        }

        public function factory(Parser $parser): Node {
            return new $this->type($parser);
        }
    }
}