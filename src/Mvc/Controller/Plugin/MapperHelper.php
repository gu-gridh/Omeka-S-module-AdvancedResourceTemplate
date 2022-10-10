<?php declare(strict_types=1);

/*
 * Copyright 2017-2020 Daniel Berthereau
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software. You can use, modify and/or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software’s author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user’s attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software’s suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace AdvancedResourceTemplate\Mvc\Controller\Plugin;

use Doctrine\DBAL\Connection;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Omeka\DataType\Manager as DataTypeManager;

/**
 * @deprecated Replace with bulk helper (partially).
 *
 * @see \BulkImport\Mvc\Controller\Plugin\Bulk
 */
class MapperHelper extends AbstractPlugin
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var DataTypeManager
     */
    protected $dataTypeManager;

    /**
     * Associative array of property ids by terms.
     *
     * @var array
     */
    protected $properties;

    /**
     * Associative array of resource classes ids by terms.
     *
     * @var array
     */
    protected $resourceClasses;

    /**
     * Associative array of resource template ids by label.
     *
     * @var array
     */
    protected $resourceTemplates;

    /**
     * Associative array of data types by themselves.
     *
     * @var array
     */
    protected $dataTypes;

    /**
     * @todo Can be replaced by \BulkImport\Mvc\Controller\Plugin\Bulk
     *
     * @param Connection $connection
     * @param DataTypeManager $dataTypeManager
     */
    public function __construct(Connection $connection, DataTypeManager $dataTypeManager)
    {
        $this->connection = $connection;
        $this->dataTypeManager = $dataTypeManager;
    }

    /**
     * Manage various methods to manage properties and resource classes.
     *
     * @return self
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Check if a string or a id is a managed term.
     *
     * @param string|int $termOrId
     * @return bool
     */
    public function isPropertyTerm($termOrId)
    {
        return $this->getPropertyId($termOrId) !== null;
    }

    /**
     * Get a property id by term or id.
     *
     * @param string|int $termOrId
     * @return int|null
     */
    public function getPropertyId($termOrId)
    {
        $ids = $this->getPropertyIds();
        return is_numeric($termOrId)
            ? (array_search($termOrId, $ids) ? $termOrId : null)
            : ($ids[$termOrId] ?? null);
    }

    /**
     * Get a property term by term or id.
     *
     * @param string|int $termOrId
     * @return string|null
     */
    public function getPropertyTerm($termOrId)
    {
        $ids = $this->getPropertyIds();
        return is_numeric($termOrId)
            ? (array_search($termOrId, $ids) ?: null)
            : (array_key_exists($termOrId, $ids) ? $termOrId : null);
    }

    /**
     * Get a property label by term or id.
     *
     * @param string|int $termOrId
     * @return string|null
     */
    public function getPropertyLabel($termOrId)
    {
        $term = $this->getPropertyTerm($termOrId);
        return $term
            ? $this->getPropertyLabels()[$term]
            : null;
    }

    /**
     * Get all property ids by term.
     *
     * @return array Associative array of ids by term.
     */
    public function getPropertyIds()
    {
        if (isset($this->properties)) {
            return $this->properties;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'DISTINCT CONCAT(vocabulary.prefix, ":", property.local_name) AS term',
                'property.id AS id',
                // Only the two first selects are needed, but some databases
                // require "order by" or "group by" value to be in the select,
                // in particular to fix the "only_full_group_by" issue.
                'vocabulary.id',
                'property.id'
            )
            ->from('property', 'property')
            ->innerJoin('property', 'vocabulary', 'vocabulary', 'property.vocabulary_id = vocabulary.id')
            ->orderBy('vocabulary.id', 'asc')
            ->addOrderBy('property.id', 'asc')
            ->addGroupBy('property.id')
        ;
        $this->properties = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        return $this->properties;
    }

    /**
     * Get all property terms by id.
     *
     * @return array Associative array of terms by id.
     */
    public function getPropertyTerms()
    {
        return array_flip($this->getPropertyIds());
    }

    /**
     * Get all property local labels by term.
     *
     * @return array Associative array of labels by term.
     */
    public function getPropertyLabels()
    {
        static $propertyLabels;

        if (is_array($propertyLabels)) {
            return $propertyLabels;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'DISTINCT CONCAT(vocabulary.prefix, ":", property.local_name) AS term',
                'property.label AS label',
                // Only the two first selects are needed, but some databases
                // require "order by" or "group by" value to be in the select,
                // in particular to fix the "only_full_group_by" issue.
                'vocabulary.id',
                'property.id'
            )
            ->from('property', 'property')
            ->innerJoin('property', 'vocabulary', 'vocabulary', 'property.vocabulary_id = vocabulary.id')
            ->orderBy('vocabulary.id', 'asc')
            ->addOrderBy('property.id', 'asc')
            ->addGroupBy('property.id')
        ;
        $propertyLabels = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        return $propertyLabels;
    }

    /**
     * Check if a string or a id is a resource class.
     *
     * @param string|int $termOrId
     * @return bool
     */
    public function isResourceClass($termOrId)
    {
        return $this->getResourceClassId($termOrId) !== null;
    }

    /**
     * Get a resource class by term or by id.
     *
     * @param string|int $termOrId
     * @return int|null
     */
    public function getResourceClassId($termOrId)
    {
        $ids = $this->getResourceClassIds();
        return is_numeric($termOrId)
            ? (array_search($termOrId, $ids) ? $termOrId : null)
            : ($ids[$termOrId] ?? null);
    }

    /**
     * Get a resource class term by term or id.
     *
     * @param string|int $termOrId
     * @return string|null
     */
    public function getResourceClassTerm($termOrId)
    {
        $ids = $this->getResourceClassIds();
        return is_numeric($termOrId)
            ? (array_search($termOrId, $ids) ?: null)
            : (array_key_exists($termOrId, $ids) ? $termOrId : null);
    }

    /**
     * Get a resource class label by term or id.
     *
     * @param string|int $termOrId
     * @return string|null
     */
    public function getResourceClassLabel($termOrId)
    {
        $term = $this->getResourceClassTerm($termOrId);
        return $term
            ? $this->getResourceClassLabels()[$term]
            : null;
    }

    /**
     * Get all resource class ids by term.
     *
     * @return array Associative array of ids by term.
     */
    public function getResourceClassIds()
    {
        if (isset($this->resourceClasses)) {
            return $this->resourceClasses;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'DISTINCT CONCAT(vocabulary.prefix, ":", resource_class.local_name) AS term',
                'resource_class.id AS id',
                // Only the two first selects are needed, but some databases
                // require "order by" or "group by" value to be in the select,
                // in particular to fix the "only_full_group_by" issue.
                'vocabulary.id',
                'resource_class.id'
            )
            ->from('resource_class', 'resource_class')
            ->innerJoin('resource_class', 'vocabulary', 'vocabulary', 'resource_class.vocabulary_id = vocabulary.id')
            ->orderBy('vocabulary.id', 'asc')
            ->addOrderBy('resource_class.id', 'asc')
            ->addGroupBy('resource_class.id')
        ;
        $this->resourceClasses = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        return $this->resourceClasses;
    }

    /**
     * Get all resource class terms by id.
     *
     * @return array Associative array of terms by id.
     */
    public function getResourceClassTerms()
    {
        return array_flip($this->getResourceClassIds());
    }

    /**
     * Get all resource class labels by term.
     *
     * @return array Associative array of resource class labels by term.
     */
    public function getResourceClassLabels()
    {
        static $resourceClassLabels;

        if (is_array($resourceClassLabels)) {
            return $resourceClassLabels;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'DISTINCT CONCAT(vocabulary.prefix, ":", resource_class.local_name) AS term',
                'resource_class.label AS label',
                // Only the two first selects are needed, but some databases
                // require "order by" or "group by" value to be in the select,
                // in particular to fix the "only_full_group_by" issue.
                'vocabulary.id',
                'resource_class.id'
            )
            ->from('resource_class', 'resource_class')
            ->innerJoin('resource_class', 'vocabulary', 'vocabulary', 'resource_class.vocabulary_id = vocabulary.id')
            ->orderBy('vocabulary.id', 'asc')
            ->addOrderBy('resource_class.id', 'asc')
            ->addGroupBy('resource_class.id')
        ;
        $resourceClassLabels = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        return $resourceClassLabels;
    }

    /**
     * Check if a string or a id is a resource template.
     *
     * @param string|int $labelOrId
     * @return bool
     */
    public function isResourceTemplate($labelOrId)
    {
        return $this->getResourceTemplateId($labelOrId) !== null;
    }

    /**
     * Get a resource template by label or by id.
     *
     * @param string|int $labelOrId
     * @return int|null
     */
    public function getResourceTemplateId($labelOrId)
    {
        $ids = $this->getResourceTemplateIds();
        return is_numeric($labelOrId)
            ? (array_search($labelOrId, $ids) ? $labelOrId : null)
            : ($ids[$labelOrId] ?? null);
    }

    /**
     * Get a resource template label by label or id.
     *
     * @param string|int $labelOrId
     * @return string|null
     */
    public function getResourceTemplateLabel($labelOrId)
    {
        $ids = $this->getResourceTemplateIds();
        return is_numeric($labelOrId)
            ? (array_search($labelOrId, $ids) ?: null)
            : (array_key_exists($labelOrId, $ids) ? $labelOrId : null);
    }

    /**
     * Get all resource templates by label.
     *
     * @return array Associative array of ids by label.
     */
    public function getResourceTemplateIds()
    {
        if (isset($this->resourceTemplates)) {
            return $this->resourceTemplates;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'resource_template.label AS label',
                'resource_template.id AS id'
            )
            ->from('resource_template', 'resource_template')
            ->orderBy('resource_template.id', 'asc')
        ;
        $this->resourceTemplates = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        return $this->resourceTemplates;
    }

    /**
     * Get all resource template labels by id.
     *
     * @return array Associative array of labels by id.
     */
    public function getResourceTemplateLabels()
    {
        return array_flip($this->getResourceTemplateIds());
    }

    /**
     * Check if a string is a managed data type.
     *
     * @param string $dataType
     * @return bool
     */
    public function isDataType($dataType)
    {
        return array_key_exists($dataType, $this->getDataTypes());
    }

    /**
     * @param string $dataType
     * @return string|null
     */
    public function getDataType($dataType)
    {
        $dataTypes = $this->getDataTypes();
        return $dataTypes[$dataType] ?? null;
    }

    /**
     * Get array of data types by themselves.
     *
     * @return array Associative array of data types by themselves.
     */
    public function getDataTypes()
    {
        if (isset($this->dataTypes)) {
            return $this->dataTypes;
        }

        $this->dataTypes = $this->dataTypeManager->getRegisteredNames();
        $this->dataTypes = array_combine($this->dataTypes, $this->dataTypes);
        return $this->dataTypes;
    }
}
