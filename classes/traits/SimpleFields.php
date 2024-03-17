<?php

namespace simplefields\traits;

use Closure;
use simplefields\exception\SimpleEnumValueException;
use simplefields\exception\SimpleFloatDefinitionException;
use simplefields\exception\SimpleLiteralDefinitionException;

trait SimpleFields
{
    static function sf_resolve_value($value, object $line)
    {
        if ($value instanceof Closure) {
            return $value($line);
        }

        return $value;
    }

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

    protected function simple_booleans(): void
    {
        trigger_error('Method ' . static::class . '::' . preg_replace('/.*::/', '', __METHOD__) . '() is deprecated; please use singular version', E_USER_DEPRECATED);

        foreach (func_get_args() as $name) {
            $this->simple_boolean($name);
        }
    }

    protected function simple_date(string $name, Closure|string|null $default = null): void
    {
        $this->simple_string($name, $default);

        $this->validations[] = fn ($line) => $this->validate_date($line->$name, $name);
    }

    protected function simple_datetime(string $name, Closure|string|null $default = null): void
    {
        $this->simple_string($name, $default);

        $this->validations[] = fn ($line) => $this->validate_datetime($line->$name, $name);
    }

    protected function simple_enum(string $name, array $allowed, Closure|string|null $default = null): void
    {
        $this->fields[$name] = function ($records) use ($name, $allowed): string {
            if (null === $as_string = @$allowed[$records['/']->$name]) {
                throw new SimpleEnumValueException('Could not fuse enum "' . $this->name . '->' . $name . '" with value "' . @$records['/']->$name . '". Expected one of [' . implode(', ', array_map(fn ($v) => '"' . $v . '"', $allowed)) . ']');
            }

            return $as_string;
        };

        $this->unfuse_fields[$name] = function ($line, $oldline) use ($name, $allowed): int {
            if (($as_int = @array_flip($allowed)[$line->$name]) === null) {
                throw new SimpleEnumValueException('Could not unfuse enum ' . $name);
            }

            return $as_int;
        };

        $this->validations[] = function ($line) use ($name, $allowed): ?string {
            if (!in_array($line->$name, $allowed)) {
                return 'invalid ' . $name;
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
                    return 'invalid ' . $name . '. Unrecognised value "' . $value . '"';
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

    protected function simple_hexs(): void
    {
        trigger_error('Method ' . static::class . '::' . preg_replace('/.*::/', '', __METHOD__) . '() is deprecated; please use singular version', E_USER_DEPRECATED);

        foreach (func_get_args() as $name) {
            $this->simple_hex($name);
        }
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

    protected function simple_ints(): void
    {
        trigger_error('Method ' . static::class . '::' . preg_replace('/.*::/', '', __METHOD__) . '() is deprecated; please use singular version', E_USER_DEPRECATED);

        foreach (func_get_args() as $name) {
            $this->simple_int($name);
        }
    }

    protected function simple_latitude(string $name): void
    {
        $this->fields[$name] = fn ($records): ?float => $records['/']->$name;
        $this->unfuse_fields[$name] = fn ($line, $oldline): ?float => is_numeric(@$line->$name) ? min(90, max(-90, (float) $line->$name)) : null;
    }

    protected function simple_literal(string $name, $value): void
    {
        if (is_null($value)) {
            $this->fields[$name] = function($records) use ($value): ?string {
                return null;
            };

            return;
        }

        if (is_string($value)) {
            $this->fields[$name] = function($records) use ($value): string {
                return $value;
            };

            return;
        }

        if (is_bool($value)) {
            $this->fields[$name] = function($records) use ($value): bool {
                return $value;
            };

            return;
        }

        if (is_int($value)) {
            $this->fields[$name] = function($records) use ($value): int {
                return $value;
            };

            return;
        }

        if (is_float($value)) {
            $this->fields[$name] = function($records) use ($value): float {
                return $value;
            };

            return;
        }

        throw new SimpleLiteralDefinitionException('Unsupported literal type');
    }

    protected function simple_longitude(string $name): void
    {
        $this->fields[$name] = fn ($records): ?float => $records['/']->$name;
        $this->unfuse_fields[$name] = fn ($line, $oldline): ?float => is_numeric(@$line->$name) ? -(fmod((-min(180, max(-180, (float) $line->$name)) + 180), 360) - 180) : null;
    }

    protected function simple_string(string $name, Closure|string|null $default = null): void
    {
        $this->fields[$name] = fn ($records): ?string => $records['/']->$name;
        $this->unfuse_fields[$name] = fn ($line, $oldline): ?string => @$line->$name;

        $this->completions[] = function ($line) use ($name, $default) {
            if (!property_exists($line, $name) || $line->$name === null) {
                $line->$name = static::sf_resolve_value($default, $line);
            }
        };
    }

    protected function simple_strings(): void
    {
        trigger_error('Method ' . static::class . '::' . preg_replace('/.*::/', '', __METHOD__) . '() is deprecated; please use singular version', E_USER_DEPRECATED);

        foreach (func_get_args() as $name) {
            $this->simple_string($name);
        }
    }

    protected function simple_time(string $name, ?string $default = null): void
    {
        $this->simple_string($name, $default);

        $this->validations[] = fn ($line) => $this->validate_time($line->$name, $name);
    }

    protected function validate_date(?string $date, string $name): ?string
    {
        if ($date !== null) {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $date, $matches)) {
                return $name . ' is not in expected format of YYYY-MM-DD';
            }

            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (!checkdate($month, $day, $year)) {
                return $name . ' is not an actual date';
            }
        }

        return null;
    }

    protected function validate_datetime(?string $datetime, string $name): ?string
    {
        if ($datetime !== null) {
            if (count($parts = explode(' ', $datetime)) !== 2) {
                return $name . ' is not in expected format of DATE TIME';
            }

            [$date, $time] = $parts;

            $errors = array_filter([
                $this->validate_date($date, $name . ' (date component)'),
                $this->validate_time($time, $name . ' (time component)'),
            ]);

            return reset($errors);
        }

        return null;
    }

    protected function validate_time(?string $time, string $name): ?string
    {
        if ($time !== null) {
            $format = '/^([0-9]{2})([0-9]{2})$/';

            if (!preg_match($format, $time, $groups)) {
                return "Incorrect format";
            }

            if (intval($groups[1]) > 23) {
                return "Invalid hour";
            }

            if (intval($groups[2]) > 59) {
                return "Invalid minute";
            }
        }

        return null;
    }
}
