<?php
namespace pharos\phathom
{   
    final class Grammar
    {
        /* raw members */
        private             array   $included   = [];   /* raw include list                             */
        private             array   $directives = [];   /* raw directive information                    */
        private             array   $parsed     = [];   /* raw rules                                    */
    
        /* parsed members */
        public private(set)  Lexer  $lexer;             /* declared lexers                              */
        private              array  $abstracts = [      /* declared abstracts                           */
            'token'   =>     '\pharos\phathom\Token',
            'context' =>     '\pharos\phathom\Context',
        ];

        /* compiled members */
        public private(set) string  $context;             /* compiled concrete Context implementation     */
        public private(set) string  $token;               /* compiled concrete Token implementation       */
        public private(set) string  $start;               /* compiled name of starting rule               */
        public private(set) array   $rules      = [];     /* compiled rules used by the Earley loop       */
        public private(set) array   $terminals  = [];     /* compiled terminals name => const int Token:: */
        public private(set) array   $patterns   = [];     /* compiled patterns name => const int Token::  */

        public function __construct(
            public private(set)  File   $file,
            public private(set) ?Assets $assets = null) {
            $this->lexer =
                new Lexer();
            $this->parse();
        }

        private function parse() : void {
            $parser =
                new Grammar\Parser($this->file);
            [
                $this->included,
                $this->directives,
                $this->parsed,
            ] = $parser->parse();

            $this->compile();
        }

        private function compile(): void {
            $compiler =
                new Grammar\Compiler(
                    $this->file,
                    $this->lexer,
                    $this->directives,
                    $this->parsed);
            [
                $this->start,
                $this->rules,
                $this->terminals,
                $this->patterns,
                $this->abstracts,
            ] = $compiler->compile();

            $generator = new Grammar\Generator(
                    $this->assets,
                    $this->abstracts,
                    $this->lexer,
                    $this->rules);
            [
                $this->token,
                $this->context
            ] = $generator->generate();

            unset($this->parsed);
        }

        public function execute(Context $context, File|Buffer $input): mixed {
            $chart =
                new Earley\Chart($this, $input);
            return new Earley\Evaluator($chart, $context)();
        }

        public function factory(): Context {
            return new $this->context($this);
        }

        public function __serialize() : array {
            return [
                'file'      => $this->file,
                'assets'    => $this->assets,
                'lexer'     => $this->lexer,
                'abstracts' => $this->abstracts,
                'rules'     => $this->rules,
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
                    $this->rules);
            [
                $this->token,
                $this->context
            ] = $generator->generate();
        }
    }
}
?>