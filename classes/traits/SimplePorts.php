<?php

namespace simplefields\traits;

use Closure;

trait SimplePorts
{
    protected function simple_port(string $name, Closure|int|null $default = null): void
    {
        $this->fields[$name] = fn($records): ?int => $records['/']->$name ?? null;
        $this->unfuse_fields[$name] = fn ($line, $oldline): ?int => $line->$name ?? null;

        $this->completions[] = function ($line) use ($name, $default) {
            if (!property_exists($line, $name) || $line->$name === null) {
                $line->$name = static::sf_resolve_value($default, $line);
            }
        };

        $this->validations[] = function ($line) use ($name): ?string {
            if ($line->$name !== null && $line->$name < 1 || $line->$name > 65535) {
                return $name . ' is out of range';
            }

            return null;
        };
    }
}
