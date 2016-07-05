<?php
namespace ITF\AdminBundle\Admin\Form\Search\Operators;

use ITF\AdminBundle\Admin\Form\Search\SearchFieldMapping;

interface SearchOperatorInterface
{
    /**
     * @param SearchFieldMapping $searchFieldMapping
     *
     * @param string $alias
     *
     * @return $this
     */
    public function setSearchFieldMapping(SearchFieldMapping $searchFieldMapping, $alias = 'a');

    /**
     * @return OperatorExpression|false
     */
    public function getExpr();

    /**
     * @return bool
     */
    public function isEmpty();

    /**
     * @return string
     */
    public function getExpressionString();
}