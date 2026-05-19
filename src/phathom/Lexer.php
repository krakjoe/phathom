<?php

namespace pharos\phathom
{
    final class Lexer
    {
        private string     $path;
        private array|bool $config;

        public function __construct(string $grammar, string $config) {
            $this->path = 
                \sprintf(
                    "%s%s%s",
                    \dirname($grammar),
                    \DIRECTORY_SEPARATOR,
                    $config);

            if (!\file_exists($this->path)) {
                throw new \Exception(
                    "$this->path does not exist");
            }

            $this->config =
                @\parse_ini_file(
                    $this->path, true);

            if ($this->config === false) {
                throw new \Exception(
                    "$this->path does not contain valid configuration (ini syntax)");
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
            $buffer   = $file->getBuffer();
            $path     = $file->getPath();
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
                            'path'     => $path,
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