# Assets

**An assets directory is required at runtime, by default the asset directory at the root of the source tree will be used**

During compilation of grammar files, a PHP class is generated from the actions therein; This file must be written to the physical disk - so that it may be cached and optimized as normal code - which means at some point you need to execute with write permission.

For a read-only deployment, you may deploy assets generated during testing/staging so long as the paths remain consistent.

You may change the assets directory location at runtime, see `Grammar::__construct(File $grammar, ?Assets $assets = null)`.

The assets directory must contain a file named `.guard`.

*Changes to grammar files will result in re-generation of assets upon subsequent invocation.*

### Notes

There are two distinct motivations for using real files over closures (or any other eval-ish strategy) for semantic actions:

  - optimization
  - serialization

Real files are the only thing maximally compatible with opcache (and so JIT), any other strategy will pay significant penalties in terms of performance.

The internal state of a Grammar object must be serializable in order for them to be cached between requests, written to disk, or otherwise sent over the wire complete.