<?php
namespace ITF\AdminBundle\Admin\Form\Search\Operators;

use Doctrine\ORM\QueryBuilder;

interface AddParameterInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     */
    public function addParameters(QueryBuilder &$queryBuilder);
}