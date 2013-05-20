<?php

namespace Iteo\DoctrineExtensions\Contextable\Mapping\Driver;

use Iteo\DoctrineExtensions\Mapping\Driver\Xml as BaseXml,
    Iteo\DoctrineExtensions\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Contextable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Contextable
 * extension.
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{

    /**
     * List of types which are valid for blame
     *
     * @var array
     */
    private $validTypes = array(
        'one',
        'int',
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement $mapping
         */
        $xmlDoctrine = $this->_getMapping($meta->name);

        if (isset($xmlDoctrine->field)) {
            $this->inspectElementForContextable($xmlDoctrine->field, $config, $meta);
        }
        if (isset($xmlDoctrine->{'many-to-one'})) {
            $this->inspectElementForContextable($xmlDoctrine->{'many-to-one'}, $config, $meta);
        }
        if (isset($xmlDoctrine->{'one-to-one'})) {
            $this->inspectElementForContextable($xmlDoctrine->{'one-to-one'}, $config, $meta);
        }
    }

    /**
     * Searches mappings on element for contextable fields
     *
     * @param SimpleXMLElement $element
     * @param array $config
     * @param object $meta
     */
    private function inspectElementForContextable(\SimpleXMLElement $element, array &$config, $meta)
    {
        foreach ($element as $mapping) {
            $mappingDoctrine = $mapping;
            /**
             * @var \SimpleXmlElement $mapping
             */
            $mapping = $mapping->children(self::ITEO_NAMESPACE_URI);

            $isAssoc = $this->_isAttributeSet($mappingDoctrine, 'field');
            $field = $this->_getAttribute($mappingDoctrine, $isAssoc ? 'field' : 'name');

            if (isset($mapping->contextable)) {
                if ($isAssoc && !$meta->associationMappings[$field]['isOwningSide']) {
                    throw new InvalidMappingException("Cannot assign context [{$field}] as it is not the owning side in object - {$meta->name}");
                }
                /**
                 * @var \SimpleXmlElement $data
                 */
                $data = $mapping->contextable;

                if (!$isAssoc && !$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'int' or a reference in class - {$meta->name}");
                }
                if (!$this->_isAttributeSet($data, 'context')) {
                    throw new InvalidMappingException("Field - [{$field}] context is not valid and must be 'string' in class - {$meta->name}");
                }
                $config['contextable'][$this->_getAttribute($data, 'context')] = $field;
            }
        }
    }
    /**
     * Checks if $field type is valid
     *
     * @param object $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}
