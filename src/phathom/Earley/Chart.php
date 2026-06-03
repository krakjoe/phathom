<?php
namespace pharos\phathom\Earley {
    use \pharos\phathom\Lexer;
    use \pharos\phathom\File;
    use \pharos\phathom\Buffer;

    final class Chart {
        public function __construct(
            private File|Buffer $input,
            private Lexer       $lexer,
            private array       $rules,
            private string      $start,
            private string      $class) {}

        public function build(): array {
            $index    = [];
            $waiting  = [];
            $tokens   = [];
            $chart    = [[]];

            $add = function (int $pos, Item $item) use (&$index, &$chart, &$waiting): void {
                $slot = &$index[$pos][$item->rule][$item->alt][$item->dot][$item->origin];

                if (isset($slot)) {
                    if (empty($item->backs)) {
                        return;
                    }
                    foreach ($item->backs as $back) {
                        $slot->backs[] = $back;
                    }
                    return;
                }

                $item->pos     = $pos;
                $slot          = $item;
                $chart[$pos][] = $item;

                if (!isset($item->alternative->symbols[$item->dot])) {
                    return;
                }

                $dotted = $item
                    ->alternative
                    ->symbols[
                        $item->dot];

                $waiting[$pos][$dotted->name][] = $item;
            };

            foreach ($this->rules[$this->start] as $aid => $alternative) {
                $add(0, new Item(
                    rule:        $this->start,
                    alt:         $aid,
                    dot:         0,
                    origin:      0,
                    backs:       [],
                    alternative: $alternative));
            }

            $position = 0;

            for ($i = 0; isset($chart[$i]); $i++) {
                $j        = 0;
                $expected = [];

                while ($j < \count($chart[$i])) {
                    $item   = $chart[$i][$j++];
                    $alt    = $item->alternative;
                    $dotted = $alt->symbols[$item->dot] ?? null;

                    if ($dotted === null) {
                        /* Complete */
                        foreach ($waiting[$item->origin][$item->rule] ?? [] as $prev) {
                            $palt = $prev->alternative;
                            $add($i, new Item(
                                rule:        $prev->rule,
                                alt:         $prev->alt,
                                dot:         $prev->dot + 1,
                                origin:      $prev->origin,
                                backs:       [new Back(
                                    prev:  $prev,
                                    child: $item,
                                    token: null)],
                                alternative: $palt));
                        }
                    } elseif (($scanning = $dotted->terminal) !== false) {
                        /* Scan — collect expected terminals */
                        $expected[$scanning] = true;
                    } else {
                        /* Predict */
                        foreach ($this->rules[$dotted->name] as $aid => $alternative) {
                            $add($i, new Item(
                                rule:        $dotted->name,
                                alt:         $aid,
                                dot:         0,
                                origin:      $i,
                                backs:       [],
                                alternative: $alternative));
                        }
                    }
                }

                if (!$expected) {
                    break;
                }

                $token =
                    $this->lexer->scan(
                        $this->input, $position,
                        $expected, $this->class);

                if ($token === null) {
                    break;
                }

                $ti      = \count($tokens);
                $tokens[] = $token;
                $chart[]  = [];

                foreach ($chart[$i] as $item) {
                    $dotted = $item->alternative->symbols[$item->dot] ?? null;
                    if ($dotted === null || $dotted->terminal !== $token->type) {
                        continue;
                    }
                    $add($i + 1, new Item(
                        rule:        $item->rule,
                        alt:         $item->alt,
                        dot:         $item->dot + 1,
                        origin:      $item->origin,
                        backs:       [new Back(
                            prev:  $item,
                            child: null,
                            token: $ti)],
                        alternative: $item->alternative));
                }
            }

            $this->lexer->scan(
                $this->input, $position,
                [], $this->class);

            return [$chart, $tokens, \count($tokens)];
        }
    }
}

?>