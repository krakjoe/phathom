<?php declare(strict_types=1);

namespace pharos\phathom\demo\jstrong;

class Strong extends \pharos\phathom\demo\jstrong\JSON
{
    protected function parse(array|object $root): array|object
    {
        $this->patch($root, $root);
        return $root;
    }

    protected function typed(string $type, array $props): object
    {
        $rc  = new \ReflectionClass($type);
        $obj = $rc->newInstanceWithoutConstructor();

        foreach ($props as $name => $value) {
            $prop = $rc->getProperty($name);
            $prop->setValue($obj, $value);
        }

        return $obj;
    }

    protected function reference(array $path): Reference
    {
        return new Reference($path);
    }

    private function patch(mixed &$node, array|object $root): void
    {
        if ($node instanceof Reference) {
            $node = $this->lookup($root, $node);
            return;
        }

        if (is_array($node)) {
            foreach ($node as &$child) {
                $this->patch($child, $root);
            }
            return;
        }

        if (is_object($node)) {
            $rc = new \ReflectionObject($node);
            foreach ($rc->getProperties() as $prop) {
                $value = $prop->getValue($node);
                $this->patch($value, $root);
                $prop->setValue(
                    $node, $value);
            }
        }
    }

    private function lookup(array|object $root, Reference $deferred): mixed
    {
        $current = $root;
        foreach ($deferred->path as [$op, $key]) {
            $current = match ($op) {
                'prop' => is_array($current) ?
                    ($current[$key]   ?? null) :
                    ($current->{$key} ?? null),
                'idx'  => $current[$key] ?? null,
            };
        }
        return $current;
    }
}
