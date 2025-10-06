<?php
declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Repository\ReactionRepository;
use App\Domain\Validation\ReactionValidator;
use App\Domain\Entity\Reaction;

/**
 * Business logic for reading and creating reactions.
 *
 * Keeps controller thin and delegates DB + validation logic to collaborators.
 */
final readonly class ReactionService
{
    /**
     * @param ReactionRepository $reactionRepository
     * @param ReactionValidator $reactionValidator
     */
    public function __construct(
        private ReactionRepository $reactionRepository,
        private ReactionValidator  $reactionValidator,
    )
    {
    }

    /**
     * Fetch a paginated list of reactions with summary metadata.
     *
     * @return array{
     *   data: list<array<string, mixed>>,
     *   meta: array{total:int, perPage:int, page:int, pages:int, last:string}
     * }
     */
    public function list(int $pageNumber, int $pageSize): array
    {
        // Input guards
        $pageNumber = max(1, $pageNumber);
        $pageSize = max(1, $pageSize);

        // Fetch from repository
        $reactions = $this->reactionRepository->paginate($pageNumber, $pageSize);
        $totalRows = $this->reactionRepository->total();
        $totalPages = max(1, (int)ceil($totalRows / $pageSize));
        $lastCreatedAt = $this->reactionRepository->lastCreatedAt();

        // Convert entities → API-ready arrays
        $items = array_map(static fn(Reaction $reaction) => [
            'id' => $reaction->id,
            'name' => $reaction->name,
            'email' => $reaction->email,
            'title' => $reaction->title,
            'message' => $reaction->message,
            'rating' => $reaction->rating,
            'created_at' => $reaction->created_at,
        ], $reactions);

        return [
            'data' => $items,
            'meta' => [
                'total' => $totalRows,
                'perPage' => $pageSize,
                'page' => $pageNumber,
                'pages' => $totalPages,
                'last' => $lastCreatedAt,
            ],
        ];
    }

    /**
     * Validate and create a new reaction.
     *
     * @return object{ok:bool, id?:int, errors?:array<string,string>}
     */
    public function create(array $input): object
    {
        // Normalize and sanitize payload
        $normalized = [
            'name' => trim((string)($input['name'] ?? '')),
            'email' => trim((string)($input['email'] ?? '')),
            'title' => trim((string)($input['title'] ?? '')),
            'message' => trim((string)($input['message'] ?? '')),
            'rating' => (int)($input['rating'] ?? 0),
        ];

        // Validate fields
        $validationErrors = $this->reactionValidator->validate($normalized);
        if ($validationErrors) {
            return (object)[
                'ok' => false,
                'errors' => $validationErrors,
            ];
        }

        // Save and return new ID
        $reaction = $this->reactionRepository->create($normalized);

        return (object)[
            'ok' => true,
            'id' => $reaction->id,
        ];
    }
}