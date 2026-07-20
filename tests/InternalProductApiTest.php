<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for the demo's token-gated internal JSON API
 * (App\Controller\Api\InternalProductApiController), the endpoint the
 * InternalProduct grid reads through the bundle's JsonDataProvider.
 */
class InternalProductApiTest extends WebTestCase
{
    private const TOKEN = 'demo-internal-api-token';

    public function testRejectsRequestWithoutToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/internal-api/products');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRejectsRequestWithWrongToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/internal-api/products', server: ['HTTP_AUTHORIZATION' => 'Bearer wrong-token']);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testReturnsPagedPayloadWithValidToken(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/internal-api/products?limit=2&skip=0',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . self::TOKEN],
        );

        $this->assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertSame(15, $payload['total']);
        $this->assertCount(2, $payload['products']);
        $this->assertArrayHasKey('title', $payload['products'][0]);
    }

    public function testSortAndSearchAreAppliedServerSide(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/internal-api/products?sortBy=price&order=desc&limit=1',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . self::TOKEN],
        );
        $topPriced = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Down Sleeping Bag', $topPriced['products'][0]['title']);

        $client->request(
            'GET',
            '/internal-api/products?q=jacket',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . self::TOKEN],
        );
        $searched = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(1, $searched['total']);
        $this->assertSame('Waterproof Jacket', $searched['products'][0]['title']);
    }
}
