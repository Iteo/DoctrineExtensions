<?php

namespace Iteo\DoctrineExtensions\Contextable\Filter;

use Doctrine\ORM\Mapping\ClassMetaData,
    Doctrine\ORM\Query\Filter\SQLFilter,
    Iteo\DoctrineExtensions\Contextable\ContextableListener,
    Iteo\DoctrineExtensions\Exception\InvalidArgumentException;

/**
 * The ContextableFilter adds the condition necessary to
 * filter entities which were assigned to ContextObject
 *
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ContextableFilter extends SQLFilter
{
    protected $listeners = array();
    protected $entityManager;
    protected $disabled = array();

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $class = $targetEntity->getName();
        if (array_key_exists($class, $this->disabled) && $this->disabled[$class] === true) {
            return '';
        } elseif (array_key_exists($targetEntity->rootEntityName, $this->disabled) && $this->disabled[$targetEntity->rootEntityName] === true) {
            return '';
        }

        $listeners = $this->getListeners();
        $sql_parts = array();

        foreach($listeners as $listener) {
            /* @var $listener ContextableListener */
            $config = $listener->getConfiguration($this->getEntityManager(), $targetEntity->name);

            if (!isset($config['contextable']) || !$config['contextable'] || !isset($config['contextable'][$listener->getContextName()])) {
                continue;
            }

            $fieldName = $config['contextable'][$listener->getContextName()];

            $objectId = $listener->getContext($targetEntity, $fieldName);
            if($objectId === null) {
                continue;
            }
            $objectId = !is_object($objectId) ?: $objectId->getId();

            $this->setParameter($listener->getContextName() . '_object', $objectId);
            if(isset($targetEntity->columnNames[$fieldName])) {
                $column = $targetEntity->columnNames[$fieldName];
                $sql_part = $targetTableAlias.'.'.$column.' = ' . $this->getParameter($listener->getContextName() . '_object');
            } else if(isset($targetEntity->associationMappings[$fieldName])) {
                $assocMapping = $targetEntity->associationMappings[$fieldName];

                $sql_part = array();
                foreach($assocMapping['joinColumnFieldNames'] as $key => $column) {
                    $sql_part[] = $targetTableAlias.'.'.$column.' = ' . $this->getParameter($listener->getContextName() . '_object');
                }

                if(empty($sql_part)) {
                    throw new InvalidArgumentException("Cann't assign contextable query (Join column not found)");
                }

                $sql_part = implode(' AND ', $sql_part);
            } else {
                throw new InvalidArgumentException("Unknown exception. [{$fieldName}] isn't a Column or Association");
            }

            $sql_parts[] = $sql_part;
        }

        $sql = implode(' AND ', $sql_parts);

        return $sql;
    }

    public function disableForEntity($class)
    {
        $this->disabled[$class] = true;
    }

    public function enableForEntity($class)
    {
        $this->disabled[$class] = false;
    }

    protected function getListeners()
    {
        if (empty($this->listeners)){
            $em = $this->getEntityManager();
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof ContextableListener) {
                        $this->listeners[] = $listener;
                    }
                }
            }


            if (empty($this->listeners)) {
                throw new \RuntimeException('Listener "ContextableListener" was not added to the EventManager!');
            }
            $this->listeners = array_unique($this->listeners, SORT_REGULAR);
        }

        return $this->listeners;
    }

    protected function getEntityManager()
    {
        if ($this->entityManager === null) {
            $refl = new \ReflectionProperty('Doctrine\ORM\Query\Filter\SQLFilter', 'em');
            $refl->setAccessible(true);
            $this->entityManager = $refl->getValue($this);
        }

        return $this->entityManager;
    }
}
