<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\Context;

    final class Ambiguity extends \pharos\phathom\Exception {
        public static function range(
            Context $context,
            string  $rule,
            array   $tokens,
            int     $start,
            int     $end) : Ambiguity {

            return new self(\sprintf(
                "Unresolved ambiguity ".
                    "(multiple parse trees and no priority annotation) ".
                "for rule '%s' from %s " .
                "%s",
                $rule,
                $context->parser->grammar->file,
                $end < $start
                    ? "matching no tokens"
                    : \sprintf("spanning %s to %s",
                        $tokens[$start]::print($tokens[$start]),
                        $tokens[$end]::print($tokens[$end])),
            ));
        }
    }
}
?>