<?php
namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Grammar;
    use \pharos\phathom\Assets;
    use \pharos\phathom\Lexer;

    use \pharos\phathom\Exception;
    use \pharos\phathom\Exception\IO as IOException;

    final class Generator {
        public function __construct(
            private ?Assets $assets,
            private array   $abstracts,
            private Lexer   $lexer,
            private array   $rules,
            private array   $synthetic) {

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

        private function compileContext(string $token) : string {
            [$class, $symbol] =
                $this->compileContextMeta();

            $generate = function() use(&$class, &$token) {
                $result = [
                    "<?php",
                    \sprintf("namespace pharos\phathom\assets\Context;"),
                    \sprintf(
                        "use %s as Token;", $token),
                ];

                $result[] =
                    \sprintf(
                        "final class %s extends %s {",
                    $class,
                    $this->abstracts['context']);

                foreach ($this->rules as $name => $rule) {
                    foreach ($rule as $index => $alternative) {
                        if ($alternative['action']) {
                            $result[] = \sprintf(
                                "\tpublic function __action_%s_%d__(%s) {\n".
                                        "\t\t%s\n".
                                "\t}",
                                $name,
                                $index,
                                ... $this->compileContextMethod(
                                        $alternative));
                        }
                    }
                }

                $result[] = "}";

                return \implode("\n", $result);
            };

            require_once(
                (string) $this->assets
                    ->entry($class, $generate));
            return $symbol;
        }

        private function compileContextMeta() : array {
            $class = \sprintf(
                "__%s__",
                \md5(
                    \serialize([
                        $this->abstracts['context'],
                        $this->rules])));
            $symbol = \sprintf(
                "\pharos\phathom\assets\Context\%s", $class);
            return [$class, $symbol];
        }

        private function compileContextMethod(array $alternative) : array {
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

                if (!isset($this->rules[$symbol->name])) {
                    $type = "Token";
                } elseif (isset($this->synthetic[$symbol->name])) {
                    $type = 'array';
                } else {
                    $type = 'mixed';
                }

                $parameters[] = \sprintf("%s %s", $type, $parameter);
            }

            return [implode(", ", $parameters), $action];
        }

        private function compileToken() : string {
            [$class, $symbol] =
                $this->compileTokenMeta();

            $generate = function() use(&$class) {
                $result = [
                    "<?php",
                    \sprintf("namespace pharos\phathom\assets\Token;"),
                ];

                $result[] =
                    \sprintf(
                        "final class %s extends %s {",
                    $class,
                    $this->abstracts['token']);

                $result = \array_merge(
                    $result,
                    $this->compileTokenConstants());
                $result[] = "";
                $result[] = "\tpublic static function string(int \$type) : string {";
                $result[] = "\t\tswitch(\$type) {";
                $result = \array_merge(
                    $result,
                    $this->compileTokenStrings());
                $result[] = "\t\t\tdefault:";
                $result[] = "\t\t\t\treturn \"UNKNOWN\";";
                $result[] = "\t\t}";
                $result[] = "\t}";
                $result[] = "}";

                return \implode("\n", $result);
            };

            require_once(
                (string) $this->assets
                    ->entry($class, $generate));
            return $symbol;
        }

        private function compileTokenMeta() : array {
            $class = \sprintf(
                "__%s__",
                \md5(
                    \serialize([
                        $this->abstracts['token'],
                        $this->lexer])));
            $symbol = \sprintf(
                "\pharos\phathom\assets\Token\%s", $class);
            return [$class, $symbol];
        }

        private function compileTokenConstants() : array {
            $constants = [];
            foreach ($this->lexer->config as $name => $config) {
                if ($config['added']) {
                    continue;
                }

                $constants[] = \sprintf(
                    "\tconst int %s = %d;",
                    $name,
                    $config['const']);
            }
            return $constants;
        }

        private function compileTokenStrings() : array {
            $strings = [];
            foreach ($this->lexer->config as $name => $config) {
                if ($config['added']) {
                    continue;
                }

                $strings[] = \sprintf(
                    "\t\t\tcase %d: return \"%s\";",
                    $config['const'], $name);
            }
            return $strings;
        }

        public function generate() : array {
            $token = $this->compileToken();
            return [
                $token,
                $this->compileContext($token)
            ];
        }
    }
}
?>