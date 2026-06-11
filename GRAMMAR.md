
# Grammar Files

Grammar files drive phathom's Earley parsing engine. A grammar file contains:

  - **directives** — instructions for the grammar parser and compiler
  - **rules**      — definitions of semantic structure with optional actions
  - **comments**   — lines beginning with `#` are ignored

---

## Directives

Form: `directive: "string";`

| Directive   | Explanation                                                                          | Required | Repeatable |
|-------------|--------------------------------------------------------------------------------------|----------|------------|
| `start`     | Name of the starting rule                                                            |    no    |     no     |
| `context`   | Fully-qualified class name of a descendant of `\pharos\phathom\Context`              |    no    |     no     |
| `token`     | Fully-qualified class name of a descendant of `\pharos\phathom\Token`                |    no    |     no     |
| `lexer`     | Path, relative to the current grammar file, of a lexer `.ini`                        |    no    |     yes    |
| `include`   | Path, relative to the current grammar file, of another grammar file                  |    no    |     yes    |
| `optimizer` | Fully-qualified class name of a descendant of `\pharos\phathom\Grammar\Optimization` |    no    |     yes    |

### `start`

Name of the starting rule, which must be available at compile time (ie, may reference a yet-to-be-included rule).

```
start: "unit";
```

**The default `unit` will be used when no `start` directive is present**

### `context`

FQCN of a descendant of `pharos\phathom\Context` for scope of action code; the engine will derive a concrete `Context` from this class.

```
context: "\MyApp\Context";
```

**The class `pharos\phathom\Context` will be used when no `context` directive is present**

### `token`

FQCN of an abstract descendant of `\pharos\phathom\Token` that represents parsed values; the engine will derive a `Token` from this class.

```
token: "\MyApp\Token";
```

*The class must extend the abstract `\pharos\phathom\Token`, without implementing `string`, and so remain abstract.*

**The abstract `pharos\phathom\Token` will be used when no `token` directive is present**

### `include`

Merges another grammar into the current grammar.

Included files support all directives (although `token` and `context` are not repeatable).

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

**`\pharos\phathom\Earley\Optimize\Lexer` is used by default**

*See: [OPTIMIZATION.md](OPTIMIZATION.md) for details*

---

## Rules

A rule gives a name to one or more alternative sequences of symbols.

```
rule: alternative ;
rule: alternative | alternative | ... ;
```

Alternatives are separated by `|`.  Each alternative is either a bare quantifiable symbol
or a parenthesised expression optionally followed by a priority and/or an action block.

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

### Priority

When a grammar is ambiguous and multiple parse trees are valid for a single rule, priority annotations select the preferred alternative. Higher integers win.

Form: `[n]` immediately after the closing `)` of an expression:

```
line: (ALPHA EQUALS ALPHA) [1] { return $this->make("low",  $1); }
    | (ALPHA EQUALS ALPHA) [2] { return $this->make("high", $1); } 
    ;
```

Priority annotations must be present for *all* alternatives in a rule, or *none*, missing priority annotations will raise `Exception\Priority` at compile time (ie, when a Grammar is constructed).

A priority annotation on a rule with a single alternative expression is inert, and will raise `Exception\Priority` at compile time.

Priority annotations must be unique (with respect to the rule of the alternative, not globally); equal priorities among alternatives will raise `Exception\Priority` at compile time.

Priority annotations have rule-scope, ie: they determine how to resolve ambiguity in a single rule, but do not effect the priorities of a consumer of that rule.

Where input is ambiguous because multiple parse paths exists and priority annotations are missing `Exception\Ambiguity` will be raised.

*Note: `Exception\Priority` is a compile time only exception, `Exception\Ambiguity` is an execution time only exception.*

### Actions

An action is a PHP code block that is called when an alternative is successfully matched.
It is enclosed in `{ }` and placed after the expression (and after any priority annotation).

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

The context class must extend `\pharos\phathom\Context`, actions may invoke `protected` API.  An instance is created automatically by the parser for each parse.

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
priority     := '[' [0-9]+ ']'
quantifiable := (ident | pattern) quantifier?
expression   := '(' quantifiable+ ')' priority?
action       := '{' code '}'
end          := ';'
quote        := ('\'' | '"')
string       := quote [^\1]+ quote
alternative  := expression action?
              | quantifiable

grammar      := (directive | rule)*
directive    := ident COLON string end
rule         := ident COLON alternative (PIPE alternative)* end
```

---

## Security

Grammar files may contain arbitrary PHP code in action blocks. That code is written to
disk as a compiled asset and executed during parsing.

**Grammar files must come from trusted sources and be treated as executable code: Never parse grammars from untrusted input.**

*See [ASSETS.md](ASSETS.md)*