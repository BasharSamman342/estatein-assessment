<?php

namespace App\Http\Requests\Concerns;

/**
 * Multipart forms send booleans as strings ("true", "false", "on", …), which Laravel's
 * boolean rule rejects. Recognised strings become real booleans; anything else is left
 * alone so the boolean rule still reports it.
 */
trait NormalizesBooleans
{
    /**
     * @param  array<int, string>  $keys
     */
    protected function normalizeBooleans(array $keys): void
    {
        foreach ($keys as $key) {
            $value = $this->input($key);

            if (! is_string($value)) {
                continue;
            }

            $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($boolean !== null) {
                $this->merge([$key => $boolean]);
            }
        }
    }
}
