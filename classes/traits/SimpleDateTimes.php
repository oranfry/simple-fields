<?php

namespace simplefields\traits;

use Closure;

trait SimpleDateTimes
{
    protected function simple_date(string $name, Closure|string|null $default = null): void
    {
        $this->simple_string($name, $default);

        $this->validations[] = fn ($line) => $this->validate_date($line->$name ?? null, $name);
    }

    protected function simple_datetime(string $name, Closure|string|null $default = null): void
    {
        $this->simple_string($name, $default);

        $this->validations[] = fn ($line) => $this->validate_datetime($line->$name ?? null, $name);
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
            $format = '/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/';

            if (!preg_match($format, $time, $groups)) {
                return "Incorrect format";
            }

            if (intval($groups[1]) > 23) {
                return "Invalid hour";
            }

            if (intval($groups[2]) > 59) {
                return "Invalid minute";
            }

            if (intval($groups[3]) > 59) {
                return "Invalid second";
            }
        }

        return null;
    }
}
