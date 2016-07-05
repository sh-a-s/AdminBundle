<?php
namespace ITF\AdminBundle\Admin\Form\Search;

use Doctrine\Common\Collections\ArrayCollection;
use ITF\AdminBundle\Admin\Form\Search\Operators\EqualsSearchOperator;
use ITF\AdminBundle\Admin\Form\Search\Operators\LikeApproxSearchOperator;
use ITF\AdminBundle\Admin\Form\Search\Operators\LikeSearchOperator;

class SearchOperators extends ArrayCollection
{
    public function __construct(array $elements = array())
    {
        parent::__construct($elements);

        $this->add(new EqualsSearchOperator());
        $this->add(new LikeSearchOperator());
        $this->add(new LikeApproxSearchOperator());
    }
    
    public function getDefaultSelected()
    {
        return 1;
    }
}