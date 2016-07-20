<?php
namespace ITF\AdminBundle\Admin\Form\Search\Operators;

use Doctrine\ORM\Query\Expr\Comparison;
use ITF\AdminBundle\Admin\Form\Search\Expr\AdminComparison;
use ITF\AdminBundle\Admin\Form\Search\SearchFieldMapping;

class LowerThanSearchOperator extends EqualsSearchOperator implements SearchOperatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->setLabel(Comparison::LT);
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
            new AdminComparison($this->getAliasedAttribute(), Comparison::LT, ':' . $this->getAttribute()),
            $this->getAttribute(),
            $this->getValue()
        );
    }
}