# Grammar Files

Grammar files drive phathom's parsing engines. A grammar file contains:

  - **directives** — instructions for the grammar parser and compiler
  - **rules**      — definitions of semantic structure with optional actions
  - **comments**   — lines beginning with `#` are ignored

Their form has been chosen to align with the taste of a PHP developer; (E)BNF does not fall off the fingers naturally.

`phathom` grammar being a mix of JSON and PHP syntax, with a terminating `;`, separating `:` and `|` ought to feel more natural to us.

---

## Directives

Form: `directive: "string" [, "string"];`

| Directive   | Explanation                                                                          | Required | Repeatable | Substitutable |
|-------------|--------------------------------------------------------------------------------------|----------|------------|---------------|
| `engine`    | FQCN of an implementation of `\pharos\phathom\Grammar\Interface\Engine`              |    no    |     no     |      no       |
| `start`     | Name of the starting rule                                                            |    no    |     no     |      n/a      |
| `context`   | FQCN of a descendant of `\pharos\phathom\Context`                                    |    no    |     yes    |      yes      |
| `token`     | FQCN of a descendant of `\pharos\phathom\Token`                                      |    no    |     yes    |      yes      |
| `lexer`     | Path, relative to the current grammar file, of a lexer `.ini`                        |    no    |     yes    |      n/a      |
| `include`   | Path, relative to the current grammar file, of another grammar file                  |    no    |     yes    |      n/a      |
| `optimizer` | FQCN of a descendant of `\pharos\phathom\Grammar\Optimization`                       |    no    |     yes    |      n/a      |
| `collector` | Name of garbage collection policy                                                    |    no    |     no     |      no       |

### `engine`

FQCN of an implementation of `\pharos\phathom\Grammar\Interface\Engine`.

```
engine: "\pharos\phathom\GLR\Engine";
```

**The default `\pharos\phathom\GLR\Engine` will be used when no `engine` directive is present**

*See: [ENGINES.md](ENGINES.md) for details*

### `start`

Name of the starting rule, which must be available at compile time (ie, may reference a yet-to-be-included rule).

```
start: "unit";
```

**The default `unit` will be used when no `start` directive is present**

### `context`

FQCN of a descendant of `pharos\phathom\Context` (or the previous declaration) for scope of action code; the engine will derive a concrete `Context` from this class.

```
context: "\MyApp\Context";
```

*`context` supports (LSP) substitution via repitition.*

**The class `pharos\phathom\Context` will be used when no `context` directive is present**

### `token`

FQCN of an abstract descendant of `\pharos\phathom\Token` (or the previous declaration) that represents parsed values; the engine will derive a `Token` from this class.

```
token: "\MyApp\Token";
```

*The class must extend the abstract `\pharos\phathom\Token`, without implementing `string`, and so remain abstract.*

*`token` supports (LSP) substitution via repitition.*

**The abstract `pharos\phathom\Token` will be used when no `token` directive is present**

### `include`

Merges another grammar into the current grammar.

Included files support all directives.

```
include: "expressions.grammar";
```

*Paths are relative to the current grammar*

*Circular and duplicate includes are detected and rejected at parse time.*

### `lexer`

Specifies a lexer configuration file (INI format) to merge into the current grammar.

```
lexer: "tokens.lexer";
```

*Paths are relative to the current grammar*

*See: [LEXER.md](LEXER.md)*

### `optimizer`

FQCN of a descendant of `pharos\phathom\Grammar\Optimization`; the engine will invoke an instance of this object at compile time.

```
optimizer: "\my\app\optimization";
```

**`\pharos\phathom\Grammar\Optimize\Lexer` is used by default**

Available Optimizations (not loaded by default):

  - `\pharos\phathom\Grammar\Optimize\Literals`

*See: [OPTIMIZATION.md](OPTIMIZATION.md) for details*

### `collector`

```
collector: "default";
```

Name of the garbage collection policy:

  - `default` - don't touch the garbage collector
  - `off`     - disable the garbage collector during pressure
  - `defer`   - defer the garbage collector during pressure

