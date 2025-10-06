<?php
declare(strict_types=1);

namespace App\Domain\Entity;

final readonly class Reaction
{
    /**
     * @param int $id
     * @param string $name
     * @param string $email
     * @param string $title
     * @param string $message
     * @param int    $rating
     * @param string $created_at
     */
    public function __construct(
        public int    $id,
        public string $name,
        public string $email,
        public string $title,
        public string $message,
        public int    $rating,
        public string $created_at
    )
    {
    }
}