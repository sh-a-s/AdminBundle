<?php
namespace ITF\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectizeTagType extends AbstractType
{
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'attr' => array(
				'class' => 'selectize',
				'data-type' => 'tags'
			),
			'required' => false
		));
	}

	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		parent::buildView($view, $form, $options);
	}

	public function getParent()
	{
		return 'text';
	}

	public function getName()
	{
		return 'selectize_tags';
	}
}