<?php declare(strict_types=1);

namespace pharos\phathom\Exception {
    use \pharos\phathom\Grammar\Token;

    final class Annotation extends \pharos\phathom\Exception {
        public static function explain(array $annotations, string $condition = 'or') : string {
            $expected = [];

            foreach ($annotations as $annotation) {
                $expected[] = \sprintf(
                    "%s (%s) expected %s",
                    $annotation,
                    $annotation::name(),
                    $annotation::expect(),
                );
            }

            return parent::explain($expected, $condition);
        }

        public static function unknown(Token $directive, array $annotations) : Annotation {
            return new self(\sprintf(
                "Unknown annotation, %s, ".
                "got %s",
                self::explain($annotations, 'and'),
                $directive::print($directive)));
        }
    }
}