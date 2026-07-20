<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Resolves the IRI references carried by reference rows (endUser, epc, licensor,
 * materials) into human-readable names, mirroring what the Angular front-end does
 * with its cached *Service.find(id) lookups.
 *
 * The reference API returns related resources as IRIs (e.g. endUser =>
 * "/api/reference/end_users/48"), so the grid needs the related collections to
 * turn those ids into labels. Each collection is fetched once per request and
 * cached in a map keyed by id; for a demo grid that is enough (a longer-lived
 * cache could be layered on later without changing callers).
 */
final class ReferenceLookup
{
    /**
     * Per-request cache of resource path => [id => label].
     *
     * @var array<string, array<int, string>>
     */
    private array $maps = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(REFERENCE_API_BASE_URL)%')]
        private readonly string $baseUri,
        #[Autowire('%env(REFERENCE_API_TOKEN)%')]
        private readonly string $apiToken,
    ) {
    }

    public function endUserName(?string $iri): string
    {
        return $this->label('api/reference/end_users', $iri);
    }

    public function epcName(?string $iri): string
    {
        return $this->label('api/reference/epcs', $iri);
    }

    public function licensorName(?string $iri): string
    {
        return $this->label('api/reference/licensors', $iri);
    }

    public function materialName(?string $iri): string
    {
        return $this->label('api/reference/materials', $iri);
    }

    /**
     * Resolve a list of material IRIs (e.g. the `materialsRel` array) into a
     * comma-separated string of names, dropping any that can't be resolved.
     *
     * @param array<int, string>|null $iris
     */
    public function materialNames(?array $iris): string
    {
        if (null === $iris || [] === $iris) {
            return '';
        }

        $names = array_filter(array_map($this->materialName(...), $iris));

        return implode(', ', $names);
    }

    private function label(string $resource, ?string $iri): string
    {
        if (null === $iri || '' === $iri) {
            return '';
        }

        $id = $this->extractId($iri);
        if (null === $id) {
            return '';
        }

        return $this->map($resource)[$id] ?? '';
    }

    /**
     * @return array<int, string>
     */
    private function map(string $resource): array
    {
        if (isset($this->maps[$resource])) {
            return $this->maps[$resource];
        }

        $map = [];
        foreach ($this->fetch($resource) as $item) {
            if (!isset($item['id'])) {
                continue;
            }

            // Materials prefer their spec number as the display label, matching
            // the Angular grid (m.specNo || m.name).
            $map[(int) $item['id']] = (string) ($item['specNo'] ?? $item['name'] ?? '');
        }

        return $this->maps[$resource] = $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $resource): array
    {
        $url = rtrim($this->baseUri, '/').'/'.ltrim($resource, '/');

        return $this->httpClient
            ->request('GET', $url, [
                'headers' => ['Authorization' => 'Bearer '.$this->apiToken],
            ])
            ->toArray();
    }

    private function extractId(string $iri): ?int
    {
        $segment = substr($iri, strrpos($iri, '/') + 1);

        return ctype_digit($segment) ? (int) $segment : null;
    }
}
