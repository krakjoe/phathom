<?php declare(strict_types=1);

namespace pharos\phathom\Earley {
    use \pharos\phathom\File;
    use \pharos\phathom\Buffer;
    use \pharos\phathom\Lexer;
    use \pharos\phathom\Grammar;

    final class Chart {
        private array $index   = [];
        private array $waiting = [];
        private int   $position = 0;

        public private(set) string $start;
        public private(set) array  $path    = [[]];
        public private(set) array  $tokens  = [];
        public private(set) int    $limit   = 0;

        public function __construct(Grammar $grammar, File|Buffer $input) {
            $scan =
                $grammar->lexer
                    ->scan(...);
            $class = $grammar->token;
            $rules = $grammar->rules;

            foreach ($rules[$this->start = $grammar->start]
                        as $aid => $alternative) {
                $this->add(0, new Item(
                    rule:        $this->start,
                    alt:         $aid,
                    dot:         0,
                    origin:      0,
                    backs:       [],
                    alternative: $alternative));
            }

            for ($i = 0; isset($this->path[$i]); $i++) {
                $j        = 0;
                $expected = [];

                while ($j < \count($this->path[$i])) {
                    $item   = $this->path[$i][$j++];
                    $dotted =
                        $item->alternative
                            ->symbols[$item->dot] ?? null;

                    if ($dotted === null) {
                        /* Complete */
                        foreach ($this->waiting[$item->origin]
                                    [$item->rule] ?? [] as $waiting) {
                            $this->add($i, new Item(
                                rule:        $waiting->rule,
                                alt:         $waiting->alt,
                                dot:         $waiting->dot + 1,
                                origin:      $waiting->origin,
                                backs:       [new Back(
                                    prev:  $waiting,
                                    child: $item,
                                    token: null)],
                                alternative: $waiting->alternative));
                        }
                    } elseif (($scanning = $dotted->terminal) !== false) {
                        /* Scan — collect expected terminals */
                        $expected[$scanning] = true;
                    } else {
                        /* Predict */
                        foreach ($rules[$dotted->name] 
                                    as $aid => $alternative) {
                            $this->add($i, new Item(
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
                    $scan(
                        $input, $this->position,
                        $expected, $class);

                if ($token === null) {
                    break;
                }

                $ti =
                    \count($this->tokens);
                $this->tokens[] = $token;

                $this->path[] = [];
                foreach ($this->path[$i] as $item) {
                    $dotted = $item->alternative
                        ->symbols[$item->dot] ?? null;
                    if ($dotted === null ||
                        $dotted->terminal !== $token->type) {
                        continue;
                    }

                    $this->add($i + 1, new Item(
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

            $scan(
                $input, $this->position,
                [], $class);

            $this->limit = \count($this->tokens);
        }

        private function add(int $pos, Item $item) : void {
            $slot =
                &$this->index[$pos]
                    [$item->rule]
                    [$item->alt]
                    [$item->dot]
                    [$item->origin];

            if (isset($slot)) {
                if (empty($item->backs)) {
                    return;
                }
                foreach ($item->backs as $back) {
                    $slot->backs[] = $back;
                }
                return;
            }

            $item->pos   = $pos;
            $this->path
                [$pos][] =
                    ($slot = $item);

            $dotted = $item
                ->alternative
                ->symbols[
                    $item->dot] ?? null;

            if ($dotted === null) {
                return;
            }

            $this->waiting[$pos]
                [$dotted->name][] = $item;
        }
    }
}
?>