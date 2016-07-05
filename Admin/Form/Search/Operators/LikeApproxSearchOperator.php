<?php
namespace ITF\AdminBundle\Admin\Form\Search\Operators;

class LikeApproxSearchOperator extends LikeSearchOperator implements SearchOperatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->setLabel('LIKE %...%');
    }
    
    public function setValue($value)
    {
        $this->value = '%' . $value . '%';
    }
}