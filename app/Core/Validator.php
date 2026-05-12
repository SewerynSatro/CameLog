<?php
namespace App\Core;

/**
 * Bardzo prosty walidator.
 */
class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $message = 'Pole wymagane'): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function email(string $field, string $message = 'Nieprawidłowy adres email'): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function min(string $field, int $length, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        if (is_string($value) && mb_strlen($value) < $length) {
            $this->errors[$field] = $message ?? "Minimalnie $length znaków";
        }
        return $this;
    }

    public function max(string $field, int $length, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        if (is_string($value) && mb_strlen($value) > $length) {
            $this->errors[$field] = $message ?? "Maksymalnie $length znaków";
        }
        return $this;
    }

    public function in(string $field, array $allowed, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->errors[$field] = $message ?? 'Nieprawidłowa wartość';
        }
        return $this;
    }

    public function fails(): bool { return !empty($this->errors); }
    public function errors(): array { return $this->errors; }
}
