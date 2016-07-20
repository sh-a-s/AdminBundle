<?php
namespace ITF\AdminBundle\Admin\Form\Search;

use Doctrine\Common\Collections\ArrayCollection;

class SearchAssociationFieldMapping extends SearchFieldMapping
{
    /** @var string Association Object Class */
    private $targetEntity;

    /** @var string Parent Object Class */
    private $sourceEntity;

    /** @var string */
    private $inversedBy;

    /** @var string */
    private $joinColumnName;

    /** @var boolean */
    private $joinColumnNullable;
    
    /** @var ArrayCollection */
    private $fieldMappings;

    public static function create($array)
    {
        $safm = new self;

        $safm->setFieldName(self::tryOrDefault(@$array['fieldName']));
        $safm->setColumnName(self::tryOrDefault(@$array['fieldName']));
        $safm->setJoinColumnName(self::tryOrDefault(@$array['joinColumns'][0]['name']));
        $safm->setJoinColumnNullable(self::tryOrDefault(@$array['joinColumns'][0]['nullable']));
        $safm->setTargetEntity(self::tryOrDefault(@$array['targetEntity']));
        $safm->setSourceEntity(self::tryOrDefault(@$array['sourceEntity']));
        $safm->setInversedBy(self::tryOrDefault(@$array['inversedBy']));

        return $safm;
    }

    private static function tryOrDefault($value, $default = NULL)
    {
        if ($value !== NULL) {
            return $value;
        }

        return $default;
    }

    /**
     * @return string
     */
    public function getTargetEntity()
    {
        return $this->targetEntity;
    }

    /**
     * @param string $targetEntity
     *
     * @return SearchAssociationFieldMapping
     */
    public function setTargetEntity($targetEntity)
    {
        $this->targetEntity = $targetEntity;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceEntity()
    {
        return $this->sourceEntity;
    }

    /**
     * @param string $sourceEntity
     *
     * @return SearchAssociationFieldMapping
     */
    public function setSourceEntity($sourceEntity)
    {
        $this->sourceEntity = $sourceEntity;

        return $this;
    }

    /**
     * @return string
     */
    public function getInversedBy()
    {
        return $this->inversedBy;
    }

    /**
     * @param string $inversedBy
     *
     * @return SearchAssociationFieldMapping
     */
    public function setInversedBy($inversedBy)
    {
        $this->inversedBy = $inversedBy;

        return $this;
    }

    /**
     * @return string
     */
    public function getJoinColumnName()
    {
        return $this->joinColumnName;
    }

    /**
     * @param string $joinColumnName
     *
     * @return SearchAssociationFieldMapping
     */
    public function setJoinColumnName($joinColumnName)
    {
        $this->joinColumnName = $joinColumnName;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isJoinColumnNullable()
    {
        return $this->joinColumnNullable;
    }

    /**
     * @param boolean $joinColumnNullable
     *
     * @return SearchAssociationFieldMapping
     */
    public function setJoinColumnNullable($joinColumnNullable)
    {
        $this->joinColumnNullable = $joinColumnNullable;

        return $this;
    }
    
    /**
     * @return ArrayCollection
     */
    public function getFieldMappings()
    {
        return $this->fieldMappings;
    }
    
    /**
     * @param ArrayCollection $fieldMappings
     *
     * @return SearchAssociationFieldMapping
     */
    public function setFieldMappings($fieldMappings)
    {
        $this->fieldMappings = $fieldMappings;
        
        return $this;
    }
}