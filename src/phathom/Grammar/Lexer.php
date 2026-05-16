<?php
namespace pharos\phathom\Grammar {
    final class Lexer {

        private string|false $buffer;
        private int          $length   = 0;
        private int          $position = 0;

        private bool         $listing  = false;
        private int          $listed   = 0;

        public function __construct(string $file) {
            if (!\file_exists($file)) {
                throw new \Exception("$file does not exist");
            }

            $this->buffer = @\file_get_contents($file);

            if ($this->buffer !== false) {
                $this->length =
                    \strlen($this->buffer);
            }
        }

        private function comment() : array {
            $content = '';
            $start = $this->position++;

            while ($this->position < $this->length) {
                switch ($this->buffer[$this->position]) {
                    case "\n":
                        $this->position++;
                    break 2;

                    default:
                        $content .=
                            $this->buffer[
                                $this->position++];
                }
            }

            return [\trim($content), $start];
        }

        private function balance(
                    string $open,
                    string $close): array {
            $depth   = 1;
            $content = '';
            $start   =
                $this->position++;

            while ($this->position < $this->length && $depth > 0) {
                if ($this->buffer[$this->position] === '\\') {
                    /* escape */
                    if ((($this->position + 1) < $this->length)) {
                        if ($this->buffer[$this->position + 1] === $open ||
                            $this->buffer[$this->position + 1] === $close ||
                            $this->buffer[$this->position + 1] === '\\') {
                            $content .=
                                $this->buffer[
                                    $this->position + 1];
                            $this->position += 2;
                        } else {
                            $content .=
                                $this->buffer[
                                    $this->position++];                        
                        }
                    }
                    continue;
                }

                if ($this->buffer[$this->position] === $open)  $depth++;
                if ($this->buffer[$this->position] === $close) $depth--;

                if ($depth > 0) {
                    $content .=
                        $this->buffer[
                            $this->position];
                }

                $this->position++;
            }

            if ($depth !== 0) {
                throw new \Exception(
                    "Unmatched $open in \"$content\", ".
                    "missing $close");
            }

            return [\trim($content), $start];
        }

        private function string(string $delimiter): array {
            $content = '';
            $start   =
                $this->position++;
            $terminated = false;

            while ($this->position < $this->length) {
                if ($this->buffer[$this->position] === '\\') {
                    /* escape */
                    if ((($this->position + 1) < $this->length)) {
                        if ($this->buffer[$this->position + 1] != $delimiter) {
                            $content .=
                                $this->buffer[
                                    $this->position++];
                        } else {
                            $this->position++;
                        }
                    }

                    if ($this->position < $this->length) {
                        $content .=
                            $this->buffer[
                                $this->position++];
                    }

                    continue;
                }

                if ($this->buffer[
                        $this->position] === $delimiter) {
                    $this->position++;

                    $terminated = true;
                    break;
                }

                $content .= $this->buffer[$this->position++];
            }

            if (!$terminated) {
                throw new \Exception(
                    "Unterminated STRING, expected $delimiter");
            }

            return [\trim($content), $start];
        }

        public function scan() : bool {
            if ($this->position >= $this->length) {
                return false;
            }

            if (!\ctype_print($this->buffer[$this->position]) ||
                 \ctype_space($this->buffer[$this->position])) {
                return false;
            }

            switch ($this->buffer[$this->position]) {
                case '#':
                case ':':
                case '|':
                case '<':
                case '>':
                case '(':
                case ')':
                case '{':
                case '}':
                case '+':
                case '*':
                case '?':
                    return false;
            }

            return true;
        }

