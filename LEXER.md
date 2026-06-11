## Lexer Format

Lexer files are standard sectioned INI files; Each section defines one token type:

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

Token names (ie `[section]` headers) are available as terminals in grammar rules.

### Context Sensitivity

The `Earley\Chart` and `Lexer` implementations interact to achieve a kind of context sensitivity: during parsing we are already inferring the expected tokens at the current position in the parse, so we pass that information to the Lexer to request that it scans for only the expected tokens at the current position, rather than all tokens defined, at every position.

This is a nice perf optimization and additionally simplifies writing Lexers; you don't need declaratively express that tokens are context-sensitive (thus we don't need to complicate INI format Lexers), their sensitivity in the current context being inferred by the engine.

### Longest Match Tie Breaker

The Lexer will attempt to find the longest match from the expected tokens at the current position, when more than one token of equal length could be constructed for the current position the tie breaker is *declaration order* in the Lexer.

### Notes

Token names (ie, section headers) must conform to PHP identifier specification, ie `[a-zA-Z][a-zA-Z0-9_]*`.

Patterns must include appropriate PCRE delimiters (and escaping), their form will be verified on lexer compilation (triggered by grammar compilation).

*It is not necessary to anchor regex, the engine does that automatically.*

Lexer files themselves are not modular (ie, they don't support include directly) because the standard builtin PHP INI parser is used to parse them. Grammar files treat lexer configuration as modular by allowing the merging of many lexer configurations from any fragment of grammar.

Lexer files **cannot** redefine tokens defined in other lexer files, this will raise a parse time error.