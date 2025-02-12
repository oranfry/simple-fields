<?php

namespace simplefields\traits;

use Closure;

trait SimpleStrings
{
    protected function simple_string(string $name, Closure|string|null $default = null): void
    {
        $this->fields[$name] = fn ($records): ?string => $records['/']->$name ?? null;
        $this->unfuse_fields[$name] = fn ($line, $oldline): ?string => $line->$name ?? null;

        $this->completions[] = function ($line) use ($name, $default) {
            if (!property_exists($line, $name) || $line->$name === null) {
                $line->$name = static::sf_resolve_value($default, $line);
            }
        };
    }
}
