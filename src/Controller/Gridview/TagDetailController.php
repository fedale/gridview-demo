<?php

namespace App\Controller\Gridview;

use App\Entity\Tag;
use Fedale\GridviewBundle\Controller\AbstractDetailController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only view of one tag, wiring the grid's {show} action.
 *
 * Mounted under /detail rather than directly on the grid's prefix: the base
 * controller's route is `/{id}`, which on a shared prefix would also match
 * `/gridview/tag/new` and `/gridview/tag/bulk/delete` now that an id no longer
 * has to be a number. The route NAME still follows the convention
 * (gridview_tag_show), which is what the grid looks for.
 */
#[Route('/gridview/tag/detail', name: 'gridview_tag_')]
class TagDetailController extends AbstractDetailController
{
    protected function getDataClass(): string
    {
        return Tag::class;
    }

    protected function viewConfig(): array
    {
        return [
            // The bundle's default is the bare key/value <table>, meant to be
            // embedded. This action is reached by a full Turbo visit out of the
            // grid's frame, so it has to answer with a whole page.
            'template' => ['show' => 'gridview/tag/detail.html.twig'],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            ['attribute' => 'id', 'label' => 'Id', 'type' => 'uuid'],
            ['attribute' => 'name', 'label' => 'tag.name'],
        ];
    }
}
