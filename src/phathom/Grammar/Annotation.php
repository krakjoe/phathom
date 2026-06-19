<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    use \pharos\phathom\Exception\Annotation as AnnotationException;

    final class Annotation {
        public static function factory(
            array $annotations, Token $value) : Interface\Annotation {
            foreach ($annotations as $annotation) {
                if ($annotation::match($value)) {
                    return new $annotation($value);
                }
            }

            throw AnnotationException::unknown($value, $annotations);
        }
    }
}
?>