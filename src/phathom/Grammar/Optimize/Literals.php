<?php declare(strict_types=1);

namespace pharos\phathom\Grammar\Optimize {
    final class Literals extends \pharos\phathom\Grammar\Optimization {
        public function pass(bool $generated) : bool {
            if ($generated === false) {
                /* not ready (no concrete symbols) */
                return false;
            }

            foreach ($this->lexer->config as $name => $config) {
                if ($config['literal'] === false) {
                    continue;
                }

                $this->literals[$config['const']] =
                    new $this->symbols['token'](
                        $config['const'],
                        [
                            'path'     => $config['file'],
                            'position' => 0,
                        ],
                    );
            }

            /* commit literals to Grammar */
            return true;
        }
    }
}