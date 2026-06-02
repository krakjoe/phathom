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

### Notes

Patterns must include appropriate PCRE delimiters (and escaping), their form will be verified on lexer compilation (triggered by grammar compilation).

*It is not necessary to anchor regex, the engine does that automatically.*

Lexer files themselves are not modular (ie, they don't support include directly) because the standard builtin PHP INI parser is used to parse them. Grammar files treat lexer configuration as modular by allowing the merging of many lexer configurations from any fragment of grammar.

Lexer files **cannot** redefine tokens defined in other lexer files, this will raise a parse time error.