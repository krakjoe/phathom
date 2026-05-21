<?php

namespace pharos\phathom
{
    final class Lexer
    {
        private array|bool $config;

        public function __construct(File $file) {
            $this->config =
                @\parse_ini_string(
                    $file->contents(), true);

            if ($this->config === false) {
                throw new \Exception(
                    "$file does not contain valid configuration (ini syntax)");
            }
        }

        public function known(string $token): bool {
            return isset($this->config[$token]);
        }

        public function add(array $patterns): void {
            foreach ($patterns as $pattern) {
                $this->config[$pattern] = [
                    'pattern' => 
                    \sprintf(
                        "/%s/",
                        $pattern)
                ];
            }
        }

        public function tokenize(File $file): array {
            $tokens   = [];
            $buffer   = $file->contents();
            $position = 0;
            $limit    = \strlen($buffer);

            $skip    = \array_filter(
                $this->config,
                fn($c) => isset($c['skip']) && $c['skip']);
            $consume = \array_filter(
                $this->config,
                fn($c) => !(isset($c['skip']) && $c['skip']));

            while ($position < $limit) {
                $advanced = true;
                while ($advanced && $position < $limit) {
                    $advanced = false;
                    foreach ($skip as $config) {
                        if (!\preg_match($config['pattern'], $buffer, $matches, \PREG_OFFSET_CAPTURE, $position)) {
                            continue;
                        }
                        if ($matches[0][1] !== $position) {
                            continue;
                        }
                        $position += \strlen($matches[0][0]);
                        $advanced  = true;
                        break;
                    }
                }

                $best   = null;
                $length = 0;
                $type   = null;

                foreach ($consume as $name => $config) {
                    if (!\preg_match($config['pattern'], $buffer, $matches, \PREG_OFFSET_CAPTURE, $position)) {
                        continue;
                    }
                    if ($matches[0][1] !== $position) {
                        continue;
                    }
                    $chunk = \strlen($matches[0][0]);
                    if ($chunk > $length) {
                        $length = $chunk;
                        $best   = $matches[0][0];
                        $type   = $name;
                    }
                }

                if ($best !== null) {
                    $tokens[]      = [
                        'type'     => $type,
                        'value'    => $best,
                        'location'     => [
                            'path'     => $file->path,
                            'position' => $position,
                        ],
                    ];
                    $position += $length;
                } else {
                    $position++;
                }
            }

            return $tokens;
        }
    }
}