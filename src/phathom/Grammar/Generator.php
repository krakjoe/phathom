<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    use \pharos\phathom\File;
    use \pharos\phathom\Grammar;
    use \pharos\phathom\Assets;
    use \pharos\phathom\Lexer;
    use \pharos\phathom\Grammar\Quantifier;

    use \pharos\phathom\Exception;
    use \pharos\phathom\Exception\IO as IOException;
    use \pharos\phathom\Exception\Undefined;

    final class Generator {
        public function __construct(
            private ?Assets $assets,
            private array   $abstracts,
            private Lexer   $lexer,
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
                        if ($alternative->action) {
                            $result[] = \sprintf(
                                "\tpublic function __action_%s_%d__(%s) {\n".
                                        "\t\t%s\n".
                                "\t}",
                                $name,
                                $index,
                                ... $this->compileContextMethod(
                                        $name,
                                        $index,
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
                \hash('sha256',
                    \serialize([
                        $this->abstracts['context'],
                        $this->rules])));
            $symbol = \sprintf(
                "\pharos\phathom\assets\Context\%s", $class);
            return [$class, $symbol];
        }

        private function compileContextMethodParameters(Alternative $alternative, array &$variables) : array {
            $parameters = [];
            foreach ($alternative->symbols as $index => $symbol) {
                $parameter =
                    \sprintf(
                        '$__sym%d__', $index + 1);
                $variables[$index + 1] = $parameter;
                if (!isset($this->rules[$symbol->name])) {
                    $type = "Token";
                } elseif ($this->rules[$symbol->name][0]->synthetic !== Quantifier::NONE) {
                    $type = 'array';
                } else {
                    $type = 'mixed';
                }
                $parameters[] = \sprintf("%s %s", $type, $parameter);
            }
            return $parameters;
        }

        private function compileContextMethod(string $rule, int $index, Alternative $alternative) : array {
            $variables  = [];
            $parameters = $this->compileContextMethodParameters($alternative, $variables);

            for ($method = '',
                 $position = 1,
                 $tokens =
                    \PHPToken::tokenize(
                        \sprintf(
                            "<?php %s", $alternative->action)),
                $limit = \count($tokens);
                $token = $tokens[$position]     ?? null,
                $next  = $tokens[$position + 1] ?? null,
                $position < $limit;
                $position++) {

                if ($token->is('$') && $next?->is(\T_LNUMBER)) {
                    $variable = 
                        (int) $next->text;

                    if (!isset($variables[$variable])) {
                        throw Undefined::variable(
                            $alternative->file,
                            $rule, $index, $variable);
                    }

                    $method .=
                        $variables[$variable];
                    $position++;
                    continue;
                }

                $method .= $token->text;
            }

            return [implode(", ", $parameters), $method];
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
                \hash('sha256',
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