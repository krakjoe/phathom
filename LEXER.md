## Lexer Format

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