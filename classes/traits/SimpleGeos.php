<?php

namespace simplefields\traits;

use Closure;

trait SimpleGeos
{
    protected function simple_latitude(string $name): void
    {
        $this->fields[$name] = fn ($records): ?float => $records['/']->$name;
        $this->unfuse_fields[$name] = fn ($line, $oldline): ?float => is_numeric(@$line->$name) ? min(90, max(-90, (float) $line->$name)) : null;
    }

    protected function simple_longitude(string $name): void
    {
        $this->fields[$name] = fn ($records): ?float => $records['/']->$name;
        $this->unfuse_fields[$name] = fn ($line, $oldline): ?float => is_numeric(@$line->$name) ? -(fmod((-min(180, max(-180, (float) $line->$name)) + 180), 360) - 180) : null;
    }
}
