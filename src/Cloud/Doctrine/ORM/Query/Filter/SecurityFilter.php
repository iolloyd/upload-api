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

namespace Cloud\Doctrine\ORM\Query\Filter;

use Cloud\Doctrine\Internal\ClassMetadataUtils as Utils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Filters results for the current company in the security context
 *
 * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/filters.html
 */
class SecurityFilter extends SQLFilter
{
    const FLAG_COMPANY = 'cx:identity:company';

    /**
     * {@inheritDoc}
     */
    public function addFilterConstraint(ClassMetadata $metadata, $targetTableAlias)
    {
        if (!Utils::hasMetadataClassFlag($metadata, self::FLAG_COMPANY)) {
            return '';
        }

        $filter = [];

        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            if (Utils::hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_COMPANY)) {
                $filter[] = $targetTableAlias
                    . '.'
                    . $metadata->getSingleAssociationJoinColumnName($fieldName)
                    . ' = '
                    . $this->getParameter('company_id');
            }
        }

        return implode(' AND ', $filter);
    }
}
