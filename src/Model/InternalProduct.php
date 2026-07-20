<?php

namespace App\Model;

/**
 * Marker class only: rows come from the internal, token-gated JSON API
 * (App\Controller\Api\InternalProductApiController) as plain arrays, never
 * hydrated as objects. It exists solely so InternalProductController::
 * getDataClass() has an FQCN to derive the grid id from ("internalproduct"),
 * the same convention used for Doctrine-backed grids.
 */
final class InternalProduct
{
}
