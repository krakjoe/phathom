# Assets

**An assets directory is required at runtime, by default the asset directory at the root of the source tree will be used**

During compilation of grammar files, 2 PHP classes are generated (Token, Context); These files must be written to the physical disk - so they may be cached and optimized as normal code - which means at some point you need to execute with write permission.

For a read-only deployment, you may deploy assets generated during testing/staging so long as the paths remain consistent.

You may change the assets directory location at runtime, see `Grammar::__construct(File $grammar, ?Assets $assets = null)`.

The assets directory must contain a file named `.guard`.

*Changes to grammar/lexer files will result in re-generation of assets upon subsequent invocation.*

## Token

A class derived from `\pharos\phathom\Token` (or a user provided abstract, set with the `token` directive) is generated with constant integer identifiers for
all named token types in the lexer configuration.

*This allows the Earley engine to reference Tokens by integer identifier rather than strings, and exposes the Token in Context, such that the programmer can see location information and token type*

## Context

A class derived from a user provided  `\pharos\phathom\Context` (set with the `context` directive) is generated containing all action code.

*The `Token` concrete implementation is imported as `Token`, such that action code referencing `Token` is referring to the concrete class*

### Notes

There are two distinct motivations for using real files over closures (or any other eval-ish strategy) for semantic actions:

  - optimization
  - serialization

Real files are the only thing maximally compatible with opcache (and so JIT), any other strategy will pay significant penalties in terms of performance.

The internal state of a Grammar object must be serializable in order for them to be cached between requests, written to disk, or otherwise sent over the wire complete.

#### Caution

Assets are automatically regenerated when content changes, but at regeneration time we cannot know which file we are replacing, and since it has a different file name, deployments that regularly change their grammars will need to prune their assets directory.