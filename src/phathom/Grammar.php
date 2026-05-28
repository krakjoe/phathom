<?php
namespace pharos\phathom
{
    use \pharos\phathom\Exception\Undeclared as UndeclaredException;
    use \pharos\phathom\Exception\Execute    as ExecuteException;
    use \pharos\phathom\Exception\Directive  as DirectiveException;
   
    final class Grammar
    {
        private             ?Lexer  $lexer     = null;

        private              array  $abstracts  = [
            'token'   =>     '\pharos\phathom\Token',
            'context' =>     null,
        ];
        public private(set) string $token;             /* concrete Token implementation */
        public private(set) string $context;           /* concrete Context implementation */

        private             array   $rules     = [];   /* raw rules from grammar file parse       */
        private             array   $compiled  = [];   /* desugared rules used by the Earley loop */
        private             array   $terminals = [];   /* terminal name => true                   */
        private             array   $patterns  = [];   /* pattern terminal name => true           */
        private             array   $synthetic = [];   /* name => 'star'|'plus'|'opt'             */
        private             string  $start;            /* name of starting rule, unit or last */

        public function __construct(
            public private(set)  File   $file,
            public private(set) ?Assets $assets = null) {

            new Grammar\Parser($this);

            if ($this->lexer === null) {
                throw new UndeclaredException(
                    "$this->file does not declare a lexer");
            }

            if ($this->abstracts['context'] === null) {
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
                'symbols'  => $symbols,
                'priority' => $priority,
                'action'   =>
                    ($action !== null) ?
                        \trim($action) : null,
            ];
        }

        public function simpleRule(string $rule, Grammar\Symbol $symbol): void {
            $this->rules[$rule][] = [
                'symbols'  => [$symbol],
                'priority' => false,
                'action'   => null,
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

            $generator = new Grammar\Generator(
                    $this->assets,
                    $this->abstracts,
                    $this->lexer,
                    $this->compiled,
                    $this->synthetic);
            [
                $this->token,
                $this->context
            ] = $generator->generate();

            $this->start = isset($this->rules['unit'])
                ? 'unit'
                : \array_key_last($this->rules);
        }

        public function execute(Context $context): Context {
            $tokens =
                $this->lexer
                    ->tokenize(
                        $context->parser
                            ->file,
                        $this->token);
            $limit = \count($tokens);

            $builder =
                new Earley\Chart(
                    $this->compiled,
                    $this->terminals,
                    $this->patterns,
                    $this->start,
                    $tokens, $limit);

            [$chart, $items] = $builder->build();

            $evaluator =
                new Earley\Evaluator(
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

        public function setAbstract(string $type, string $abstract, string $parent) : void {
            if ($this->abstracts[$type] !== null &&
                $this->abstracts[$type] !== $parent) {
                throw new DirectiveException(
                    "{$type} already declared as {$this->abstracts[$type]}");
            }

            $parents = @\class_parents($abstract);

            if ($parents === false) {
                throw new DirectiveException(
                    "{$abstract} does not exist, it must be autoloadable");
            }

            $parents = array_map(function(string $parent) : string {
                return "\\$parent";
            }, $parents);

            if (!\in_array($parent, $parents)) {
                throw new DirectiveException(
                    "{$abstract} does not extend {$parent}");
            }

            $this->abstracts[$type] = $abstract;
        }

        public function factory(Parser $parser): Context {
            return new $this->context($parser);
        }

        public function __serialize() : array {
            return [
                'file'      => $this->file,
                'assets'    => $this->assets,
                'lexer'     => $this->lexer,
                'abstracts' => $this->abstracts,
                'compiled'  => $this->compiled,
                'synthetic' => $this->synthetic,
                'terminals' => $this->terminals,
                'patterns'  => $this->patterns,
                'start'     => $this->start,
            ];
        }

        public function __unserialize(array $array) : void {
            foreach ($array as $member => $value) {
                $this->$member = $value;
            }

            $generator =
                new Grammar\Generator(
                    $this->assets,
                    $this->abstracts,
                    $this->lexer,
                    $this->compiled,
                    $this->synthetic);
            [
                $this->token,
                $this->context
            ] = $generator->generate();
        }
    }
}