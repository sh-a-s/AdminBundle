<?php
namespace ITF\AdminBundle\Admin\Form\Search\Operators;

use Doctrine\ORM\Query\Expr\Comparison;
use ITF\AdminBundle\Admin\Form\Search\Expr\AdminComparison;

class OperatorExpression
{
    /** @var Comparison */
    private $expression;

    /** @var string */
    private $parameter;

    /** @var mixed */
    private $value;


    /**
     * OperatorExpression constructor.
     *
     * @param AdminComparison $expression
     * @param string $parameter
     * @param mixed $value
     */
    public function __construct(AdminComparison $expression = NULL, $parameter = NULL, $value = NULL)
    {
        $this->expression = $expression;
        $this->parameter = $parameter;
        $this->value = $value;
    }


    /**
     * @return AdminComparison
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @param Comparison $expression
     *
     * @return OperatorExpression
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;

        return $this;
    }
    
    /**
     * @param bool $as_part
     *
     * @return string
     */
    public function getParameter($as_part = true)
    {
        return $as_part
            ? ':' . $this->parameter
            : $this->parameter
        ;
    }

    /**
     * @param string $parameter
     *
     * @return OperatorExpression
     */
    public function setParameter($parameter)
    {
        $this->parameter = $parameter;

        return $this;
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
     * @return OperatorExpression
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }


}