<?php

namespace Iteo\DoctrineExtensions\Contextable\Mapping\Event\Adapter;

use Iteo\DoctrineExtensions\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Iteo\DoctrineExtensions\Contextable\Mapping\Event\ContextableAdapter;

/**
 * Doctrine event adapter for ORM adapted
 * for Contextable behavior.
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements ContextableAdapter
{
}
