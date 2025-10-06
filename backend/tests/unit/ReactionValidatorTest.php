<?php
declare(strict_types=1);

use App\Domain\Validation\ReactionValidator;
use PHPUnit\Framework\TestCase;

final class ReactionValidatorTest extends TestCase
{
    private ReactionValidator $reactionValidator;
    protected function setUp(): void { $this->reactionValidator = new ReactionValidator(); }

    public function test_valid_payload_passes(): void
    {
        $errors = $this->reactionValidator->validate([
            'name'    => 'Alice Smith',
            'email'   => 'alice@example.com',
            'title'   => 'Great idea',
            'message' => 'Nice work',
            'rating'  => 5
        ]);
        $this->assertSame([], $errors);
    }

    public function test_invalid_fields_flagged(): void
    {
        $errors = $this->reactionValidator->validate([
            'name'    => 'Al',
            'email'   => 'bad',
            'title'   => 'Hey',
            'message' => '',
            'rating'  => 6
        ]);
        foreach (['name','email','title','message','rating'] as $dataPiece) {
            $this->assertArrayHasKey($dataPiece, $errors);
        }
    }
}