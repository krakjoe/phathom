<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Grammar;
    use \pharos\phathom\Assets;

    use \pharos\phathom\Exception;
    use \pharos\phathom\Exception\IO as IOException;

    final class Generator {
        public private(set) File         $asset;
        public private(set) string|false $symbol = false;

        public function __construct(
            private ?Assets $assets,
            private string  $type,
            private array   $rules) {

            if ($this->assets === null) {
                $self = new \pharos\phathom\File(__FILE__);

                try {
                    $default =
                        $self->relative(
                            "../../../assets");
                    $this->assets = new Assets($default);
                } catch(IOException $ex) {
                    throw new Exception(
                        "could not find the default assets directory");
                }
            }
        }

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

        private function compileClassMeta() : array {
            $class = \sprintf(
                "__%s__",
                \md5(
                    \json_encode(
                        $this->rules)));
            return [
                $class,
                \sprintf(
                    "%s.php", $class),
                \sprintf(
                    "\pharos\phathom\assets\%s", $class)];
        }

        private function compileClass() : string {
            if ($this->symbol !== false) {
                return $this->symbol;
            }

            [$class, $file, $symbol] =
                $this->compileClassMeta();

            $generate = function() use(&$class) {
                $result = [
                    "<?php",
                    \sprintf("namespace pharos\phathom\assets;")
                ];

                $result[] =
                    \sprintf(
                        "final class %s extends %s {",
                    $class,
                    $this->type);

                foreach ($this->rules as $name => $rule) {
                    foreach ($rule as $index => $alternative) {
                        if ($alternative['action']) {
                            $result[] = \sprintf(
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

                $result[] = "}";

                return \implode("\n", $result);
            };

            $this->asset =
                $this->assets
                    ->entry($symbol, $generate);
            $this->symbol = $symbol;

           require_once(
                (string) $this->asset);
            return $this->symbol;
        }

        public function __toString() : string {
            return $this->compileClass();
        }
    }
}
?>