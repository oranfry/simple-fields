<?php

namespace simplefields\traits;

use Closure;

trait SimpleBooleans
{
    protected function simple_boolean(string $name, Closure|bool|null $default = null): void
    {
        $this->fields[$name] = fn($records): ?bool => @$records['/']->$name;
        $this->unfuse_fields[$name] = fn ($line, $oldline): ?bool => (bool) @$line->$name;

        $this->completions[] = function ($line) use ($name, $default) {
            if (!property_exists($line, $name) || $line->$name === null) {
                $line->$name = static::sf_resolve_value($default, $line);
            }
        };
    }
}
