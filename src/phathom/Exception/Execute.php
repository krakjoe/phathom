<?php
namespace pharos\phathom\Exception {
    use \pharos\phathom\Context;

    final class Execute extends \pharos\phathom\Exception {
        public static function nomatch(
            Context $context,
            string  $start,
            array   $tokens) : Execute {

            $first = $tokens[0];
            $last  = $tokens[\count($tokens) - 1];

            return new self(\sprintf(
                "Input %s does not match '%s' in %s: ".
                "unexpected tokens from %s, ".
                "last token %s",
                $context->parser->file,
                $start,
                $context->parser->grammar->file,
                $first::print($first),
                $last::print($last)
            ));
        }
    }
}
?>