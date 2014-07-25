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

use InvalidArgumentException;
use Cloud\Doctrine\SecurityEventSubscriber;
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
    /**
     * {@inheritDoc}
     */
    public function addFilterConstraint(ClassMetadata $metadata, $targetTableAlias)
    {
        if (!Utils::hasMetadataClassFlag($metadata, SecurityEventSubscriber::FLAG_COMPANY)) {
            return '';
        }

        $filter = [];

        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            if (Utils::hasMetadataFieldFlag($metadata, $fieldName, SecurityEventSubscriber::FLAG_COMPANY)) {
                $companyId = $this->getCompanyId();

                if ($companyId !== null) {
                    $filter[] =
                        $targetTableAlias
                        . '.' . $metadata->getSingleAssociationJoinColumnName($fieldName)
                        . ' = ' . $companyId
                    ;
                } elseif (Utils::getMetadataFieldFlag($metadata, $fieldName, SecurityEventSubscriber::FLAG_ALLOW_ANONYMOUS)) {
                    /*
                     * Don't filter if no company_id set (not logged in) but
                     * allowAnonymous = true
                     */
                    continue;
                } else {
                    $filter[] =
                        $targetTableAlias
                        . '.' . $metadata->getSingleAssociationJoinColumnName($fieldName)
                        . ' IS NULL'
                    ;
                }
            }
        }

        return implode(' AND ', $filter);
    }

    /**
     * @return null|string  quoted value or null if the parameter has not been
     *                       set
     */
    protected function getCompanyId()
    {
        try {
            return $this->getParameter('company_id');
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
