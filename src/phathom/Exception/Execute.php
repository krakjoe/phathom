<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\File;
    use \pharos\phathom\Buffer;
    use \pharos\phathom\Context;

    final class Execute extends \pharos\phathom\Exception {
        public static function nomatch(
            Context $context,
            string  $start,
            array   $tokens) : Execute {

            if (\count($tokens)) {
                $first = $tokens[0];
                $last  = $tokens[
                    \count($tokens) > 1 ?
                        \count($tokens) - 1 :
                        0
                ];

                return new self(\sprintf(
                    "Input does not match '%s' in %s: ".
                    "unexpected tokens from %s, ".
                    "last token %s",
                    $start,
                    $context->grammar->file,
                    $first::print($first),
                    $last::print($last)
                ));
            } else {
                return new self(\sprintf(
                    "Input does not match '%s' in %s: ".
                    "no input available",
                    $start,
                    $context->grammar->file
                ));
            }
        }
    }
}
?>