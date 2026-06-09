# Optimizations

Optimization passes allow for special preparation of the grammar at compile time.

The optimization pass unseals the Compiler and passes it deconstructed to the concrete implementation of `\pharos\phathom\Grammar\Optimization`, it then invokes the concrete `pass` method, and requests a reconstruction from the implementation.

Construction of the `Optimization` and reconstruction of `Compiler` after the pass is executed are implemented by the abstract, and cannot be altered.

The concrete implementation only requires `pass` to be implemented:

```
namespace my\app\Optimization {
    final class Optimization extends \pharos\phathom\Grammar\Optimization {
        public function pass() : void {
            /* do things with deconstructed Compiler,
                for details see the abstract,
                for example see src/phathom/Earley/Optimize/Lexer.php */
        }
    }
}
```

in grammar:

```
optimizer: "\my\app\Optimization";
```

## Notes

Optimization passes run at the tail of the compilation pipeline, such that optimizations are persisted with the serial form of Grammar.

### `\pharos\phathom\Earley\Optimize\Lexer`

Loaded: `yes`

This pass warms the lexer pattern cache, see the [implementation](src/phathom/Earley/Optimize/Lexer.php) for more details.