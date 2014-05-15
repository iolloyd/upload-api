<?php

namespace Cloud\Model;

/**
 * Basis for all model classes
 */
abstract class AbstractModel
{
    /**
     * Forbid serialization of doctrine entities
     *
     * To serialize entities with Doctrine you have to pay attention to get it
     * working. Rather than risking errors code, we forbid serialization.
     * Instead, you should store the entity's identifier and refetch it from
     * the database each time. Caching should negate any performance
     * differences.
     *
     * > Serializing entities can be problematic and is not really recommended,
     * > at least not as long as an entity instance still holds references to
     * > proxy objects or is still managed by an EntityManager. If you intend
     * > to serialize (and unserialize) entity instances that still hold
     * > references to proxy objects you may run into problems with private
     * > properties because of technical limitations...
     *
     * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/architecture.html#serializing-entities
     */
    final public function serialize()
    {
        trigger_error(sprintf(
            '%s: Doctrine entities cannot be serialized. Instead, store '
            . 'only the identifier and retrieve a fresh copy from the '
            . 'database.',
            get_called_class()
        ), E_USER_ERROR);

        return null;
    }

    /**
     * Ignore unserialize of doctrine entities
     */
    final public function unserialize($data)
    {
    }
}
