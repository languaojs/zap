<?php

namespace Zap\Core\Utils;

class Validator
{
    protected array $data = [];
    protected array $rules = [];
    protected array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): static
    {
        return new static($data, $rules);
    }

    public function fails(): bool
    {
        return !$this->validate();
    }

    public function passes(): bool
    {
        return $this->validate();
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                if (isset($this->errors[$field])) {
                    break;
                }

                $params = null;

                if (str_contains($rule, ':')) {
                    [$rule, $params] = explode(':', $rule, 2);
                }

                if ($value === null && $rule !== 'required') {
                    continue;
                }

                $method = "validate" . ucfirst($rule);

                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $params);
                }
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function validated(): array
    {
        $valid = [];

        foreach ($this->rules as $field => $rule) {
            if (!isset($this->errors[$field]) && isset($this->data[$field])) {
                $valid[$field] = $this->data[$field];
            }
        }

        return $valid;
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // --- Validation Rules ---

    protected function validateRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "$field is required.");
        }
    }

    protected function validateString(string $field, mixed $value): void
    {
        if (!is_string($value)) {
            $this->addError($field, "$field must be a string.");
        }
    }

    protected function validateEmail(string $field, mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "$field must be a valid email.");
        }
    }

    protected function validateMin(string $field, mixed $value, string $param): void
    {
        if (strlen((string) $value) < (int) $param) {
            $this->addError($field, "$field must be at least $param characters.");
        }
    }

    protected function validateMax(string $field, mixed $value, string $param): void
    {
        if (strlen((string) $value) > (int) $param) {
            $this->addError($field, "$field must not exceed $param characters.");
        }
    }

    protected function validateNumeric(string $field, mixed $value): void
    {
        if (!is_numeric($value)) {
            $this->addError($field, "$field must be numeric.");
        }
    }

    protected function validateUrl(string $field, mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "$field must be a valid URL.");
        }
    }

    protected function validateBoolean(string $field, mixed $value): void
    {
        if (!is_bool($value) && !in_array($value, [0, 1, '0', '1'], true)) {
            $this->addError($field, "$field must be boolean.");
        }
    }

    protected function validateIn(string $field, mixed $value, ?string $params): void
    {
        $allowed = explode(',', $params ?? '');

        if (!in_array($value, $allowed, true)) {
            $this->addError($field, "$field must be one of: $params");
        }
    }

    protected function validateArray(string $field, mixed $value): void
    {
        if (!is_array($value)) {
            $this->addError($field, "$field must be an array.");
        }
    }

    protected function validateRegex(string $field, mixed $value, ?string $param): void
    {
        if (!preg_match($param ?? '', (string) $value)) {
            $this->addError($field, "$field format is invalid.");
        }
    }

    protected function validateConfirmed(string $field, mixed $value): void
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->data[$confirmationField] ?? null;

        if ($value !== $confirmationValue) {
            $this->addError($field, "$field confirmation does not match.");
        }
    }
}
