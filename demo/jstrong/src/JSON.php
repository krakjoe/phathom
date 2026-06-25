<?php declare(strict_types=1);

namespace pharos\phathom\demo\jstrong;

class JSON extends \pharos\phathom\Context
{
    protected function parse(array|object $root): array|object
    {
        return $root;
    }

    protected function array(array $entries): array
    {
        $result = [];
        $next   = 0;
        foreach ($entries as [$idx, $val]) {
            if ($idx === null) {
                while (array_key_exists($next, $result)) {
                    $next++;
                }
                $result[$next++] = $val;
            } else {
                $result[$idx] = $val;
                $next = $idx + 1;
            }
        }
        return $result;
    }
}
