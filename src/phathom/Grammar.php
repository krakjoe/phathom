<?php
namespace pharos\phathom
{
    use \pharos\phathom\Exception\Undeclared as UndeclaredException;
    use \pharos\phathom\Exception\Execute    as ExecuteException;
    use \pharos\phathom\Exception\Directive  as DirectiveException;

    final class Grammar
    {
        private             ?Lexer  $lexer     = null;
        private             ?string $context   = null;

        private             array   $rules     = [];   /* raw rules from grammar file parse       */
        private             array   $compiled  = [];   /* desugared rules used by the Earley loop */
        private             array   $terminals = [];   /* terminal name => true                   */
        private             array   $patterns  = [];   /* pattern terminal name => true           */
        private             array   $synthetic = [];   /* name => 'star'|'plus'|'opt'             */
        private             string  $start;            /* name of starting rule, unit or last */

        public function __construct(
            public private(set)  File $file,
            public private(set) ?File $assets = null) {

            new Grammar\Parser($this);

            if ($this->lexer === null) {
                throw new UndeclaredException(
                    "$this->file does not declare a lexer");
            }

            if ($this->context === null) {
                throw new UndeclaredException(
                    "$this->file does not declare a context");
            }

            if (empty($this->rules)) {
                throw new UndeclaredException(
                    "$this->file does not declare any rules");
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
                    'name'       => $token['value'],
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

            $this->context = (string)
                new Grammar\Generator(
                    $this->assets,
                    $this->context,
                    $this->compiled);

            $this->start = isset($this->rules['unit'])
                ? 'unit'
                : \array_key_last($this->rules);
        }

        public function execute(Context $context): Context {
            $tokens =
                $this->lexer
                    ->tokenize(
                        $context->parser->file);
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
                    $context, $this->start, $tokens, $limit)) {
                throw new ExecuteException(
                    "{$context->parser->file} does not match ".
                        "'{$this->start}' in {$this->file}");
            }

            return $context;
        }

        public function setLexer(string $location): void {
            if ($this->lexer !== null) {
                throw new DirectiveException(
                    "lexer already declared as {$this->lexer->file}");
            }

            $this->lexer = new Lexer(
                $this->file->relative($location));
        }

        public function setContext(string $context): void {
            if ($this->context !== null) {
                throw new DirectiveException(
                    "context already declared as {$this->context}");
            }

            $parents = @\class_parents($context);

            if ($parents === false) {
                throw new DirectiveException(
                    "{$context} does not exist, it must be autoloadable");
            }

            if (!\in_array(Context::class, $parents)) {
                throw new DirectiveException(
                    "{$context} does not extend \\pharos\\phathom\\Context");
            }

            $this->context = $context;
        }

        public function factory(Parser $parser): Context {
            return new $this->context($parser);
        }
    }
}