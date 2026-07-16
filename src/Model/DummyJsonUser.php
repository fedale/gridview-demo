<?php

namespace App\Model;

/**
 * Marker class only: dummyjson.com/users rows are plain arrays fetched over
 * HTTP, never hydrated as objects. It exists solely so
 * DummyJsonUserController::getDataClass() has an FQCN to derive the grid id
 * from (see AbstractGridController::defaultConfig()), the same convention
 * used for Doctrine-backed grids.
 */
final class DummyJsonUser
{
}
