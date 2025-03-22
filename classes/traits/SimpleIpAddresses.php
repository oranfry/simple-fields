<?php

namespace simplefields\traits;

use Closure;

trait SimpleIpAddresses
{
    protected function simple_ip_address(string $name, Closure|string|null $default = null): void
    {
        $this->fields[$name] = function ($records) use ($name): ?string {
            if (@$records['/']->$name && $n = inet_pton($records['/']->$name)) {
                return inet_ntop($n);
            }

            return null;
        };

        $this->unfuse_fields[$name] = function ($line) use ($name): ?string {
            if (@$line->$name && $n = inet_pton($line->$name)) {
                return inet_ntop($n);
            }

            return null;
        };

        $this->completions[] = function ($line) use ($name, $default) {
            if (!property_exists($line, $name) || $line->$name === null) {
                $line->$name = static::sf_resolve_value($default, $line);
            }
        };

        $this->validations[] = fn ($line) => @$line->$name && inet_pton($line->$name) === false ? $name . ' is not a valid ip address' : null;
    }

    protected function simple_ipv4_address(string $name, Closure|string|null $default = null): void
    {
        $this->simple_ip_address($name, $default);

        $this->validations[] = fn ($line) => @$line->$name && strpos($line->$name, '.') === false ? $name . ' is not an ipv4 address' : null;
    }

    protected function simple_ipv6_address(string $name, Closure|string|null $default = null): void
    {
        $this->simple_ip_address($name, $default);

        $this->validations[] = fn ($line) => @$line->$name && strpos($line->$name, ':') === false ? $name . ' is not an ipv6 address' : null;
    }
}
