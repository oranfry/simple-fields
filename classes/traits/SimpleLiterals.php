<?php

namespace simplefields\traits;

use Closure;
use simplefields\exception\SimpleLiteralDefinitionException;

trait SimpleLiterals
{
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
}
