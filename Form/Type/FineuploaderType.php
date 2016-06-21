<?php
namespace ITF\AdminBundle\Form\Type;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Mapping\PropertyMetadata;

class FineuploaderType extends AbstractType
{
	/* @var ContainerInterface */
	protected $container;

	protected $defaults = array(
		'fn_template' => 'default',
		'fn_file_limit' => 1,
		'fn_bundle' => NULL,
		'fn_entity' => NULL,
		'fn_entity_id' => 0,
		'fn_property' => NULL,
		'allowed_extensions' => array(),
		'_constraints' => array(
			'mimeTypes' => '*',
			'minWidth' => 0,
			'maxWidth' => 0,
			'minHeight' => 0,
			'maxHeight' => 0,
			'minRatio' => 0,
			'maxSize' => 0
		)
		//'fn_max_size' =>
		//'fn_file_types' =>
	);

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults($this->defaults);
	}

	public function extractConstraints(PropertyMetadata $propertyMetadata)
	{
		foreach($propertyMetadata->getConstraints() as $constraint) {
			foreach($this->defaults['_constraints'] as $key => $value) {
				try {
					$this->defaults['_constraints'][ $key ] = @$constraint->{$key};
				} catch (InvalidOptionsException $e) {}
			}
		}
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{

	}

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		parent::buildView($view, $form, $options);

		$entity = $form->getParent()->getData();
		$property_name = $view->vars['name'];

		$this->defaults = array_merge($this->defaults, $options);

		// entity info
		$entity_info = $this->container->get('itf.admin_helper')->getEntityInfo(get_class($entity));
		$this->defaults['fn_entity'] = $entity_info['entity_short'];
		$this->defaults['fn_bundle'] = $entity_info['bundle_short'];
		$this->defaults['fn_property'] = $property_name;

		// extract constraints
		$validator = $this->container->get('validator');
		$metadata = $validator->getMetadataFor(get_class($entity));

		if (isset($metadata->properties[$this->defaults['fn_property']])) {
			$this->extractConstraints($metadata->properties[$this->defaults['fn_property']]);
		}

		// set id
		if (method_exists($entity, 'getId')) {
			$this->defaults['fn_entity_id'] = (int) $entity->getId();
		}

		$view->vars = array_merge($view->vars, $this->defaults);
	}

	public function getName()
	{
		return 'fineuploader';
	}

	public function getParent()
	{
		return 'text';
	}
}