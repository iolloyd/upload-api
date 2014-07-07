<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

namespace Cloud\Doctrine\Internal;

use Cloud\Doctrine\Exception;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Doctrine ClassMetadata extensions and utilities
 */
class ClassMetadataUtils
{
    /**
     * Set a class level configuration value in the given metadata
     *
     * @param  ClassMetadata $metadata
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public static function setMetadataClassFlag(ClassMetadata $metadata, $key, $value = true)
    {
        static::prepareClassMetadata($metadata);

        $metadata->table['options'][$key] = $value;
    }

    /**
     * Get a class level configuration value from the given metadata
     *
     * @param  ClassMetadata $metadata
     * @param  string $key
     * @return mixed
     */
    public static function getMetadataClassFlag(ClassMetadata $metadata, $key)
    {
        static::prepareClassMetadata($metadata);

        if (!array_key_exists($key, $metadata->table['options'])) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s(): Class metadata flag "%s" not found for "%s"',
                __METHOD__,
                $key, $metadata->name
            ));
        }

        return $metadata->table['options'][$key];
    }

    /**
     * Check if the given metadata has a class level configuration value set
     *
     * @param  ClassMetadata $metadata
     * @param  string $key
     * @return bool
     */
    public static function hasMetadataClassFlag(ClassMetadata $metadata, $key)
    {
        static::prepareClassMetadata($metadata);
        return array_key_exists($key, $metadata->table['options']);
    }

    /**
     * Remove a class level configuration value from the given metadata
     *
     * @param  ClassMetadata $metadata
     * @param  string $key
     * @return void
     */
    public static function unsetMetadataClassFlag(ClassMetadata $metadata, $key)
    {
        static::prepareClassMetadata($metadata);

        if (array_key_exists($key, $metadata->table['options'])) {
            unset($metadata->table['options'][$key]);
        }
    }

    /**
     * Set a field level configuration value in the given metadata
     *
     * @param  ClassMetadata $metadata
     * @param  string $fieldName
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public static function setMetadataFieldFlag(ClassMetadata $metadata, $fieldName, $key, $value = true)
    {
        if (array_key_exists($fieldName, $metadata->fieldMappings)) {
            $metadata->fieldMappings[$fieldName][$key] = $value;
        } elseif (array_key_exists($fieldName, $metadata->associationMappings)) {
            $metadata->associationMappings[$fieldName][$key] = $value;
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s(): Field "%s" of "%s" does not exist',
                __METHOD__,
                $fieldName, $metadata->name
            ));
        }
    }

    /**
     * Get a field level configuration value from the given metadata
     *
     * @param  ClassMetadata $metadata
     * @param  string $fieldName
     * @param  string $key
     * @return mixed
     */
    public static function getMetadataFieldFlag(ClassMetadata $metadata, $fieldName, $key)
    {
        if (array_key_exists($fieldName, $metadata->fieldMappings)
            && array_key_exists($key, $metadata->fieldMappings[$fieldName])
        ) {
            return $metadata->fieldMappings[$fieldName][$key];
        } elseif (array_key_exists($fieldName, $metadata->associationMappings)
            && array_key_exists($key, $metadata->associationMappings[$fieldName])
        ) {
            return $metadata->associationMappings[$fieldName][$key];
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s(): Field metadata flag "%s" not found for field "%s" of "%s"',
                __METHOD__,
                $key, $fieldName, $metadata->name
            ));
        }
    }

    /**
     * Check if the given metadata has a field level configuration value set
     *
     * @param  ClassMetadata $metadata
     * @param  string $fieldName
     * @param  string $key
     * @return bool
     */
    public static function hasMetadataFieldFlag(ClassMetadata $metadata, $fieldName, $key)
    {
        return (array_key_exists($fieldName, $metadata->fieldMappings)
                && array_key_exists($key, $metadata->fieldMappings[$fieldName]))
            || (array_key_exists($fieldName, $metadata->associationMappings)
                && array_key_exists($key, $metadata->associationMappings[$fieldName]));
    }

    /**
     * Remove a field level configuration value from the given metadata
     *
     * @param  ClassMetadata $metadata
     * @param  string $fieldName
     * @param  string $key
     * @return void
     */
    public static function unsetMetadataFieldFlag(ClassMetadata $metadata, $fieldName, $key)
    {
        if (array_key_exists($fieldName, $metadata->fieldMappings)
            && array_key_exists($key, $metadata->fieldMappings[$fieldName])
        ) {
            unset($metadata->fieldMappings[$fieldName][$key]);
        } elseif (array_key_exists($fieldName, $metadata->associationMappings)
            && array_key_exists($key, $metadata->associationMappings[$fieldName])
        ) {
            unset($metadata->associationMappings[$fieldName][$key]);
        }
    }

    /**
     * Prepares the metadata object to store class options
     *
     * @param  ClassMetadata $metadata
     * @return void
     */
    protected static function prepareClassMetadata(ClassMetadata $metadata)
    {
        if (!isset($metadata->table['options'])) {
            $metadata->table['options'] = [];
        }
    }
}
