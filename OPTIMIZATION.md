# Optimizations

`Grammar\Optimizer` implements a double pass (speculative) optimization pipeline:

  - `pre-generation`
  - `post-generation`

*Note: `Grammar\Optimizer` runs as part of the compilation pipeline, before any oppportunity to serialize; this means changes made by `Optimization` instances are persistent in the serial state of `Grammar`; `Grammar\Optimizer` does not run after `__unserialize`.*

## pre-generation

At this stage of `Grammar` compilation, no `Assets` have been written, which means two things:

  - concrete symbols are not yet available
  - changes committed by the pass are reflected in `Assets`

## post-generation

At this stage of `Grammar` compilation, `Assets` have been written, which means two things:

  - concrete symbols are available
  - changes committed by the pass are not reflected in `Assets`

### Flow

`Grammar\Optimizer` passes a deconstructed `Grammar` to (a new instance of) each registered `Grammar\Optimization` at both stages.

The implementation of `Optimization::pass` may refuse to take part in a stage, or may apply changes speculatively - ie, it can return `false`.

Changes committed are threaded through `Grammar\Optimizer` to `Grammar`.

### Abstract

Follows is an abstract documentation of the public API for `Grammar\Optimization`:

```php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\Lexer;

    abstract class Optimization {
        /**
         * @var Interface\Engine $engine (never committed)
         **/
        protected Interface\Engine  $engine;

        /**
         * @var Lexer $lexer (never committed)
         **/ 
        protected Lexer             $lexer;

        /**
         * @var string $start string (committed)
         **/
        protected            string $start;

        /**
         * @var array $rules [string(name) => array(Alternative)]  (committed)
        **/
        protected            array  $rules;

        /**
         * @var array $terminals [string(name) => int(Token:: const)] (committed)
         **/
        protected            array  $terminals;

        /**
         * @var array $patterns  [string(pattern) => int(Token:: const)] (committed)
         **/
        protected            array  $patterns;

        /**
         * @var array $literals [int(Token:: const) => Token] (committed)
         **/
        protected            array  $literals;

        /**
         * Contains abstract types pre-generations, concrete post-generation
         * @var array $symbols [token => string, context => string] (never committed)
         **/
        protected            array  $symbols;

        /**
        * @param generated false === pre-generation, true == post-generation
        * @return bool true to commit, false to refuse
        */
        abstract public function pass(bool $generated) : bool;
    }
}
```

## Grammar

```
optimizer: "\my\app\Optimization";
```

### Optimizers

#### `\pharos\phathom\Grammar\Optimize\Lexer`

Loaded: `yes`

This pass warms the lexer pattern cache, see the [implementation](src/phathom/GLR/Optimize/Lexer.php) for more details.

#### `\pharos\phathom\Grammar\Optimize\Literals`

Loaded: `no`

This pass populates a literal token cache with any token whose pattern defines a literal string - ie, doesn't use any special regex characters.

Note that, enabling this optimization sacrifices location information for these `Token` instances; during development it is probably desirable to remain disabled for the sake of exception messages.