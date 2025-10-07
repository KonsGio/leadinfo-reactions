<?php
declare(strict_types=1);

namespace App\Domain\Entity;

final class Reaction
{
    public int $id;
    public string $name;
    public string $email;
    public string $title;
    public string $message;
    public int $rating;
    public string $created_at;

    /**
     * @param int $id
     * @param string $name
     * @param string $email
     * @param string $title
     * @param string $message
     * @param int $rating
     * @param string $created_at
     */
    public function __construct(
        int    $id,
        string $name,
        string $email,
        string $title,
        string $message,
        int    $rating,
        string $created_at
    )
    {
        $this->created_at = $created_at;
        $this->rating = $rating;
        $this->message = $message;
        $this->title = $title;
        $this->email = $email;
        $this->name = $name;
        $this->id = $id;
    }
}