<?php declare(strict_types=1);

namespace pharos\phathom\Grammar\Annotation {
    use \pharos\phathom\Exception;
    use \pharos\phathom\Grammar\Interface\Annotation as AnnotationInterface;

    final class Associativity implements AnnotationInterface {
        public private(set) \pharos\phathom\Grammar\Associativity $value;

        public function __construct(
            \pharos\phathom\Grammar\Token $token) {
            $this->value =
                \pharos\phathom\Grammar\Associativity::from(
                    (string) $token);
        }

        public static function name() : string {
            return "associativity";
        }

        public static function expect() : string {
            return Exception::explain([
                'left', 'right', 'none']);
        }

        public static function match(
            \pharos\phathom\Grammar\Token $token) : bool {
            return \in_array(
                \strtolower((string) $token),
                ['left', 'right', 'none'], true);
        }
    }
}
?>