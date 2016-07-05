<?php
namespace ITF\AdminBundle\Admin\Form\Search;

abstract class AbstractSearchOperator
{
    /** @var string */
    protected $label;
    
    /** @var string */
    protected $alias;

    /** @var string */
    protected $attribute;

    /** @var mixed */
    protected $value;

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return AbstractSearchOperator
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }
    
    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
    
    /**
     * @param string $alias
     *
     * @return AbstractSearchOperator
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param string $attribute
     *
     * @return AbstractSearchOperator
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getAliasedAttribute()
    {
        return $this->getAlias() . '.' . $this->getAttribute();
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return AbstractSearchOperator
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function __toString()
    {
        return (string) $this->getLabel();
    }
}