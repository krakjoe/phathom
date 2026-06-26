<?php declare(strict_types=1);
/*
  +----------------------------------------------------------------------+
  | phathom                                                              |
  +----------------------------------------------------------------------+
  | Copyright (c) Joe Watkins 2026                                       |
  +----------------------------------------------------------------------+
  | This source file is subject to the BSD 3-Clause License bundled     |
  | with this package in the file LICENSE.                               |
  +----------------------------------------------------------------------+
  | Author: krakjoe                                                      |
  +----------------------------------------------------------------------+
 */

namespace pharos\phathom\GLR {
    use \pharos\phathom\Context;
    use \pharos\phathom\Exception;
    use \pharos\phathom\Grammar\Quantifier;
    use \pharos\phathom\Grammar\Frame;

    final class Evaluator {
        private string  $start;
        private array   $tokens;
        private int     $limit;
        private array   $threads;
        private array   $actions = [[]];
        private Context $context;

        public function __construct(Chart $chart, Context $context) {
            $this->start   = $chart->start;
            $this->tokens  = $chart->tokens;
            $this->limit   = $chart->limit;
            $this->threads = $chart->threads;
            $this->context = $context;
        }

        private function apply(Node $node, array $values) : mixed {
            $alt = $node->alternative;

            if ($alt->action !== null) {
                $action =
                    $this->actions
                        [$node->rule]
                        [$node->alt] ?? null;
                if ($action === null) {
                    $method =
                        \sprintf(
                            '__action_%s_%d__',
                            $node->rule,
                            $node->alt);
                    $action =
                        $this->actions
                            [$node->rule]
                            [$node->alt] =
                                $this->context
                                    ->$method(...);
                }
                return $action(...$values);
            }

            return match ($alt->synthetic) {
                Quantifier::STAR,
                Quantifier::PLUS     =>
                    \count($alt->symbols) === 2
                        ? [...$values[0], $values[1]]
                        : [$values[0]],
                Quantifier::OPTIONAL => $values[0],
                default              =>
                    \count($values) === 1
                        ? $values[0]
                        : $values,
            };
        }

        private function setup() : array {
            return [
                function(mixed $node, array $tokens) : array {
                    $limit   = \count($node->children);
                    $partial = \array_fill(0, $limit, null);
                    $slots   = [];
                    $nodes   = [];

                    for ($pos = $limit - 1; $pos >= 0; $pos--) {
                        $child = $node->children[$pos];
                        if ($child instanceof Node) {
                            $slots[] = $pos;
                            $nodes[] = $child;
                        } else {
                            $partial[$pos] = $tokens[$child];
                        }
                    }

                    return [$partial, $slots, $nodes];
                },
                $this->apply(...),
            ];
        }

        public function execute(Node $node) : mixed {
            [
                $children,
                $apply
            ] = $this->setup();

            $stack = [Frame::select($node)];
            $heap  = [];

            while (($frame = \array_pop($stack))) {
                $frame(
                    $children,
                    $apply,
                    $stack,
                    $heap,
                    $this->tokens);
            }

            return \array_pop($heap);
        }

        public function __invoke() : mixed {
            $winner = null;

            foreach ($this->threads as $thread) {
                $nodes = $thread->nodes;
                $node  = $nodes[\count($nodes) - 1];

                if ($winner === null) {
                    $winner = $node;
                    continue;
                }

                throw Exception\Ambiguity::range(
                    $this->context,
                    $this->start,
                    $this->tokens,
                    0,
                    $this->limit - 1);
            }

            if ($winner === null) {
                throw Exception\Execute::nomatch(
                    $this->context,
                    $this->start,
                    $this->tokens);
            }

            return $this->execute($winner);
        }
    }
}
?>