**`default` is used by default**

*See: [COLLECTOR.md](COLLECTOR.md) for details*

---

## Rules

A rule gives a name to one or more alternative sequences of symbols.

```
rule: alternative ;
rule: alternative | alternative | ... ;
```

Alternatives are separated by `|`.  Each alternative is either a bare quantifiable symbol
or a parenthesised expression optionally followed by annotations and/or an action block.

### Symbols

A symbol is either:

- an **ident** — a token name from the lexer or the name of another rule
- a **pattern** — an inline regex enclosed in `< >` (see [Inline Patterns](#inline-patterns))

*Note: unknown symbols will throw `Exception\Undefined` at compile time*

Every symbol may carry an optional [quantifier](#quantifiers).

### Expressions

An expression is a parenthesised sequence of one or more quantifiable symbols:

```
rule: (SYM_A SYM_B SYM_C) ;
```

An expression may be followed by a [priority](#priority) and/or an [action](#actions).

### Quantifiers

Quantifiers follow a symbol (inside or outside an expression) and control repetition.
The compiler rewrites quantified references into synthetic rules automatically.

| Quantifier | Meaning                | Synthetic expansion                                 |
|------------|------------------------|-----------------------------------------------------|
| `+`        | one or more            | `$X_plus$ : X \| ($X_plus$ X)`                      |
| `*`        | zero or more           | `$X_star$ : ε \| ($X_star$ X)`                      |
| `?`        | zero or one (optional) | `$X_opt$ : ε \| X`                                  |

Quantified symbols are represented by arrays, unfound optional symbols being an empty array.

**Examples:**

```
# one or more items
unit: item+ ;

# zero or more lines
document: line* ;

# optional trailer
row: (OPEN value CLOSE trailer?) ;
```

### Inline Patterns

A pattern is an inline PCRE regex, enclosed in angle brackets, used where a dedicated
lexer token would be overly general.  Patterns become anonymous tokens added to the
lexer at compile time.

```
numeric: </\d+/> ;

line: (ALPHA EQUALS numeric)     { return $this->pair((string) $1, (int) (string) $3); }
    | (ALPHA EQUALS </[A-Z]/>+)  { return $this->pair((string) $1, (array) $3); }
    ;
```

### Annotations

Annotations follow expressions in the form of `[annotation]`, multiple annotations are accepted.

`annotation` must match `[0-9a-zA-z]+`, illegal annotations will throw `Exception\Unexpected` at compile time.

Unrecognized annotations will throw `Exception\Annotation` at compile time.

### Priority

Match: `[n]` where `n` is a positive integer.

When a rule is ambiguous — multiple alternatives can match the same input — priority annotations select the preferred alternative. Higher integers win.

```
line: (ALPHA EQUALS ALPHA) [1] { return "low";  }
    | (ALPHA EQUALS ALPHA) [2] { return "high"; }
    ;
```

Rules:

- Annotations must be present on **all** alternatives in a rule, or **none** — mixing raises `Exception\Priority`.
- A single annotated alternative is inert and raises `Exception\Priority`.
- Priorities must be unique within a rule unless associativity annotations are also present (see [Associativity](#associativity)).
- Priority is rule-scoped: it does not propagate to rules that reference this one.

Where input is ambiguous and no priority annotations are present, `Exception\Ambiguity` is raised at parse time.

*Note: `Exception\Priority` is a compile time exception; `Exception\Ambiguity` is a parse time exception.*

### Associativity

Match: `left`, `right`, or `none`.

When multiple alternatives share the same priority — typically for a recursive rule like `expr → expr OP expr` — associativity annotations break the tie by selecting which parse tree to prefer.

- `[left]` — prefer the left-recursive parse: `(a OP b) OP c`
- `[right]` — prefer the right-recursive parse: `a OP (b OP c)`
- `[none]` — explicit absence of associativity; equivalent to omitting the annotation

```
expr: (expr EQUALS expr) [1][left] { return [$1, $3]; }
    | (ALPHA)            [1][left] { return (string) $1; }
    ;
```

Rules:

- An associativity annotation requires a priority annotation on the same alternative — raises `Exception\Associativity` otherwise.
- All alternatives sharing a priority must carry the **same** non-`none` associativity annotation — conflicting or absent annotations within a priority group raise `Exception\Associativity`.
- An associativity annotation on an alternative whose priority is unique within the rule is inert and raises `Exception\Associativity`.

*Note: `Exception\Associativity` is a compile time exception; `Exception\Ambiguity` is a parse time exception.*

### Actions

An action is a PHP code block that is called when an alternative is successfully matched.
It is enclosed in `{ }` and placed after the expression (and after any annotations).

Inside an action: 

  - Concrete `Token` is imported into this `Context`
  - `$this` is the `Context` object derived from the `context` directive (or default `\pharos\phathom\Context`)
  - `$1`, `$2`, … `$n` refer to the values of the matched symbols in order passed as function arguments
  - The type of function arguments is inferred:
    - `Token` for terminal
    - `array` for synthetic
    - `mixed` otherwise
  - References to undefined variables will raise `Exception\Undefined` at compile time

```
item: (ALPHA EQUALS NUM) {
    return $this->pair((string) $1, (int) (string) $3);
} ;
```

Things to know:

  - Casting `Token` to `string` returns the textual value of the token
  - `$1` ... `$3` are `Token` (in this example case)
  - `$1->type === Token::ALPHA` and `$3->type === Token::NUM`

The return value of the action becomes the semantic value of the matched rule for use by other rules.

---

## Context Class

The context class must extend `\pharos\phathom\Context`.

An instance is created for each instance of `\pharos\phathom\Parser`, this means that repeated calls to the same instance of `Parser::parse` reuses the same `Context`.

```php
namespace MyApp;

class ParseContext extends \pharos\phathom\Context {
    protected function pair(string $key, int $value): array {
        return [$key => $value];
    }
}
```

---

## Complete Example

**`example.lexer`**

```ini
[ALPHA]
pattern = "/[a-zA-Z]+/"

[NUM]
pattern = "/[0-9]+/"

[EQUALS]
pattern = "/=/"

[WHITESPACE]
pattern = "/\s+/"
skip = true
```

**`example.grammar`**

```
lexer:   "example.lexer";
context: "\MyApp\ParseContext";

item: (ALPHA EQUALS NUM) {
    # $1...$3 are Token
    return $this->pair(
          (string) $1,
    (int) (string) $3);
};

# one or more items
unit: item+ ;
```

**`example.php`**

```php
<?php
require_once("vendor/autoload.php");

$grammar =
    new \pharos\phathom\Grammar(
        new File("example.grammar"));

$parser =
    new \pharos\phathom\Parser($grammar);

$result = $parser->parse(
    new File("example.conf"));
```

---

## Modularity

In the general parser framework case, grammar files are monolithic (the zend language parser is ~2k lines for example): If a new feature is added, you have to pick apart at least one giant file to implement it.

For phathom, grammar files are modular - they may merge additional lexer configuration and *append alternatives for rules defined in other grammar*.

---

## Formal Reference

```
ident        := [a-zA-Z_][a-zA-Z0-9_]*
pattern      := '<' [^>]+ '>'
quantifier   := [+*?]
annotation   := '[' [0-9a-zA-Z]+ ']'
quantifiable := (ident | pattern) quantifier?
expression   := '(' quantifiable+ ')' annotation*
action       := '{' code '}'
end          := ';'
quote        := ('\'' | '"')
string       := quote [^\1]+ quote
alternative  := expression action?
              | quantifiable
strings      := string
              | strings comma string
grammar      := (directive | rule)*
directive    := ident COLON strings end
rule         := ident COLON alternative (PIPE alternative)* end
```

---

## Security

Grammar files may contain arbitrary PHP code in action blocks. That code is written to
disk as a compiled asset and executed during parsing.

**Grammar files must come from trusted sources and be treated as executable code: Never parse grammars from untrusted input.**

*See [ASSETS.md](ASSETS.md)*