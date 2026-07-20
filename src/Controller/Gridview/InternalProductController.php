<?php

namespace App\Controller\Gridview;

use App\Model\InternalProduct;
use Fedale\GridviewBundle\Controller\AbstractGridController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only grid backed by the demo's own token-gated JSON API
 * (App\Controller\Api\InternalProductApiController) via the bundle's built-in
 * Fedale\GridviewBundle\DataProvider\JsonDataProvider. It proves two things the
 * public-API DummyJsonUser grid does not: (1) the reusable JsonDataProvider
 * shipped in the bundle needs no custom provider class, only config; and (2) an
 * endpoint requiring an Authorization token works, because the token travels as
 * a request header the provider forwards on every call.
 *
 * The provider is selected in config/packages/gridview.yaml
 * (`gridviews.internalproduct.dataProvider: ...JsonDataProvider`). Everything
 * else — base URI, response shape and the Bearer header — is described here in
 * dataConfig(); the token itself comes from the INTERNAL_API_TOKEN env var and
 * is never hard-coded. Extends the read-only {@see AbstractGridController}: a
 * non-Doctrine backend has no CRUD write path today.
 */
#[Route('/gridview/internal-product', name: 'gridview_internal_product_')]
class InternalProductController extends AbstractGridController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire('%env(INTERNAL_API_TOKEN)%')]
        private readonly string $apiToken,
        // Origin of the internal API. Empty by default: the grid self-calls the
        // host serving the request, which is correct on any concurrent server
        // (PHP-FPM, production). Set it to a separate origin when the dev server
        // handles one request at a time (`symfony serve` with a single `php -S`
        // worker deadlocks on a same-worker self-call). See .env.
        #[Autowire('%env(INTERNAL_API_BASE_URL)%')]
        private readonly string $apiBaseUri = '',
    ) {
    }

    protected function getDataClass(): string
    {
        return InternalProduct::class;
    }

    protected function viewConfig(): array
    {
        return [
            'labels' => ['heading' => 'Products (internal token-gated API demo)'],
            'options' => [
                'behavior' => ['globalSearch' => ['title', 'category']],
            ],
        ];
    }

    protected function dataConfig(): array
    {
        // Prefer an explicit origin when set (see the constructor note on the
        // single-worker dev server); otherwise self-call the current host.
        $baseUri = '' !== $this->apiBaseUri
            ? $this->apiBaseUri
            : ($this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost() ?? 'http://localhost');

        return [
            'model' => [
                'baseUri' => $baseUri,
                'resource' => 'internal-api/products',
                'listPath' => 'products',
                'totalPath' => 'total',
                'headers' => ['Authorization' => 'Bearer '.$this->apiToken],
            ],
            'pagination' => ['defaultPageSize' => 10],
            'sort' => [
                'map' => [
                    'title' => ['asc' => ['title'], 'desc' => ['title']],
                    'price' => ['asc' => ['price'], 'desc' => ['price']],
                    'stock' => ['asc' => ['stock'], 'desc' => ['stock']],
                ],
                'default' => ['title' => 'asc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            'id',
            ['attribute' => 'title', 'label' => 'Title', 'sortable' => true],
            ['attribute' => 'category', 'label' => 'Category'],
            ['attribute' => 'price', 'label' => 'Price (EUR)', 'type' => 'number', 'sortable' => true],
            ['attribute' => 'weight', 'label' => 'Weight (kg)', 'type' => 'number'],
            ['attribute' => 'stock', 'label' => 'Stock', 'type' => 'number', 'sortable' => true],
        ];
    }
}
