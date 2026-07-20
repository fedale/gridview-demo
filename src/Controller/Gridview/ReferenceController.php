<?php

namespace App\Controller\Gridview;

use App\Model\Reference;
use App\Service\ReferenceLookup;
use Fedale\GridviewBundle\Controller\AbstractGridController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only grid backed by the remote, token-gated reference API
 * (https://test-reference.waltertosto.it/api/references) via the bundle's
 * built-in Fedale\GridviewBundle\DataProvider\JsonDataProvider. It is the
 * PHP/Gridview counterpart of the Angular "reference" screen.
 *
 * This is a deliberately simple first version: it renders only the "column"
 * category. The category is not a user-facing column filter (the JsonDataProvider
 * does not push column filters to the API yet) but a fixed request parameter, so
 * it travels as the static query param `category=column` set in dataConfig().
 * The endpoint returns a plain JSON array with no server-side total, so the
 * provider fetches the list once and pages it in memory.
 *
 * Related resources arrive as IRIs (endUser => "/api/reference/end_users/48",
 * materialsRel => ["/api/reference/materials/9"]); ReferenceLookup resolves them
 * to names, the same way the Angular grid resolves them through its cached
 * services. The provider is selected in config/packages/gridview.yaml
 * (`gridviews.reference.dataProvider`); the Bearer token comes from the
 * REFERENCE_API_TOKEN env var and is never hard-coded.
 */
#[Route('/gridview/reference', name: 'gridview_reference_')]
class ReferenceController extends AbstractGridController
{
    /**
     * Scalar attributes the user can sort by. Only plain row keys are listed:
     * the relation columns (endUser, epc, licensor, materials*Rel) display names
     * resolved from IRIs, so ordering by their raw value would sort by IRI, not
     * by the visible label — they stay non-sortable in this first version.
     *
     * @var list<string>
     */
    private const SORTABLE = [
        'id', 'internalNumber', 'year', 'plantType', 'plantName', 'location',
        'description', 'manufacturer', 'qty', 'code', 'sizeDiam1', 'thk',
        'thkOverlay', 'sizeTl', 'desPress', 'desTempMin', 'desTempMax',
        'materials', 'weight',
    ];

    public function __construct(
        private readonly ReferenceLookup $lookup,
        #[Autowire('%env(REFERENCE_API_BASE_URL)%')]
        private readonly string $apiBaseUri,
        #[Autowire('%env(REFERENCE_API_TOKEN)%')]
        private readonly string $apiToken,
    ) {
    }

    protected function getDataClass(): string
    {
        return Reference::class;
    }

    protected function viewConfig(): array
    {
        return [
            'labels' => ['heading' => 'References — Column (remote token-gated API demo)'],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => [
                'baseUri' => $this->apiBaseUri,
                'resource' => 'api/references',
                // Body is a plain JSON array (no wrapper) and carries no total,
                // so the provider fetches everything once and pages in memory.
                'listPath' => null,
                'totalPath' => null,
                'headers' => ['Authorization' => 'Bearer '.$this->apiToken],
                // Fixed per-grid filter: this first version only shows columns.
                'query' => ['category' => 'column'],
            ],
            'pagination' => ['defaultPageSize' => 20],
            // The endpoint does not sort server-side, so sorting is done in memory
            // by the JsonDataProvider. The map is declared in the expanded format
            // (['asc' => ['field' => 'asc']]) so the sort field resolves to the
            // plain row key the provider orders by.
            'sort' => ['map' => $this->sortMap()],
        ];
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private function sortMap(): array
    {
        $map = [];
        foreach (self::SORTABLE as $attribute) {
            $map[$attribute] = [
                'asc' => [$attribute => 'asc'],
                'desc' => [$attribute => 'desc'],
            ];
        }

        return $map;
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $columns = [
            // Id and year are identifiers, not quantities: keep them as plain text
            // so the number type does not add thousands separators (2,020 / 710,101).
            ['attribute' => 'id', 'label' => 'Id'],
            ['attribute' => 'internalNumber', 'label' => 'Internal number'],
            ['attribute' => 'year', 'label' => 'Year'],
            [
                'attribute' => 'endUser',
                'label' => 'End user',
                'value' => fn (array $row): string => $this->lookup->endUserName($row['endUser'] ?? null),
            ],
            [
                'attribute' => 'epc',
                'label' => 'EPC',
                'value' => fn (array $row): string => $this->lookup->epcName($row['epc'] ?? null),
            ],
            [
                'attribute' => 'licensor',
                'label' => 'Licensor',
                'value' => fn (array $row): string => $this->lookup->licensorName($row['licensor'] ?? null),
            ],
            ['attribute' => 'plantType', 'label' => 'Plant type'],
            ['attribute' => 'plantName', 'label' => 'Plant name'],
            ['attribute' => 'location', 'label' => 'Location'],
            ['attribute' => 'description', 'label' => 'Description'],
            ['attribute' => 'manufacturer', 'label' => 'Manufacturer'],
            ['attribute' => 'qty', 'label' => 'Qty', 'type' => 'number'],
            ['attribute' => 'code', 'label' => 'Code'],
            ['attribute' => 'sizeDiam1', 'label' => 'Size diam. 1', 'type' => 'number'],
            ['attribute' => 'thk', 'label' => 'Thk', 'type' => 'number'],
            ['attribute' => 'thkOverlay', 'label' => 'Thk overlay', 'type' => 'number'],
            ['attribute' => 'sizeTl', 'label' => 'Size TL', 'type' => 'number'],
            ['attribute' => 'desPress', 'label' => 'Des. press', 'type' => 'number'],
            ['attribute' => 'desTempMin', 'label' => 'Des. temp min', 'type' => 'number'],
            ['attribute' => 'desTempMax', 'label' => 'Des. temp max', 'type' => 'number'],
            ['attribute' => 'materials', 'label' => 'Materials (legacy)'],
            [
                'attribute' => 'materialsRel',
                'label' => 'Materials',
                'value' => fn (array $row): string => $this->lookup->materialNames($row['materialsRel'] ?? null),
            ],
            [
                'attribute' => 'materialOverlayRel',
                'label' => 'Material overlay',
                'value' => fn (array $row): string => $this->lookup->materialName($row['materialOverlayRel'] ?? null),
            ],
            [
                'attribute' => 'materialCladRel',
                'label' => 'Material clad',
                'value' => fn (array $row): string => $this->lookup->materialName($row['materialCladRel'] ?? null),
            ],
            ['attribute' => 'weight', 'label' => 'Weight', 'type' => 'number'],
        ];

        // Single source of truth for what can be sorted: flag every scalar column
        // listed in SORTABLE, leaving the IRI-resolved relation columns untouched.
        return array_map(
            static function (array $column): array {
                if (\in_array($column['attribute'], self::SORTABLE, true)) {
                    $column['sortable'] = true;
                }

                return $column;
            },
            $columns,
        );
    }
}
