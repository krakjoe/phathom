!An assets directory is required at runtime, this directory will be used by default!

During compilation of grammar files, a PHP class is generated from the actions therein; This file must be written to the physical disk - so that it may be cached and optimized as normal code - which means at some point you need to execute with write permission.

For a read-only deployment, you may deploy assets generated during testing/staging so long as the paths remain consistent.

You may change the assets directory location at runtime, see `Grammar::__construct(File $grammar, ?File $assets = null)`.

*Changes to grammar files will result in re-generation of assets upon subsequent invocation.*