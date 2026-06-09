# Optimizations

Optimization passes allow for special preparation of the engine or grammar at compile time.

The optimization pass unseals the Compiler and passes it deconstructed to the implementation of `\pharos\phathom\Interface\Optimizer`, the implementation is expected to mirror the constructor (ie, return reconstruction) upon invocation:

```
namespace my\app\Optimization {
    use \pharos\phathom\Lexer;
    use \pharos\phathom\Interface\Optimizer;

    final class Optimization implements Optimizer {
        public function __construct(
            private Lexer  $lexer,
            private string $start,
            private array  $rules,
            private array  $terminals,
            private array  $patterns,
            private array  $abstracts
        ) {}

        public function __invoke() : array {
            return [
                $this->lexer,
                $this->start,
                $this->rules,
                $this->terminals,
                $this->patterns,
                $this->abstracts
            ];
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