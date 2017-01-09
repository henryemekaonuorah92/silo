<?php

namespace Silo\Base\Provider\DoctrineProvider;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Prefix tables from the Silo namespace.
 */
class TablePrefix
{
    /** @var string */
    protected $prefix = '';

    /**
     * @param $prefix Prefix to apply on Silo tables
     */
    public function __construct($prefix)
    {
        $this->prefix = (string) $prefix;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (stripos($classMetadata->namespace, 'Silo\\') !== 0) {
            return;
        }

        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->getName() === $classMetadata->rootEntityName) {
            $classMetadata->setTableName($this->prefix.$classMetadata->getTableName());
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY && $mapping['isOwningSide']) {
                $mappedTableName = $mapping['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix.$mappedTableName;
            }
        }
    }
}
