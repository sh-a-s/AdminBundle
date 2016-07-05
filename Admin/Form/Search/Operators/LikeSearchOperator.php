<?php
namespace ITF\AdminBundle\Admin\Form\Search\Operators;

use Doctrine\ORM\Query\Expr\Comparison;
use ITF\AdminBundle\Admin\Form\Search\AbstractSearchOperator;
use ITF\AdminBundle\Admin\Form\Search\Expr\AdminComparison;
use ITF\AdminBundle\Admin\Form\Search\SearchFieldMapping;

class LikeSearchOperator extends AbstractSearchOperator implements SearchOperatorInterface
{
    public function __construct()
    {
        $this->setLabel('LIKE');
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
        return strlen($this->getValue()) == 0;
    }
    
    /**
     * @return OperatorExpression
     */
    public function getExpr()
    {
        if ($this->isEmpty()) {
            return SearchFieldMapping::OPERATOR_EXPR_EMPTY;
        }

        return new OperatorExpression(
            new AdminComparison($this->getAliasedAttribute(), 'LIKE', ':' . $this->getAttribute()),
            $this->getAttribute(),
            $this->getValue()
        );
    }

    public function getExpressionString()
    {
        $expr = $this->getExpr();

        if ($expr !== SearchFieldMapping::OPERATOR_EXPR_EMPTY) {
            return $expr->getExpression()->__toString();
        }

        return SearchFieldMapping::OPERATOR_EXPR_EMPTY;
    }
}