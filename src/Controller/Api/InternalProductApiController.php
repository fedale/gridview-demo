<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Self-contained, token-gated JSON API used only to demonstrate the bundle's
 * JsonDataProvider against an endpoint that requires an Authorization header.
 * It mimics the query contract JsonDataProvider speaks by default (limit/skip/q/
 * sortBy/order) and answers with a dummyjson-shaped body ({products, total}),
 * so the grid can page, search and sort entirely server-side.
 *
 * The dataset is a static in-memory list: the point is the token check and the
 * response shape, not persistence. Without a valid Bearer token every route
 * returns 401, proving the grid really carries the credential on each request.
 */
#[Route('/internal-api', name: 'internal_api_')]
class InternalProductApiController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(INTERNAL_API_TOKEN)%')]
        private readonly string $apiToken,
    ) {
    }

    #[Route('/products', name: 'products', methods: ['GET'])]
    public function products(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return $this->json(
                ['error' => 'Missing or invalid Authorization bearer token.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $products = $this->dataset();

        $search = trim((string) $request->query->get('q', ''));
        if ('' !== $search) {
            $needle = mb_strtolower($search);
            $products = array_values(array_filter(
                $products,
                static fn (array $product): bool => str_contains(mb_strtolower($product['title']), $needle)
                    || str_contains(mb_strtolower($product['category']), $needle),
            ));
        }

        $sortBy = (string) $request->query->get('sortBy', '');
        if ('' !== $sortBy && \array_key_exists($sortBy, $products[0] ?? [])) {
            $direction = 'desc' === strtolower((string) $request->query->get('order', 'asc')) ? -1 : 1;
            usort(
                $products,
                static fn (array $a, array $b): int => ($a[$sortBy] <=> $b[$sortBy]) * $direction,
            );
        }

        $total = \count($products);

        // limit=0 (or absent) means "return everything", matching the dummyjson
        // convention JsonDataProvider relies on for its export/getAllData path.
        $limit = max(0, $request->query->getInt('limit'));
        $skip = max(0, $request->query->getInt('skip'));
        $page = 0 === $limit ? \array_slice($products, $skip) : \array_slice($products, $skip, $limit);

        return $this->json(['products' => array_values($page), 'total' => $total]);
    }

    private function isAuthorized(Request $request): bool
    {
        $header = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return false;
        }

        return hash_equals($this->apiToken, substr($header, 7));
    }

    /**
     * @return list<array{id: int, title: string, category: string, price: float, weight: float, stock: int}>
     */
    private function dataset(): array
    {
        return [
            ['id' => 1, 'title' => 'Aluminium Water Bottle', 'category' => 'Outdoor', 'price' => 18.5, 'weight' => 0.28, 'stock' => 120],
            ['id' => 2, 'title' => 'Trekking Backpack 40 L', 'category' => 'Outdoor', 'price' => 89.9, 'weight' => 1.35, 'stock' => 42],
            ['id' => 3, 'title' => 'Merino Wool Socks', 'category' => 'Apparel', 'price' => 14.0, 'weight' => 0.08, 'stock' => 300],
            ['id' => 4, 'title' => 'Stainless Steel Thermos', 'category' => 'Kitchen', 'price' => 32.0, 'weight' => 0.55, 'stock' => 75],
            ['id' => 5, 'title' => 'Carbon Trekking Poles', 'category' => 'Outdoor', 'price' => 120.0, 'weight' => 0.46, 'stock' => 30],
            ['id' => 6, 'title' => 'Cotton T-Shirt', 'category' => 'Apparel', 'price' => 22.5, 'weight' => 0.18, 'stock' => 210],
            ['id' => 7, 'title' => 'Cast Iron Pan 28 cm', 'category' => 'Kitchen', 'price' => 45.0, 'weight' => 2.1, 'stock' => 60],
            ['id' => 8, 'title' => 'Headlamp 350 lm', 'category' => 'Outdoor', 'price' => 27.9, 'weight' => 0.09, 'stock' => 95],
            ['id' => 9, 'title' => 'Down Sleeping Bag', 'category' => 'Outdoor', 'price' => 159.0, 'weight' => 0.98, 'stock' => 18],
            ['id' => 10, 'title' => 'Ceramic Coffee Mug', 'category' => 'Kitchen', 'price' => 9.5, 'weight' => 0.32, 'stock' => 400],
            ['id' => 11, 'title' => 'Waterproof Jacket', 'category' => 'Apparel', 'price' => 139.0, 'weight' => 0.42, 'stock' => 55],
            ['id' => 12, 'title' => 'Folding Camp Chair', 'category' => 'Outdoor', 'price' => 39.9, 'weight' => 1.7, 'stock' => 80],
            ['id' => 13, 'title' => 'Chef Knife 20 cm', 'category' => 'Kitchen', 'price' => 64.0, 'weight' => 0.2, 'stock' => 48],
            ['id' => 14, 'title' => 'Fleece Beanie', 'category' => 'Apparel', 'price' => 16.5, 'weight' => 0.06, 'stock' => 260],
            ['id' => 15, 'title' => 'Portable Gas Stove', 'category' => 'Outdoor', 'price' => 54.0, 'weight' => 1.05, 'stock' => 36],
        ];
    }
}
