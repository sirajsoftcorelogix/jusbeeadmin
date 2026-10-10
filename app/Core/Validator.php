<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;

class Validator
{
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $fieldRules = explode('|', $ruleString);

            foreach ($fieldRules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                if ($name === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = 'This field is required.';
                }

                if ($value === null || $value === '') {
                    continue;
                }

                if ($name === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $errors[$field][] = 'Please provide a valid email.';
                }

                if ($name === 'min' && is_scalar($value) && mb_strlen((string) $value) < (int) $param) {
                    $errors[$field][] = "Minimum length is {$param}.";
                }

                if ($name === 'max' && is_scalar($value) && mb_strlen((string) $value) > (int) $param) {
                    $errors[$field][] = "Maximum length is {$param}.";
                }

                if ($name === 'int' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field][] = 'Must be an integer.';
                }
            }
        }

        if (!empty($errors)) {
            throw new HttpException(422, json_encode(['errors' => $errors], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Validation failed');
        }

        return $data;
    }
}
