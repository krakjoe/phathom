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

namespace pharos\phathom\Grammar {
    enum Collector {
        case DEFAULT;
        case OFF;
        case DEFER;
        case RESTORE;
        case COLLECT;
        case UNKNOWN;

        const array policies = [
            'off', 'defer', 'default',
        ];

        public static function from(string $string) : Collector {
            return match($string) {
                'off'     => Collector::OFF,
                'defer'   => Collector::DEFER,
                'default' => Collector::DEFAULT,
                default   => Collector::UNKNOWN,
            };
        }

        public static function apply(Collector $policy) : Collector {
            if ($policy === Collector::DEFAULT) {
                return Collector::DEFAULT;
            }

            if (!\gc_enabled()) {
                return Collector::DEFAULT;
            }

            \gc_disable();

            return match($policy) {
                Collector::OFF   => Collector::RESTORE,
                Collector::DEFER => Collector::COLLECT,
                default          => Collector::DEFAULT,
            };
        }

        public static function restore(Collector $restoration) : void {
            if ($restoration === Collector::DEFAULT) {
                return;
            }
            \gc_enable();
            if ($restoration == Collector::COLLECT) {
                \gc_collect_cycles();
            }
        }
    }
}
?>