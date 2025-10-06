<?php
declare(strict_types=1);

namespace Feature;

use DI\DependencyException;
use DI\NotFoundException;
use JsonException;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class ApiBasicTest extends TestCase
{
    /**
     * /api/health should return 200 so the UI can show “service up”.
     *
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function test_health_ok(): void
    {
        $app = make_test_app();

        $req = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');
        $res = $app->handle($req);

        $this->assertSame(200, $res->getStatusCode());
    }

    /**
     * Happy path:
     * 1) list is empty
     * 2) create one valid reaction (201)
     * 3) list again shows exactly one item
     *
     * @return void
     * @throws JsonException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function test_list_create_list(): void
    {
        $app = make_test_app();

        // 1) First list (empty)
        $reqList1 = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/reactions?limit=3&page=1');

        $resList1 = $app->handle($reqList1);
        $this->assertSame(200, $resList1->getStatusCode());

        $body1 = json_decode((string)$resList1->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $body1['meta']['total']);

        // 2) Create one
        $reqCreate = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/reactions')
            ->withHeader('Content-Type', 'application/json');

        $payload = [
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'title' => 'Great',
            'message' => 'Nice',
            'rating' => 5,
        ];
        $reqCreate->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));
        $reqCreate->getBody()->rewind();

        $resCreate = $app->handle($reqCreate);
        $this->assertSame(201, $resCreate->getStatusCode());

        // 3) List again (now 1)
        $reqList2 = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/reactions?limit=3&page=1');

        $resList2 = $app->handle($reqList2);
        $this->assertSame(200, $resList2->getStatusCode());

        $body2 = json_decode((string)$resList2->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $body2['meta']['total']);
        $this->assertCount(1, $body2['data']);
    }
}