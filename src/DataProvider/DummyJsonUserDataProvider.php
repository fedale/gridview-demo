<?php

namespace App\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Fedale\GridviewBundle\DataProvider\AbstractDataProvider;
use Fedale\GridviewBundle\Event\RowEvent;
use Fedale\GridviewBundle\Row\Row;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Read-only DataProviderInterface backed by the public https://dummyjson.com
 * API instead of Doctrine, demonstrating that Gridview's data layer is
 * pluggable the same way RuleProviderInterface is in access-control-bundle.
 * Sorting/searching are pushed down to the API's own sortBy/order/q query
 * params rather than replicated client-side (dummyjson supports both natively,
 * unlike the access-control API assumed by ApiRuleProvider).
 */
final class DummyJsonUserDataProvider extends AbstractDataProvider
{
    private string $resource = 'users';

    private ?string $searchTerm = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $baseUri = 'https://dummyjson.com',
    ) {
    }

    public function prepareModels(string|array $models): void
    {
        $this->resource = is_string($models) ? $models : ($models['resource'] ?? 'users');
    }

    public function setFormName(string $formName): void
    {
        // No filter form on this demo grid; nothing to key request params under.
    }

    public function applyGlobalSearch(array $fields, string $term): void
    {
        $this->searchTerm = $term;
    }

    public function getData()
    {
        // Pagination::getCurrentPage() clamps the requested page against the
        // total page count the first time it's called, and caches the result —
        // so totalCount must be set (a cheap limit=1 probe) *before* getOffset()
        // is read, exactly like EntityDataProvider::getData() calls
        // getTotalCount() before setMaxResults()/setFirstResult(). Reading
        // getOffset() first would clamp against a stale totalCount of 0 and
        // produce a negative offset on any page beyond the first.
        $this->pagination->setTotalCount($this->fetchTotalCount());

        $limit = $this->pagination->getPageSize() ?? 10;
        $offset = $this->pagination->getOffset();

        $payload = $this->fetch($limit, $offset);
        $this->models = $this->buildRows($payload[$this->resource] ?? [], $limit, $offset);

        return $this->models;
    }

    private function fetchTotalCount(): int
    {
        $payload = $this->fetch(1, 0);

        return (int) ($payload['total'] ?? 0);
    }

    public function getAllData()
    {
        // dummyjson treats limit=0 as "return every record" (used for export).
        $payload = $this->fetch(0, 0);

        return $this->buildRows($payload[$this->resource] ?? [], 0, 0);
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function buildRows(array $items, int $pageSize, int $offset): ArrayCollection
    {
        $rows = new ArrayCollection();

        foreach ($items as $key => $item) {
            $row = new Row($key, $pageSize, $offset);
            $row->data = $item;

            $event = new RowEvent();
            $event->row = $row;
            $this->eventDispatcher->dispatch($event, RowEvent::BEFORE_ROW);
            $rows->add($row);
            $this->eventDispatcher->dispatch($event, RowEvent::AFTER_ROW);
        }

        return $rows;
    }

    /**
     * @return array{users?: list<array<string, mixed>>, total?: int}
     */
    private function fetch(int $limit, int $offset): array
    {
        $query = ['limit' => $limit, 'skip' => $offset];

        $url = $this->baseUri . '/' . $this->resource;
        if (null !== $this->searchTerm && '' !== $this->searchTerm) {
            $url .= '/search';
            $query['q'] = $this->searchTerm;
        }

        [$field, $direction] = $this->firstSortOrder();
        if (null !== $field) {
            $query['sortBy'] = $field;
            $query['order'] = $direction;
        }

        return $this->httpClient->request('GET', $url, ['query' => $query])->toArray();
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private function firstSortOrder(): array
    {
        foreach ($this->getSort()->fetchOrders() as $field => $direction) {
            return [$field, $direction];
        }

        return [null, 'asc'];
    }
}
