<?php declare(strict_types=1);

namespace pharos\phathom\demo\jstrong;

final class Reference
{
    /** @param list<array{0:'prop'|'idx', 1:string|int}> $path */
    public function __construct(
        public readonly array $path,
    ) {}
}
