<?php
namespace ITF\AdminBundle\Admin\Form\Search\Expr;
use Doctrine\ORM\Query\Expr\Comparison;

/**
 * Expression class for DQL comparison expressions.
 */
class AdminComparison extends Comparison
{
    /**
     * @param $leftExpr
     *
     * @return mixed
     */
    public function setLeftExpr($leftExpr)
    {
        $this->leftExpr = $leftExpr;
    }

    /**
     * @param $operator
     *
     * @return string
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    /**
     * @param $rightExpr
     *
     * @return mixed
     */
    public function setRightExpr($rightExpr)
    {
        $this->rightExpr = $rightExpr;
    }
}
