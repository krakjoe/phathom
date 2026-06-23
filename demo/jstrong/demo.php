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