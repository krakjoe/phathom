# Multi-Engine Support

Earley is technically beautiful, but CPU and RAM hungry; additionally it is easy to read, understand, and debug, so it remains primarily for development time.

GLR is just as flexible as Earley but a few times faster in the real world and so is chosen as the default engine.

The engine declared in `Grammar` may be overridden with the `PHATHOM_ENGINE` environment variablle. Note that the env switch is a development time lever, as such it is not afforded the same verification as the directive - the existence and shape of the class will not be verified as it is by the parser when set from a directive.

Engines:

  - `\pharos\phathom\GLR\Engine`
  - `\pharos\phathom\Earley\Engine`

`PHATHOM_ENGINE` expects the FQCN of an implementor of `\pharos\phathom\Grammar\Interface\Engine`.

## Comparisons

Using a subset of PHP grammar for the purposes of testing and profiling, we can essentially characterise the performance of each Engine.

### Earley (PHP)

Because Earley is dynamic, the debugging procedure is somewhat kinder to developers not familiar with debugging parsing pipelines, but debugging the PHP will be costly.

The PHP Earley implementation is by far the slowest and hungriest available option for execution, no matter the shape of the grammar:

A 64kb sample will parse in an average of ~1000ms (1s).

### Earley (C)

The C implementation aggressively optimizes memory usage and relieves the pressure of GC and Zend MM with respect to the Grammar during parsing - note that user action code may induce it's own pressure which the extension cannot avoid.

A 64kb sample will parse in an average of ~400ms.

### GLR (PHP)

GLR is a table driven parser - that's to say that the Automaton constructed for analysis and optimization is retained for runtime, it contains the actual parsing tables necessary to execute (where Earley builds them on the fly).

It's just as flexible as Earley, in that in handles left recursion and ambiguity, however while Earley contains within its state all possible derivations, GLR only "forks" - that's to say creates parallel paths through a parse - when ambiguity actually arises in the input.

Because it's a table parser, built ahead of time of the parse, it can be somewhat overwhelming to debug. This is just a brute fact, I cannot change it.

A 64kb sample will parse in an average of ~220ms.

### GLR (C)

The C implementation takes all the same opportunities as the Earley implementation to optimize execution, and is much more successful at doing so - the margin between PHP and C for GLR is wider than the margin for Earley by percentage.

A 64kb sample will parse in an average of ~65ms.

#### Testing Notes

Note that all these tests were run on the same machine with opcache disabled in a release build of PHP 8.4.