# Garbage Collection

The cost of Earley can be visible at the garbage collector during parsing, this is somewhat unavoidable in userland - it's necessary to create a significant number of objects for the chart and evaluator, and while cycles can be avoided (and are where practical), it's not possible to entirely avoid creating objects.

The grammar itself is what determines whether it's necessary to disable the garbage collector; the control of the policy is turned over to a grammar directive.

## Policies

### `default`

This is the default mode - don't touch the garbage collector.

### `off`

For the duration of the parse, the garbage collector is disabled (ie, `\gc_disable()`).

When parsing completes, `\gc_collect_cycles()` shall not be invoked.

### `defer`

For the duration of the parse, the garbage collector is disabled (ie, `\gc_disable()`).

When parsing completes, `\gc_collect_cycles()` shall be invoked.