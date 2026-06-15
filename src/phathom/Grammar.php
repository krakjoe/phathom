<?php declare(strict_types=1);

namespace pharos\phathom
{   
    use \pharos\phathom\Grammar\Collector;

    final class Grammar
    {
        /* raw members */
        private             array     $included   = [];   /* raw include list                             */
        private             array     $directives = [];   /* raw directive information                    */
        private             array     $parsed     = [];   /* raw rules                                    */
    
        /* parsed members */
        public private(set)  Lexer    $lexer;             /* declared lexers                              */
        private              array    $abstracts = [      /* declared abstracts                           */
            'token'   =>     '\pharos\phathom\Token',
            'context' =>     '\pharos\phathom\Context',
        ];

        /* compiled members */
        public private(set) string    $context;           /* compiled concrete Context implementation     */
        public private(set) string    $token;             /* compiled concrete Token implementation       */
        public private(set) string    $start;             /* compiled name of starting rule               */
        public private(set) array     $rules      = [];   /* compiled rules used by the Earley loop       */
        public private(set) array     $terminals  = [];   /* compiled terminals name => const int Token:: */
        public private(set) array     $patterns   = [];   /* compiled patterns name => const int Token::  */
        /* compiled Collector policy */
        public private(set) Collector $collector  = Collector::DEFAULT;

        public function __construct(
            public private(set)  File   $file,
            public private(set) ?Assets $assets = null) {
            $this->lexer =
                new Lexer();
            $this->parse();
            unset($this->included);
            unset($this->directives);
            unset($this->parsed);
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
                $this->collector,
                $this->start,
                $this->rules,
                $this->terminals,
                $this->patterns,
                $this->abstracts,
            ] = $compiler->compile();

            $this->generate();
        }

        private function generate() : void {
            $generator = new Grammar\Generator(
                $this->assets,
                $this->abstracts,
                $this->lexer,
                $this->rules);
            [
                $this->token,
                $this->context
            ] = $generator->generate();
        }

        public function execute(Context $context, File|Buffer $input): mixed {
            $collections =
                Collector::apply($this->collector);
            try {
                $chart =
                new Earley\Chart($this, $input);
                $result =
                    new Earley\Evaluator(
                        $chart, $context)();
            } finally {
                Collector::restore($collections);
            }
            return $result;
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
                'collector' => $this->collector,
            ];
        }

        public function __unserialize(array $array) : void {
            foreach ($array as $member => $value) {
                $this->$member = $value;
            }

            $this->generate();
        }
    }
}
?>