<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

final class PayloadRedactor
{
    /**
     * Recursively mask values for keys matching the configured redact_keys list.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function redact(array $data): array
    {
        /** @var array<string> $redactKeys */
        $redactKeys = config('conductor.redact_keys', []);

        $lowerKeys = array_map('strtolower', $redactKeys);

        return $this->walk($data, $lowerKeys);
    }

    /**
     * @param  array<mixed>  $data
     * @param  array<string>  $lowerKeys
     * @return array<mixed>
     */
    private function walk(array $data, array $lowerKeys): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), $lowerKeys, true)) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->walk($value, $lowerKeys);
            } elseif (is_object($value)) {
                $result[$key] = $this->walk((array) $value, $lowerKeys);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
