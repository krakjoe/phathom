# Optimizations

Optimization passes allow for special preparation of the grammar at compile time.

Flow:
`Grammar\Optimizer` unseals the `Grammar` by passing a deconstruction to a concrete implementation of `\pharos\phathom\Grammar\Optimization`, it then invokes the `pass` method. Where `pass` returned `true`, requests a reconstruction from the implementation.

Construction of the `Optimization` and reconstruction of `Grammar` after a committed pass are implemented by the abstract, and cannot be altered.

The concrete implementation only requires `pass` to be implemented:

```
namespace my\app {
    final class Optimization extends \pharos\phathom\Grammar\Optimization {
        /* 
           executed precisely once with generated === true
            (pre-generation, $this->symbols are abstract)
           executed precisely once with generated === false
            (post-generation, $this->symbols are concrete)

           $this->symbols are never committed
        */
        public function pass(bool $generated) : bool {
            if ($generated) {
                /* omit to take part in post-generation pass */
                return false;
            }

            /* do things with deconstructed Grammar, or Engine::$automaton
                for details see the abstract,
                for example see src/phathom/Earley/Optimize/Lexer.php */

            /* return true to commit changes */
            return true;
        }
    }
}
```

in grammar:

```
optimizer: "\my\app\Optimization";
```

## Optimizers

### `\pharos\phathom\Grammar\Optimize\Lexer`

Loaded: `yes`

This pass warms the lexer pattern cache, see the [implementation](src/phathom/GLR/Optimize/Lexer.php) for more details.

### `\pharos\phathom\Grammar\Optimize\Literals`

Loaded: `no`

This pass populates a literal token cache with any token whose pattern defines a literal string - ie, doesn't use any special regex characters.

#### Notes

Optimization passes run at the tail of the compilation pipeline, such that optimizations are persisted with the serial form of Grammar.