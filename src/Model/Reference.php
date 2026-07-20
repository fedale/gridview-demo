<?php

namespace App\Model;

/**
 * Marker class only: rows come from the remote, token-gated reference API
 * (https://test-reference.waltertosto.it/api/references) as plain arrays, never
 * hydrated as objects. It exists solely so ReferenceController::getDataClass()
 * has an FQCN to derive the grid id from ("reference"), the same convention used
 * for Doctrine-backed grids.
 *
 * This is the PHP/Gridview counterpart of the Angular "reference" grid: this
 * first version renders only the "column" category (the request always carries
 * category=column as a static query param).
 */
final class Reference
{
}
