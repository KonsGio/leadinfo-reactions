<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use PDO;
use App\Domain\Entity\Reaction;

/**
 * Data access for reactions (PDO-based).
 */
final readonly class ReactionRepository
{
    public function __construct(private PDO $connection)
    {
    }

    /**
     * Persist a new reaction and return the created entity.
     *
     * @param array{
     *   name:string,
     *   email:string,
     *   title:string,
     *   message:string,
     *   rating:int
     * } $payload
     */
    public function create(array $payload): Reaction
    {
        $sql = <<<SQL
            INSERT INTO reactions (name, email, title, message, rating, created_at)
            VALUES (:name, :email, :title, :message, :rating, :created_at)
        SQL;

        $statement = $this->connection->prepare($sql);
        $createdAt = date('Y-m-d H:i:s');

        $statement->bindValue(':name', $payload['name']);
        $statement->bindValue(':email', $payload['email']);
        $statement->bindValue(':title', $payload['title']);
        $statement->bindValue(':message', $payload['message']);
        $statement->bindValue(':rating', (int)$payload['rating'], PDO::PARAM_INT);
        $statement->bindValue(':created_at', $createdAt);

        $statement->execute();

        $newId = (int)$this->connection->lastInsertId();

        return new Reaction(
            $newId,
            $payload['name'],
            $payload['email'],
            $payload['title'],
            $payload['message'],
            (int)$payload['rating'],
            $createdAt
        );
    }

    /**
     * Return a page of newest reactions as entities.
     *
     * @param int $pageNumber
     * @param int $pageSize
     * @return Reaction[]
     */
    public function paginate(int $pageNumber, int $pageSize): array
    {
        // Keep bounds sane
        $pageSize = max(1, $pageSize);
        $offset = max(0, ($pageNumber - 1) * $pageSize);

        $sql = <<<SQL
            SELECT id, name, email, title, message, rating, created_at
            FROM reactions
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(
            static fn(array $row) => new Reaction(
                (int)$row['id'],
                (string)$row['name'],
                (string)$row['email'],
                (string)$row['title'],
                (string)$row['message'],
                (int)$row['rating'],
                (string)$row['created_at']
            ),
            $rows
        );
    }

    /**
     * Total number of reactions.
     * @return int
     */
    public function total(): int
    {
        $statement = $this->connection->query('SELECT COUNT(*) FROM reactions');
        return (int)$statement->fetchColumn();
    }

    /**
     * Timestamp of the most recent reaction, or null if none.
     *
     * @return string|null
     */
    public function lastCreatedAt(): ?string
    {
        $statement = $this->connection->query('SELECT MAX(created_at) FROM reactions');
        $value = $statement->fetchColumn();
        return $value !== false ? (string)$value : null;
    }
}