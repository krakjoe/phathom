# Garbage Collection

The cost of the engine can be visible at the garbage collector during parsing, this is somewhat unavoidable in userland - it's necessary to create a significant number of objects for the chart and evaluator, and while cycles can be avoided (and are where practical), it's not possible to entirely avoid creating objects.

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

#### Notes

*Even when the extension is loaded and usurps performance critical parts of the engine, the user code embedded in grammar files still may create significant GC overhead during parsing, which the extension cannot avoid.*