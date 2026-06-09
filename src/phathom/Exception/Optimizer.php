<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\Token;

    final class Optimizer extends \pharos\phathom\Exception {
        public static function threw(string $optimization, Token|false $directive, \Throwable $thrown) : Optimizer {
            return new self(\sprintf(
                "while executing %s (%s) an uncaught exception (%s) was thrown",
                $optimization,
                $directive === false ?
                    // @codeCoverageIgnoreStart
                    \sprintf("builtin") :
                    // @codeCoverageIgnoreEnd
                    \sprintf("from %s:%d",
                        $directive->location['path'],
                        $directive->location['position']),
                \get_class($thrown),
            ), 0, $thrown);
        }
    }
}