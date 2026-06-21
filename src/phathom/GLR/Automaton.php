<?php declare(strict_types=1);

namespace pharos\phathom\GLR {
    use \pharos\phathom\Grammar;
    use \pharos\phathom\Grammar\Symbol;
    use \pharos\phathom\Grammar\Alternative;
    use \pharos\phathom\Grammar\Interface;

    /*
    * GLR LR(0) automaton — built AOT, retained and serialised for use at
    * parse time.
    *
    * Standard LR(0) item-set closure from an augmented initial item
    * (RESERVE → • start_rule), producing shift/reduce/goto tables consumed
    * by GLR\Chart.  Unlike Earley\Automaton these tables are required at
    * parse time because GLR is table-driven, so the automaton is included
    * in the serialised grammar and restored on __unserialize.
    *
    * expected[] (terminal sets for lexer prewarming) is NOT serialised —
    * it travels with the lexer pattern cache via Grammar\Optimize\Lexer,
    * identical to the Earley case.
    *
    * State identity uses a trie over sorted integer item-IDs to avoid
    * repeated string comparisons during item-set interning.
    */
    final class Automaton implements Interface\Automaton {
        public  const int    INITIAL = 0;
        private const string RESERVE = "\$Automaton\$";

        public private(set) int   $accept;   /* state_id of the accept state      */
        public private(set) array $reduces;  /* [state_id] => Reduction[]         */
        public private(set) array $shifts;   /* [state_id][terminal_id] => int    */
        public private(set) array $goto;     /* [state_id][rule_name] => int      */

        private string $start;         /* start rule from grammar */
        private array  $rules    = []; /* rules from grammar */
        private array  $slots    = []; /* trie over sorted integer item-IDs => state_id */
        private array  $items    = []; /* string item-key => int ID (interning table)   */
        private array  $states   = []; /* state_id => Item[]                            */
        private array  $expected = []; /* [[terminal_id => true], …]   */

        public function __construct(Grammar $grammar) {
            $initial =
                $this->start($grammar);

            $slot =
                &$this->slot(
                    $initial);
            $slot[
                Automaton::RESERVE] =
                    Automaton::INITIAL;

            $this->states  = [$initial];

            $worklist = [
                Automaton::INITIAL];

            do {
                $state =
                    \array_shift($worklist);
                $items = $this->states[$state];

                [
                    $terminals,
                    $nonterminals,
                ] = $this->reduce(
                        $state, $items);

                $this->shift($state, 
                    $terminals, $items,
                    $worklist);

                $this->goto($state,
                    $nonterminals, $items,
                    $worklist
                );
            } while (!empty($worklist));

            $this->accept =
                $this->goto[
                    Automaton::INITIAL]
                        [$this->start] ?? -1;

            unset($this->start);
            unset($this->rules);
            unset($this->slots);
            unset($this->items);
            unset($this->states);
        }

        private function start(Grammar $grammar) : array {
            $this->start = $grammar->start;
            $this->rules = $grammar->rules;

            $augmented = new Item(
                rule:   Automaton::RESERVE, 
                alt:    0, 
                dot:    0, 
                length: 1,
                alternative:
                    Alternative::simple(
                        $grammar->file,
                        new Symbol(
                            type: 0,
                            name: $this->start)));
            return $this->collect([
                $augmented->key => $augmented
            ], $this->rules);
        }

        private function reduce(int $state, array $items) : array {
            $terminals    = [];
            $nonterminals = [];

            foreach ($items as $item) {
                $dotted =
                    $item->alternative
                        ->symbols[$item->dot] ?? null;

                if ($dotted === null) {
                    /* Complete item.  Skip the augmented rule — its
                        "reduction" is the accept action. */
                    if ($item->rule !== Automaton::RESERVE) {
                        $this->reduces[$state][] = new Reduction(
                            rule:        $item->rule,
                            alt:         $item->alt,
                            length:      $item->length,
                            alternative: $item->alternative);
                    }
                } elseif ($dotted->terminal !== false) {
                    $terminals[$dotted->terminal] = true;
                } else {
                    $nonterminals[$dotted->name] = true;
                }
            }

            if (\count($terminals)) {
                $this->expected[] = $terminals;
            }

            return [$terminals, $nonterminals];
        }

