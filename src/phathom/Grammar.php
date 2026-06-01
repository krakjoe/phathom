<?php
namespace pharos\phathom
{    
    use \pharos\phathom\Exception\Undeclared as UndeclaredException;
    use \pharos\phathom\Exception\Execute    as ExecuteException;
    use \pharos\phathom\Exception\Directive  as DirectiveException;
   
    final class Grammar
    {
        /* raw members */
        private             array   $included   = [];   /* raw include list                             */
        private             array   $directives = [];   /* raw directive information                    */
        private             array   $rules      = [];   /* raw rules                                    */
    
        /* parsed members */
        private             ?Lexer  $lexer     = null;  /* declared lexer                               */
        private              array  $abstracts  = [     /* declared abstracts                           */
            'token'   =>     '\pharos\phathom\Token',
            'context' =>     '\pharos\phathom\Context',
        ];

        /* compiled members */
        public private(set) string  $token;             /* compiled concrete Token implementation       */
        public private(set) string  $context;           /* compiled concrete Context implementation     */
        private             array   $compiled   = [];   /* compiled rules used by the Earley loop       */
        private             array   $terminals  = [];   /* compiled terminals name => const int Token:: */
        private             array   $patterns   = [];   /* compiled patterns name => const int Token::  */
        private             string  $start;             /* compiled name of starting rule, unit or last */

        public function __construct(
            public private(set)  File   $file,
            public private(set) ?Assets $assets = null) {
            $this->lexer =
                new Lexer();
            $this->parse();
        }

        private function parse() : void {
            $parser = new Grammar\Parser($this->file);
            [
                $this->included,
                $this->directives,
                $this->rules,
            ] = $parser->parse();

            foreach ($this->directives['lexer'] as $path => $location) {
                $this->lexer
                    ->merge(new File($path));
            }

            if ($this->directives['context'] !== false) {
                $this->setAbstract(
                    'context',
                    (string) $this->directives['context'],
                    $this->abstracts['context']);
            }

            if ($this->directives['token'] !== false) {
                $this->setAbstract(
                    'token',
                    (string) $this->directives['token'],
                    $this->abstracts['token']);
            }

            if (empty($this->rules)) {
                throw new UndeclaredException(
                    "$this->file does not declare any rules");
            }

            $this->compile();
        }

        private function compile(): void {
            $compiler =
                new Grammar\Compiler(
                    $this->file,
                    $this->lexer,
                    $this->rules);
            [
                $this->compiled,
                $this->terminals,
                $this->patterns
            ] = $compiler->compile();

            $generator = new Grammar\Generator(
                    $this->assets,
                    $this->abstracts,
                    $this->lexer,
                    $this->compiled);
            [
                $this->token,
                $this->context
            ] = $generator->generate();

            $this->start = isset($this->rules['unit'])
                ? 'unit'
                : \array_key_last($this->rules);

            unset($this->rules);
        }

        public function execute(Context $context): mixed {
            $tokens =
                $this->lexer
                    ->tokenize(
                        $context->parser
                            ->input,
                        $this->token);
            $limit = \count($tokens);

            $builder =
                new Earley\Chart(
                    $this->compiled,
                    $this->terminals,
                    $this->patterns,
                    $this->start,
                    $tokens, $limit);

            $chart = $builder->build();

            $evaluator =
                new Earley\Evaluator(
                    $context,
                    $chart);

            return $evaluator->enter($this->start, $tokens, $limit);
        }

        private function setAbstract(string $type, string $abstract, string $parent) : void {
            if (!\class_exists($abstract)) {
                throw new DirectiveException(
                    "{$abstract} does not exist, it must be autoloadable");
            }

            $parents = \array_map(function(string $parent) : string {
                return "\\$parent";
            }, \class_parents($abstract));

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
                    $this->compiled);
            [
                $this->token,
                $this->context
            ] = $generator->generate();
        }
    }
}
?>