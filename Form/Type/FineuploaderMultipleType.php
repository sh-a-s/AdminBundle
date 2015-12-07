<?php
namespace ITF\AdminBundle\Form\Type;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FineuploaderMultipleType extends FineuploaderType
{

	/*private $defaults = array(
		'fn_template' => 'default',
		'fn_file_limit' => 1,
		'fn_bundle' => NULL,
		'fn_entity' => NULL,
		'fn_entity_id' => 0,
		'fn_property' => NULL,
		//'fn_max_size' =>
		//'fn_file_types' =>
	);*/

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$this->defaults = array_merge($this->defaults, array(
			'fn_entity_property' => NULL,
			'fn_fk_entity' => NULL,
			'fn_fk_bundle' => NULL,
		));

		$resolver->setDefaults($this->defaults);
	}

	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		parent::buildView($view, $form, $options);

		$entity = $form->getParent()->getData();
		$property_name = $view->vars['name'];

		$this->defaults = array_merge($this->defaults, $options);

		// entity info
		$entity_info = $this->container->get('itf.admin_helper')->getEntityInfo(get_class($entity));
		$fk_entity_info = $this->container->get('itf.admin_helper')->getEntityInfo($this->defaults['data_class']);
		$this->defaults['fn_entity'] = $entity_info['entity_short'];
		$this->defaults['fn_bundle'] = $entity_info['bundle_short'];
		$this->defaults['fn_property'] = $property_name;
		$this->defaults['fn_fk_entity'] = $fk_entity_info['entity_short'];
		$this->defaults['fn_fk_bundle'] = $fk_entity_info['bundle_short'];

		// set id
		if (method_exists($entity, 'getId')) {
			$this->defaults['fn_entity_id'] = (int) $entity->getId();
		}

		$view->vars = array_merge($view->vars, $this->defaults);
	}

	public function getName()
	{
		return 'fineuploader_multiple';
	}

	public function getParent()
	{
		return 'text';
	}
}