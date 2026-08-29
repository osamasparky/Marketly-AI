<?php

namespace App\AI\Security;

use InvalidArgumentException;

class AISchemaValidator
{
    /**
     * Validate AI output against expected schema properties and types before persistence.
     *
     * @param array<string, mixed> $output
     * @param array<string, string> $requiredTypes (e.g. ['title' => 'string', 'hook' => 'string', 'score' => 'integer'])
     * @return array<string, mixed>
     * @throws InvalidArgumentException
     */
    public static function validate(array $output, array $requiredTypes): array
    {
        $validated = [];

        foreach ($requiredTypes as $field => $expectedType) {
            if (!array_key_exists($field, $output)) {
                throw new InvalidArgumentException("AI output validation failed: Missing required field '{$field}'.");
            }

            $value = $output[$field];
            $actualType = gettype($value);

            if ($expectedType === 'int') $expectedType = 'integer';
            if ($expectedType === 'bool') $expectedType = 'boolean';
            if ($expectedType === 'float') $expectedType = 'double';

            if ($actualType !== $expectedType && !($expectedType === 'array' && is_array($value))) {
                throw new InvalidArgumentException("AI output field '{$field}' type mismatch: expected {$expectedType}, got {$actualType}.");
            }

            // Neutralize any shell/SQL characters if output is a raw string
            if (is_string($value)) {
                $validated[$field] = trim($value);
            } else {
                $validated[$field] = $value;
            }
        }

        return $validated;
    }
}
