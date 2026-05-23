<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Grammar;

    use \pharos\phathom\Exception;
    use \pharos\phathom\Exception\IO as IOException;

    final class Generator {
        public private(set) File         $asset;
        public private(set) string|false $symbol = false;

        public function __construct(
            private ?File  $assets,
            private string $type,
            private array  $rules) {

            if ($this->assets === null) {
                $self = new \pharos\phathom\File(__FILE__);

                try {
                    $this->assets =
                        $self->relative(
                            "../../../assets");
                } catch(IOException $ex) {
                    throw new Exception(
                        "could not find the default assets directory");
                }
            }

            if (!$this->assets->writable()) {
                // @codeCoverageIgnoreStart
                throw new IOException(
                    "{$this->assets} is not writable");
                // @codeCoverageIgnoreEnd
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

        private function compileClass() : string {
            if ($this->symbol !== false) {
                return $this->symbol;
            }

            $symbol =
                \sprintf(
                    "__%s__",
                    \md5(
                        \json_encode(
                            $this->rules)));

            $file =
                \sprintf(
                    "%s.php", $symbol);

            $this->symbol =
                \sprintf(
                    "pharos\phathom\assets\%s", $symbol);

            try {
                $this->asset =
                    $this->assets
                        ->relative($file);
                // @codeCoverageIgnoreStart
                require_once(
                    (string) $this->asset);
                return $this->symbol;
                // @codeCoverageIgnoreEnd
            } catch (IOException $ex) {
                /* continue, regenerate cache */
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

            $this->asset =
                $this->assets->put(
                    $file,
                    \implode(
                        "\n", $class));

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