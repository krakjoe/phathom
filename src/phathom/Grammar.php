<?php declare(strict_types=1);
/*
  +----------------------------------------------------------------------+
  | phathom                                                              |
  +----------------------------------------------------------------------+
  | Copyright (c) Joe Watkins 2026                                       |
  +----------------------------------------------------------------------+
  | This source file is subject to the BSD 3-Clause License bundled     |
  | with this package in the file LICENSE.                               |
  +----------------------------------------------------------------------+
  | Author: krakjoe                                                      |
  +----------------------------------------------------------------------+
 */

namespace pharos\phathom
{   
    use \pharos\phathom\Grammar\Collector;
    use \pharos\phathom\Grammar\Interface\Engine;

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
        public private(set) Engine    $engine;            /* compiled engine                                */
        public private(set) array     $import     = [];   /* compiled imports                               */
        public private(set) string    $context;           /* compiled concrete Context implementation       */
        public private(set) string    $token;             /* compiled concrete Token implementation         */
        public private(set) string    $start;             /* compiled name of starting rule                 */
        public private(set) array     $rules      = [];   /* compiled rules used by the Earley loop         */
        public private(set) array     $terminals  = [];   /* compiled terminals name => const int Token::   */
        public private(set) array     $patterns   = [];   /* compiled patterns name => const int Token::    */
        public private(set) array     $literals   = [];   /* optionally compiled const int Token:: => Token */
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
                $engine,
                $optimizations,
                $this->import,
                $this->start,
                $this->rules,
                $this->terminals,
                $this->patterns,
                $this->abstracts,
                $this->collector,
            ] = $compiler->compile();

            $this->engine = new $engine($this);

            $this->optimize(
                \array_merge(
                    $this->engine
                        ->optimizations,
                    $optimizations), true);
        }

        private function optimize(array $optimizations, bool $generate) : void {
            $optimizer =
                new Grammar\Optimizer(
                    $this->engine,
                    $this->lexer,
                    $this->start,
                    $this->rules,
                    $this->terminals,
                    $this->patterns,
                    $this->literals,
                    $generate ? $this->abstracts : [
                        'token'   => $this->token,
                        'context' => $this->context,
                    ]);

            [
                $this->lexer,
                $this->start,
                $this->rules,
                $this->terminals,
                $this->patterns,
                $this->literals,
            ] = $optimizer->optimize(
                    $optimizations, !$generate);

            if ($generate === false) {
                return;
            }

            $this->generate($optimizations);
        }

        private function generate(array|false $optimizations) : void {
            $generator = new Grammar\Generator(
                $this->assets,
                \get_class(
                    $this->engine),
                $this->abstracts,
                $this->import,
                $this->lexer,
                $this->rules);
            [
                $this->token,
                $this->context
            ] = $generator->generate();

            if ($optimizations === false) {
                return;
            }

            $this->optimize($optimizations, false);
        }

        public function execute(Context $context, File|Buffer $input): mixed {
            $collections =
                Collector::apply($this->collector);
            try {
                $result =
                    ($this->engine)(
                        $context, $input);
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
                'start'     => $this->start,
                'rules'     => $this->rules,
                'terminals' => $this->terminals,
                'patterns'  => $this->patterns,
                'literals'  => $this->literals,
                'abstracts' => $this->abstracts,
                'collector' => $this->collector,
                'engine'    => $this->engine,
                'import'    => $this->import,
            ];
        }

        public function __unserialize(array $array) : void {
            foreach ($array as $member => $value) {
                $this->$member = $value;
            }

            $this->generate(false);
        }
    }
}
?>