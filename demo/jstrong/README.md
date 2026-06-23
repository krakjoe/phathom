# jstrong - a phathom demonstration

JStrong is a json-ish flavour language with inline objects and self-references:

```json
{
    "object": new MyObject {
        "MyObjectProperty": "MyPropertyValue", 
    },
    "reference": {
        "MyReference": $this->object,
    }
}
```

The [grammar](grammar/jstrong.grammar) to do this is a **60 line module** (on top of [json](grammar/json.grammar) grammar, less than 60 lines).

JStrong is a demonstration of the flexibility of phathom and its modular grammar format; it is *not* meant as a demonstration of a complete JSON parser.

# Get Started

In this directory: `composer install`, optionally followed by `vendor/bin/phpunit`.

```php
<?php declare(strict_types=1);

use \pharos\phathom;

require __DIR__ . '/vendor/autoload.php';

class MyObject
{
    public private(set) string $Property;
}

$grammar = new phathom\Grammar(
    new phathom\File(
        "grammar/jstrong.grammar"),
    new phathom\Assets(
        new phathom\File("src/assets")
    ));

$parser = new phathom\Parser($grammar);

var_dump($parser->parse(
    new phathom\File("demo.jstrong")));
```

Will yield:

```
array(2) {
  ["object"]=>
  object(MyObject)#155 (1) {
    ["Property"]=>
    string(15) "MyPropertyValue"
  }
  ["reference"]=>
  array(1) {
    ["MyReference"]=>
    object(MyObject)#155 (1) {
      ["Property"]=>
      string(15) "MyPropertyValue"
    }
  }
}
```

## Notes

 - The test suite is written to achieve 100% coverage over generated files.
 - The test harness is setup to write assets to `src`, this demonstrates how to collect coverage and possibly deploy (or even commit) generated assets.
 - This is *demonstration* code:
   - The parser could be stricter
   - There is no encoder
   - Some errors might be swallowed