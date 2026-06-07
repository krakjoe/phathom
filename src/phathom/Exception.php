<?php
namespace pharos\phathom {
    class Exception extends \Exception {
        protected static function explain(array $options, string $condition = 'or') : string {
            switch (\count($options)) {
                case 0:
                    return "end of input";

                case 1:
                    return $options[0];

                case 2:
                    return \vsprintf(
                        "%s {$condition} %s", $options);

                default:
                    $last =
                        \array_pop($options);
                    return \sprintf(
                        "%s, {$condition} %s",
                        \implode(", ", $options),
                        $last);
            }
        }
    }
}
?>