        public function tokenize(): array {
            $tokens   = [];

            while ($this->position < $this->length) {
                if (\ctype_space(
                        $this->buffer[
                            $this->position])) {
                    $this->position++;
                    continue;
                }

                switch ($this->buffer[$this->position]) {
                    case '#':
                        [$content, $start] =
                            $this->comment();
                        /* drop */
                    break;

                    case '(':
                        if ($this->listing) {
                            throw new \Exception(
                                "Unexpected LIST_START, ".
                                "EXPRESSION may only contain IDENT or PATTERN");
                        }

                        if (\count($tokens) < 1) {
                            throw new \Exception(
                                "Unexpected LIST_START, ".
                                "LIST_START must follow COLON, ".
                                "not enough tokens");
                        }

                        $previous = $tokens[\count($tokens) - 1];

                        if ($previous['type'] !== 'COLON' &&
                            $previous['type'] !== 'PIPE') {
                            throw new \Exception(
                                "Unexpected LIST_START, ".
                                "LIST_START must follow COLON or PIPE, ".
                                "got {$previous['type']}");
                        }

                        $tokens[]      = [
                            'type'     => 'LIST_START',
                            'position' => $this->position,
                        ];

                        $this->position++;
                        $this->listing = true;
                        $this->listed  = 0;
                    break;

                    case ')':
                        if (\count($tokens) < 1) {
                            throw new \Exception(
                                "Unexpected LIST_END, ".
                                "LIST_END must follow IDENT or PATTERN, ".
                                "not enough tokens");
                        }

                        if ($this->listed === 0) {
                            throw new \Exception(
                                "Unexpected LIST_END, ".
                                "LIST_END must follow IDENT or PATTERN, ".
                                "none listed");
                        }

                        $tokens[]      = [
                            'type'     => 'LIST_END',
                            'position' => $this->position,
                        ];
                        $this->position++;
                        $this->listing = false;
                        $this->listed  = 0;
                    break;

                    case ':':
                        if (\count($tokens) < 1) {
                            throw new \Exception(
                                "Unexpected COLON, ".
                                "COLON must follow IDENT, ".
                                "not enough tokens");
                        }

                        $ident = $tokens[\count($tokens) - 1];

                        if ($ident['type'] !== 'IDENT') {
                            throw new \Exception(
                                "Unexpected COLON, ".
                                "COLON must follow IDENT, ".
                                "got {$ident['type']}");
                        }

                        if ($this->listing) {
                            throw new \Exception(
                                "Unexpected COLON, ".
                                "EXPRESSION may only contain IDENT or PATTERN");
                        }

                        $tokens[]      = [
                            'type'     => 'COLON',
                            'position' => $this->position,
                        ];
                        $this->position++;
                        break;

                    case '|':
                        if (\count($tokens) < 1) {
                            throw new \Exception(
                                "Unexpected PIPE, ".
                                "PIPE must follow ".
                                    "IDENT, PATTERN, QUANTIFIER, LIST_END, or ACTION, ".
                                "not enough tokens");
                        }

                        $previous = $tokens[\count($tokens) - 1];

                        if ($previous['type'] !== 'IDENT' &&
                            $previous['type'] !== 'PATTERN' &&
                            $previous['type'] !== 'QUANTIFIER' &&
                            $previous['type'] !== 'ACTION' &&
                            $previous['type'] !== 'LIST_END') {
                            throw new \Exception(
                                "Unexpected PIPE, ".
                                "PIPE must follow ".
                                    "IDENT, PATTERN, QUANTIFIER, LIST_END, or ACTION, ".
                                "got {$previous['type']}");
                        }

                        if ($this->listing) {
                            throw new \Exception(
                                "Unexpected PIPE, EXPRESSION may only contain IDENT or PATTERN");
                        }

                        $tokens[]      = [
                            'type'     => 'PIPE',
                            'position' => $this->position,
                        ];
                        $this->position++;
                        break;

                    case '<':
                        [$content, $start] =
                            $this->balance(
                                '<', '>');
                        $tokens[]      = [
                            'type'     => 'PATTERN',
                            'value'    => $content,
                            'position' => $start,
                        ];

                        if ($this->listing) {
                            $this->listed++;
                        }

                        break;

                    case '{':
                        if (\count($tokens) < 1) {
                            throw new \Exception(
                                "Unexpected ACTION, ".
                                "ACTION must follow LIST_END, ".
                                "not enough tokens");
                        }

                        $list = $tokens[\count($tokens) - 1];

                        if ($list['type'] !== 'LIST_END') {
                            throw new \Exception(
                                "Unexpected ACTION, ".
                                "ACTION must follow LIST_END, ".
                                "got {$list['type']}");
                        }

                        [$content, $start] =
                            $this->balance(
                                '{', '}');
                        $tokens[]      = [
                            'type'     => 'ACTION',
                            'value'    => $content,
                            'position' => $start,
                        ];
                        break;

                    case '\'':
                    case '"':
                        [$content, $start] =
                            $this->string(
                                $this->buffer[$this->position]);

                        if (($limit = \count($tokens)) < 2) {
                            throw new \Exception(
                                "Unexpected STRING, ".
                                "STRING must follow IDENT COLON, ".
                                "not enough tokens");
                        }

                        $colon     =  $tokens[$limit - 1];
                        $directive = &$tokens[$limit - 2];

                        if ($colon['type'] !== 'COLON') {
                            throw new \Exception(
                                "Unexpected STRING, ".
                                "STRING must follow IDENT COLON, ".
                                "got {$directive['type']} {$colon['type']}");
                        }

                        $directive['type'] = 'DIRECTIVE';
                        $directive['value'] =
                            \strtolower(
                                $directive['value']);

                        $tokens[]      = [
                            'type'     => 'STRING',
                            'value'    => $content,
                            'position' => $start,
                        ];
                        break;

                    case '+':
                    case '*':
                    case '?':
                        if (\count($tokens) < 1) {
                            throw new \Exception(
                                "Unexpected QUANTIFIER, ".
                                "QUANTIFIER must follow IDENT or PATTERN, ".
                                "not enough tokens");
                        }

                        $quantifying =
                            $tokens[\count($tokens) - 1];

                        if ($quantifying['type'] !== 'IDENT' &&
                            $quantifying['type'] !== 'PATTERN') {
                            throw new \Exception(
                                "Unexpected QUANTIFIER, ".
                                "QUANTIFIER must follow IDENT or PATTERN, ".
                                "got {$quantifying['type']}");
                        }

                        $tokens[]      = [
                            'type'     => 'QUANTIFIER',
                            'value'    => 
                                $this->buffer[
                                    $this->position],
                            'position' => $this->position++,
                        ];
                    break;

                    default:
                        if ($this->scan()) {
                            $ident = '';
                            $start =
                                $this->position;
                            while ($this->scan()) {
                                $ident .=
                                    $this->buffer[
                                        $this->position++];
                            }

                            $tokens[]      = [
                                'type'     => 'IDENT',
                                'value'    => $ident,
                                'position' => $start,
                            ];
                            
                            if ($this->listing) {
                                $this->listed++;
                            }
                        } else {
                            throw new \Exception(
                                "Unexpected {$this->buffer[$this->position]}, ".
                                "expected IDENT");
                        }
                }
            }

            if ($this->listing) {
                throw new \Exception(
                    "Unterminated LIST_START, expected )");
            }

            if (\count($tokens)) {
                $dangling = $tokens[
                    \count($tokens) - 1];

                if ($dangling['type'] === 'COLON') {
                    throw new \Exception(
                        "Unexpected EOF, ".
                        "COLON must be followed by ".
                            "IDENT, PATTERN, STRING, or LIST_START");
                }

                if ($dangling['type'] === 'PIPE') {
                    throw new \Exception(
                        "Unexpected EOF, ".
                        "PIPE must be followed by ".
                            "IDENT, PATTERN, or LIST_START");
                }

                if ($dangling['type'] !== 'EOF') {
                    $tokens[]      = [
                        'type'     => 'EOF',
                        'position' => $this->position
                    ];
                }
            } else {
                $tokens[]      = [
                    'type'     => 'EOF',
                    'position' => $this->position
                ];
            }

            return $tokens;
        }
    }
}
?>