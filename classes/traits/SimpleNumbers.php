<?php

namespace simplefields\traits;

use Closure;

trait SimpleNumbers
{
    protected function simple_float(string $name, int $dp = 2): void
    {
        if ($dp < 0) {
            throw new SimpleFloatDefinitionException('DP should not be negative');
        }

        if ($dp > 48) {
            throw new SimpleFloatDefinitionException('Max DP is 48');
        }

        $this->fields[$name] = function ($records) use ($dp, $name): ?float {
            if (null === $value = $records['/']->$name ?? null) {
                return null;
            }

            return (float) bcadd('0', $value, $dp);
        };

        $this->unfuse_fields[$name] = function ($line) use ($dp, $name): ?string {
            if (null === $value = $line->$name ?? null) {
                return null;
            }

            return bcadd('0', (string) $value, $dp);
        };
    }

    protected function simple_int(string $name, Closure|int|null $default = null): void
    {
        $this->fields[$name] = fn ($records): ?int => $records['/']->$name;

        $this->unfuse_fields[$name] = function($line, $oldline) use ($name): ?int {
            if (!is_numeric(@$line->$name)) {
                return null;
            }

            return (int) $line->$name;
        };

        $this->completions[] = function ($line) use ($name, $default) {
            if (!property_exists($line, $name) || $line->$name === null) {
                $line->$name = static::sf_resolve_value($default, $line);
            }
        };
    }
}
