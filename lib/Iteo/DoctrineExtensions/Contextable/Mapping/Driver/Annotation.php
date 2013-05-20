<?php

namespace Iteo\DoctrineExtensions\Contextable\Mapping\Driver;

use Iteo\DoctrineExtensions\Mapping\Driver\AbstractAnnotationDriver,
    Doctrine\Common\Annotations\AnnotationReader;

/**
 * This is a annotation mapping driver for Contextable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Contextable
 * extension.
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        return;
    }
}
