<?php
namespace ITF\AdminBundle\Admin\Form\Search\Operators;

use Doctrine\ORM\Query\Expr\Comparison;
use ITF\AdminBundle\Admin\Form\Search\Expr\AdminComparison;
use ITF\AdminBundle\Admin\Form\Search\SearchFieldMapping;

class GreaterThanSearchOperator extends EqualsSearchOperator implements SearchOperatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->setLabel(Comparison::GT);
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
            new AdminComparison($this->getAliasedAttribute(), Comparison::GT, ':' . $this->getAttribute()),
            $this->getAttribute(),
            $this->getValue()
        );
    }
}