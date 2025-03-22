<?php

namespace simplefields\traits;

use Closure;
use simplefields\exception\SimpleEnumValueException;

trait SimpleEnums
{
    protected function simple_enum(string $name, array $allowed, Closure|string|null $default = null): void
    {
        $this->fields[$name] = function ($records) use ($name, $allowed): ?string {
            if (!array_key_exists($records['/']->$name, $allowed)) {
                throw new SimpleEnumValueException('Could not fuse enum "' . $this->name . '->' . $name . '" with value "' . @$records['/']->$name . '". Expected one of [' . implode(', ', array_map(fn ($v) => '"' . $v . '"', $allowed)) . ']');
            }

            return $allowed[$records['/']->$name];
        };

        $this->unfuse_fields[$name] = function ($line, $oldline) use ($name, $allowed): int {
            foreach ($allowed as $as_int => $allowed_value) {
                if (@$line->$name === $allowed_value) {
                    return $as_int;
                }
            }

            throw new SimpleEnumValueException('Could not unfuse enum ' . $name);
        };

        $this->validations[] = function ($line) use ($name, $allowed): ?string {
            if (!in_array($line->$name, $allowed, true)) {
                return 'Invalid ' . $name;
            }

            return null;
        };

        $this->completions[] = function ($line) use ($name, $default, $allowed) {
            if (
                !property_exists($line, $name)
                || $line->$name === null
                || $line->$name === ''
                && false === array_search('', $allowed)
            ) {
                $line->$name = static::sf_resolve_value($default, $line);
            }
        };
    }

    protected function simple_enum_multi(string $name, array $allowed, Closure|string|null $default = null): void
    {
        $this->fields[$name] = function ($records) use ($name, $allowed): string {
            $values = [];

            foreach ($allowed as $i => $allowed_value) {
                if ($records['/']->$name & (1 << $i)) {
                    $values[] = $allowed_value;
                }
            }

            return implode(',', $values);
        };

        $this->unfuse_fields[$name] = function ($line, $oldline) use ($name, $allowed): int {
            $as_int = 0;
            $values = $line->$name ? explode(',', $line->$name) : [];

            foreach ($allowed as $i => $allowed_value) {
                if (in_array($allowed_value, $values)) {
                    $as_int |= (1 << $i);
                }
            }

            return $as_int;
        };

        $this->validations[] = function ($line) use ($name, $allowed): ?string {
            $values = $line->$name ? explode(',', $line->$name) : [];

            foreach ($values as $value) {
                if (!in_array($value, $allowed)) {
                    return 'Invalid ' . $name . '. Unrecognised value "' . $value . '"';
                }
            }

            return null;
        };

        $this->completions[] = function ($line) use ($name, $default) {
            if (!property_exists($line, $name) || $line->$name === null) {
                $default_as_int = 0;
                $default_value = static::sf_resolve_value($default, $line);
                $default_values = $default_value ? explode(',', $default_value) : [];

                foreach ($allowed as $i => $allowed_value) {
                    if (in_array($allowed_value, $default_values)) {
                        $default_as_int |= (1 << $i);
                    }
                }

                $line->$name = $default_as_int;
            }
        };
    }
}