        private function shift(
            int $state, array $terminals, array $items, array &$worklist) : void {
            foreach ($terminals as $terminal => $_) {
                $kernel = [];
                foreach ($items as $item) {
                    $dotted =
                        $item->alternative
                            ->symbols[$item->dot] ?? null;

                    if ($dotted === null ||
                        $dotted->terminal !== $terminal) {
                        continue;
                    }

                    $new = new Item(
                        rule:        $item->rule,
                        alt:         $item->alt,
                        dot:         $item->dot + 1,
                        length:      $item->length,
                        alternative: $item->alternative);

                    $kernel[$new->key] = $new;
                }

                $closed = $this->collect($kernel);
                $slot = &$this->slot($closed);

                if (!isset($slot[Automaton::RESERVE])) {
                    $next = \count($this->states);
                    $slot[
                        Automaton::RESERVE] = $next;
                    $this->states[
                        $next] = $closed;
                    $worklist[] = $next;
                }

                $this->shifts[$state][$terminal] =
                    $slot[Automaton::RESERVE];
            }
        }

        private function goto(
            int $state, array $nonterminals, array $items, array &$worklist) : void {
            foreach ($nonterminals as $name => $_) {
                $kernel = [];
                foreach ($items as $item) {
                    $dotted =
                        $item->alternative
                            ->symbols[$item->dot] ?? null;

                    if ($dotted === null ||
                        $dotted->terminal !== false ||
                        $dotted->name !== $name) {
                        continue;
                    }

                    $new = new Item(
                        rule:        $item->rule,
                        alt:         $item->alt,
                        dot:         $item->dot + 1,
                        length:      $item->length,
                        alternative: $item->alternative);
                    $kernel[$new->key] = $new;
                }

                $closed = $this->collect($kernel);
                $slot = &$this->slot($closed);

                if (!isset($slot[Automaton::RESERVE])) {
                    $next = \count($this->states);
                    $slot[
                        Automaton::RESERVE] = $next;
                    $this->states[
                        $next] = $closed;
                    $worklist[] = $next;
                }

                $this->goto[$state][$name] =
                    $slot[Automaton::RESERVE];
            }
        }

        private function collect(array $items) : array {
            $worklist = \array_values($items);

            while ($item = \array_shift($worklist)) {
                $dotted =
                    $item->alternative
                        ->symbols[$item->dot] ?? null;

                if ($dotted === null ||
                    $dotted->terminal !== false) {
                    continue;
                }

                foreach ($this->rules[$dotted->name] ?? [] as $aid => $alt) {
                    $new = new Item(
                        rule:        $dotted->name, 
                        alt:         $aid, 
                        dot:         0, 
                        length:      \count($alt->symbols),
                        alternative: $alt);

                    if (!isset($items[$new->key])) {
                        $items[
                            $new->key] = $new;
                        $worklist[]  = $new;
                    }
                }
            }

            return $items;
        }

        private function &slot(array $items) : mixed {
            $ids = [];
            foreach (\array_keys($items) as $k) {
                if (!isset($this->items[$k])) {
                    $this->items[$k] =
                        \count($this->items);
                }
                $ids[] = $this->items[$k];
            }
            \sort($ids);
            $slot = &$this->slots;
            foreach ($ids as $id) {
                $slot = &$slot[$id];
            }
            return $slot;
        }

        public function expected() : array {
            return $this->expected;
        }

        public function __serialize() : array {
            return [
                'accept'  => $this->accept,
                'reduces' => $this->reduces,
                'shifts'  => $this->shifts,
                'goto'    => $this->goto, 
            ];
        }

        public function __unserialize(array $array) : void {
            foreach ($array as $member => $value) {
                $this->$member = $value;
            }

            unset($this->rules);
            unset($this->slots);
            unset($this->items);
            unset($this->states);
        }
    }
}
?>
