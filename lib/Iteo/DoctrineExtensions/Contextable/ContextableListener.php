<?php

namespace Iteo\DoctrineExtensions\Contextable;

use Doctrine\Common\EventArgs;
use Doctrine\Common\NotifyPropertyChanged;
use Iteo\DoctrineExtensions\Mapping\MappedEventSubscriber;
use Iteo\DoctrineExtensions\Exception\InvalidArgumentException;
use Iteo\DoctrineExtensions\Contextable\Mapping\Event\ContextableAdapter;

/**
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ContextableListener extends MappedEventSubscriber
{
    protected $contextObject = null;
    protected $contextName;

    public function __construct($contextName)
    {
        parent::__construct();
        $this->contextName = $contextName;
    }

    public function getContextName()
    {
        return $this->contextName;
    }

    public function getContextObject()
    {
        return $this->contextObject;
    }

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'loadClassMetadata'
        );
    }

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Get the contextObject value to set on a contextable field
     *
     * @param object $meta
     * @param string $field
     * @return mixed
     */
    public function getContext($meta, $field)
    {
        if ($meta->hasAssociation($field)) {
            if (null !== $this->contextObject && !is_object($this->contextObject) && !is_integer($this->contextObject)) {
                throw new InvalidArgumentException("Contexting is reference, `contextObject` must be an object or integer");
            }

            return $this->contextObject;
        }

        // ok so its not an association, then it is a string
        if (is_object($this->contextObject)) {
            if (method_exists($this->contextObject, 'getId')) {
                return $this->contextObject->getId();
            }

            throw new InvalidArgumentException("Field expects integer, contextObject object should have method getId");
        }

        return $this->contextObject;
    }

    /**
     * Set a contextObject value to return
     *
     * @return mixed
     */
    public function setContext($contextObject)
    {
        $this->contextObject = $contextObject;
    }

    /**
     * Checks for persisted Contextable objects
     * to update creation
     *
     * @param EventArgs $args
     * @return void
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();

        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->getName())) {
            if (isset($config['contextable']) && isset($config['contextable'][$this->contextName])) {
                $field = $config['contextable'][$this->contextName];
                if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                    $this->updateField($object, $ea, $meta, $field);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Updates a field
     *
     * @param mixed $object
     * @param ContextableAdapter $ea
     * @param $meta
     * @param $field
     */
    protected function updateField($object, $ea, $meta, $field)
    {
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $newValue = $this->getContext($meta, $field);

        if ($newValue !== null) {
            $property->setValue($object, $newValue);
            if ($object instanceof NotifyPropertyChanged) {
                $uow = $ea->getObjectManager()->getUnitOfWork();
                $uow->propertyChanged($object, $field, $oldValue, $newValue);
            }
        }
    }
}
