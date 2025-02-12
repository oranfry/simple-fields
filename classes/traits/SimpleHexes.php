<?php

namespace simplefields\traits;

use Closure;

trait SimpleHexes
{
    protected function simple_hex(string $name, Closure|string|null $default = null): void
    {
        $this->fields[$name] = function($records) use ($name): ?string {
            if (!$base64 = $records['/']->$name) {
                return null;
            }

            return bin2hex(base64_decode($base64));
        };

        $this->unfuse_fields[$name] = function($line, $oldline) use ($name): ?string {
            if (false === $bin = @hex2bin(@$line->$name)) {
                return null;
            }

            return base64_encode($bin);
        };

        $this->validations[] = function ($line) use ($name) : ?string {
            if (($value = @$line->$name) && @hex2bin($value) === false) {
                return 'Invalid hexidecimal value for ' . $name;
            }

            return null;
        };

        $this->completions[] = function ($line) use ($name, $default) {
            if (!property_exists($line, $name) || $line->$name === null) {
                $line->$name = static::sf_resolve_value($default, $line);
            }
        };
    }
}
