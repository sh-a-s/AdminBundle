<?php
namespace ITF\AdminBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectizeMultipleType extends SelectizeTreeType
{
    protected $defaults = array(
        'attr' => array(
            'class' => 'selectize',
            'data-type' => 'basic',
            'multiple' => 'multiple'
        ),
        'class' => null,
        'choices' => null,
        'choice_list' => null,
        'required' => false,
        'selectize' => array(
            'init' => 'init',
            'route' => 'api_admin_tree_html',
            'bundle' => null,
            'entity' => null,
            'format' => 'json',
            'type' => 'flat'
        )
    );
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults($this->defaults);
    }
    
	public function getName()
	{
		return 'selectize_multiple';
	}
}