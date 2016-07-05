<?php
namespace ITF\AdminBundle\Admin\Form\Search\Operators;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use ITF\AdminBundle\Admin\Form\Search\AbstractSearchOperator;
use ITF\AdminBundle\Admin\Form\Search\SearchFieldMapping;

class BetweenSearchOperator extends AbstractSearchOperator implements SearchOperatorInterface, AddParameterInterface
{
    public function __construct()
    {
        $this->setLabel('BETWEEN');
    }

    /**
     * @param SearchFieldMapping $searchFieldMapping
     * @param string $alias
     *
     * @return $this
     */
    public function setSearchFieldMapping(SearchFieldMapping $searchFieldMapping, $alias = 'a')
    {
        $this->setAlias($alias);
        $this->setAttribute($searchFieldMapping->getFieldName());
        $this->setValue($searchFieldMapping->getRequestValue());

        return $this;
    }

    public function isEmpty()
    {
        $empty = true;

        if (is_array($this->getValue())) {
            foreach ($this->getValue() as $value) {
                if (strlen($value) > 0) {
                    $empty = false;
                }
            }
        }

        return $empty;
    }

    public function setValue($dates)
    {
        $this->value = $dates;

        if (!$this->isEmpty()) {
            foreach ($dates as &$date) {
                $_date = new \DateTime($date);
                $date = $_date->format('Y-m-d H:i:s');
            }
        }

        $this->value = $dates;
    }

    /**
     * @return OperatorExpression
     */
    public function getExpr()
    {
        return SearchFieldMapping::OPERATOR_USE_STRING;
    }

    public function getExpressionString()
    {
        return $this->getAliasedAttribute() . ' BETWEEN ' . $this->getParameters()[0] . ' AND ' . $this->getParameters()[1];
    }

    public function addParameters(QueryBuilder &$queryBuilder)
    {
        $queryBuilder->setParameters(array(
            $this->getParameters()[0] => $this->getValue()[0],
            $this->getParameters()[1] => $this->getValue()[1],
        ));
    }

    public function getParameters()
    {
        return array(
            ':' . $this->getAttribute().'1',
            ':' . $this->getAttribute().'2'
        );
    }
}