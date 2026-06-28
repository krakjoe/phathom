# Assets

**An assets directory is required at runtime, by default the asset directory at the root of the `phathom` source tree will be used**

During compilation of grammar files, 2 PHP classes are generated (Token, Context); These files must be written to the physical disk - so they may be cached and optimized as normal code - which means at some point you need to execute with write permission.

You should change the assets directory location at runtime, see `Grammar::__construct(File $grammar, ?Assets $assets = null)`.

*Changes to grammar/lexer files will result in re-generation of assets upon subsequent invocation.*

## The guard

**The assets directory must contain a file named `.guard`.**

The framework will **not** create this file; it's recommended to store assets in your source tree for versioning and housekeeping; *The existence of a .guard file is how we know assets deployment has been considered properly by the programmer, and that we are not accidentally going to create files in an undesirable, possibly security sensitive, or otherwise incorrect location.*

### Token

A class derived from `\pharos\phathom\Token` (or a user provided abstract, set with the `token` directive) is generated with constant integer identifiers for
all named token types in the lexer configuration.

*This allows the engine to reference Tokens by integer identifier rather than strings, and exposes the Token in Context, such that the programmer can see location information and token type*

### Context

A class derived from a user provided  `\pharos\phathom\Context` (set with the `context` directive) is generated containing all action code.

*The `Token` concrete implementation is imported as `Token`, such that action code referencing `Token` is referring to the concrete class*

#### Deploying Assets

Grammar **is code**, not data; as such you will want to collect coverage on this code, version it, and store it alongside other application code.

**Assets are automatically regenerated when content changes, but at regeneration time we cannot know which file we are replacing, and since it has a different file name, deployments that regularly change their grammars live will need to prune their assets directory.**

If `Grammar` and the resulting assets are treated as code, and committed with your application, then pruning becomes a workflow problem solved with normal VCS workflow idioms.

`phathom` does not provide an out-of-the-box solution for asset pruning beyond a description of best practice, because no such thing can exist and be generally fit for purpose; `phathom` storage model includes serialization, and the storage models for serialized data are innumerable. It also includes physical file generation, these physical files should be generated in your source code tree; We are not releasing a tool that can potentially wipe out your source code if you make a mistake.

*The solution to pruning depends entirely on your deployment model and requires either careful bespoke implementation, or no implementation at all; we provide and prefer the latter!*

#### Notes

There are two distinct motivations for using real files over closures (or any other eval-ish strategy) for semantic actions:

  - optimization
  - serialization

Real files are the only thing maximally compatible with opcache (and so JIT), any other strategy will pay significant penalties in terms of performance.

The internal state of a Grammar object must be serializable in order for them to be cached between requests, written to disk, or otherwise sent over the wire complete.