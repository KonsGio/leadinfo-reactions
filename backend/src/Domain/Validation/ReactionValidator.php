<?php
declare(strict_types=1);

namespace App\Domain\Validation;

final class ReactionValidator
{
    /**
     * Simple per-field validation per assignment rules.
     *
     * @param array $data
     * @return array
     */
    public function validate(array $data): array
    {
        $e = [];

        $name = (string)($data['name'] ?? '');
        if (strlen($name) < 5 || strlen($name) > 100) {
            $e['name'] = 'Name must be 5–100 characters.';
        }

        $email = (string)($data['email'] ?? '');
        if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Please enter a valid email.';
        }

        $title = (string)($data['title'] ?? '');
        if (strlen($title) < 5 || strlen($title) > 100) {
            $e['title'] = 'Title must be 5–100 characters.';
        }

        $msg = (string)($data['message'] ?? '');
        if (strlen($msg) < 1 || strlen($msg) > 400) {
            $e['message'] = 'Message must be 1–400 characters.';
        }

        $rating = (int)($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            $e['rating'] = 'Rating must be between 1 and 5.';
        }

        return $e;
    }
}