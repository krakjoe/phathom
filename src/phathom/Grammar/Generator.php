<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\Grammar;

    final class Generator {
        public function __construct(
            private string $type,
            private array $rules) {}

        private function compileAction(array $alternative) : array {
            $parameters = [];
            $action     = $alternative['action'];

            foreach ($alternative['symbols'] as $index => $symbol) {
                $parameter =
                    \sprintf(
                        '$__sym%d__',
                        $index + 1);
                $action = \preg_replace(
                    \sprintf(
                        '/\$%d(?!\d)/',
                        $index + 1
                    ),
                    $parameter,
                    $action);

                $parameters[] = \sprintf("mixed %s", $parameter);
            }

            return [implode(", ", $parameters), $action];
        }

        private function compileClass() : string {
            $symbol =
                \sprintf(
                    "__%s__",
                    \md5(
                        \json_encode(
                            $this->rules)));
            $path = \sprintf("assets/%s.php", $symbol);

            if (\file_exists($path)) {
                return \sprintf(
                    "pharos\phathom\assets\%s", $symbol);
            }

            $class = [
                "<?php",
                \sprintf("namespace pharos\phathom\assets;")
            ];

            $class[] =
                \sprintf(
                    "final class %s extends %s {",
                $symbol,
                $this->type);

            foreach ($this->rules as $name => $rule) {
                foreach ($rule as $index => $alternative) {
                    if ($alternative['action']) {
                        $class[] = \sprintf(
                            "\tpublic function __action_%s_%d__(%s) : mixed {\n".
                                    "\t\t%s\n".
                            "\t}",
                            $name,
                            $index,
                            ... $this->compileAction(
                                    $alternative));
                    }
                }
            }

            $class[] = "}";

            file_put_contents(
                $path, \implode("\n", $class));

            return \sprintf(
                "pharos\phathom\assets\%s", $symbol);
        }

        public function __toString() : string {
            return $this->compileClass();
        }
    }
}
?>