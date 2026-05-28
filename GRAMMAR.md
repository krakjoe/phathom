
# Grammar Files

Grammar files drive phathom's Earley parsing engine. A grammar file contains:

  - **directives** — instructions for the grammar parser and compiler
  - **rules**      — definitions of semantic structure with optional actions
  - **comments**   — lines beginning with `#` are ignored

---

## Directives

Form: `directive: "string";`

| Directive | Explanation                                                             | Required |
|-----------|-------------------------------------------------------------------------|----------|
| `lexer`   | Path, relative to the current grammar file, of the lexer `.ini`         |    yes   |
| `context` | Fully-qualified class name of a descendant of `\pharos\phathom\Context` |    yes   |
| `token`   | Fully-qualified class name of a descendant of `\pharos\phathom\Token`   |    no    |
| `include` | Path, relative to the current grammar file, of another grammar file     |    no    |

### `lexer`

Specifies the lexer configuration file (INI format) to use when tokenizing input.

```
lexer: "tokens.lexer";
```

### `context`

Names the PHP class that receives parsed values through action callbacks.

```
context: "\MyApp\Context";
```

*The class must extend `\pharos\phathom\Context`.*

### `token`

Names the (abstract) PHP class that represents parsed values through action callbacks.

```
token: "\MyApp\Token";
```

*The class must extend the abstract `\pharos\phathom\Token`.*

**The abstract `pharos\phathom\Token` will be used when no `token` directive is used`

### `include`

Merges another grammar file's rules into the current grammar.
Included files do not need their own `lexer` or `context` directives — they inherit those
from the including file.

```
include: "expressions.grammar";
```

*Circular and duplicate includes are detected and rejected at parse time.*

---

## Lexer Files

Lexer files are INI files. Each section defines one token type.

```ini
[TOKEN_NAME]
pattern = "/regex/"
skip    = true        ; optional — matched text is discarded (e.g. whitespace)
```

**Example:**

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

Token names defined here are available as terminals in grammar rules.

---

## Rules

A rule gives a name to one or more alternative sequences of symbols.

```
rule-name: alternative ;
rule-name: alternative | alternative | ... ;
```

Alternatives are separated by `|`.  Each alternative is either a bare quantifiable symbol
or a parenthesised expression optionally followed by a priority and/or an action block.

### Symbols

A symbol is either:

- an **ident** — a token name from the lexer or the name of another rule
- a **pattern** — an inline regex enclosed in `< >` (see [Inline Patterns](#inline-patterns))

Every symbol may carry an optional [quantifier](#quantifiers).

### Expressions

An expression is a parenthesised sequence of one or more quantifiable symbols:

```
rule-name: (SYM_A SYM_B SYM_C) ;
```

An expression may be followed by a [priority](#priority) and/or an [action](#actions).

### Quantifiers

Quantifiers follow a symbol (inside or outside an expression) and control repetition.
The compiler rewrites quantified references into synthetic rules automatically.

| Quantifier | Meaning              | Synthetic expansion                                 |
|------------|----------------------|-----------------------------------------------------|
| `+`        | one or more          | `__X_plus__ : X \| __X_plus__ X`                   |
| `*`        | zero or more         | `__X_star__ : ε \| __X_star__ X`                   |
| `?`        | zero or one (optional) | `__X_opt__ : ε \| X`                              |

Actions on synthetic rules receive an array (for `+` and `*`) or a single value / `null` (for `?`).

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

When a grammar is ambiguous and multiple parse trees are valid, a priority annotation
on an expression selects the preferred alternative.  Higher integers win.

Form: `[n]` immediately after the closing `)` of an expression.

```
low:  (ALPHA EQUALS ALPHA) [1] { return $this->make("low",  $1); } ;
high: (ALPHA EQUALS ALPHA) [2] { return $this->make("high", $1); } ;

line: low | high ;
```

Priority propagates through synthetic quantifier rules, so it applies correctly
when the prioritised rule is reached via `+`, `*`, or `?`.

### Actions

An action is a PHP code block that is called when an alternative is successfully matched.
It is enclosed in `{ }` and placed after the expression (and after any priority annotation).

Inside an action, `$1`, `$2`, … `$n` refer to the values of the matched symbols in order, their type is inferred.

`$this` is the context object specified by the `context` directive.

```
item: (ALPHA EQUALS NUM) {
    return $this->pair((string) $1, (int) (string) $3);
} ;
```

*Note: we know that ALPHA and NUM are instanceof `Token`, casting to string retrieves the value of the Token object*

The return value of the action becomes the semantic value of the matched rule for use
by other rules.

#### `$n` substitution

Action code is treated as an opaque string, so `$1` … `$n` are replaced textually before the code is written to disk:

```php
item: (ALPHA) {
    echo "I paid a stranger \$100 to tickle me until I cried";
    /* ... */
};
```

Keep action code concise and avoid JIT-unfriendly constructs such as variable variables.

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
    new \pharos\phathom\Parser(
        $grammar, 
        new File("example.conf"));

$result = $parser->parse();
```

---

## Formal Reference

```
ident        := [^\s#:|<>(){}+*?"';]+
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