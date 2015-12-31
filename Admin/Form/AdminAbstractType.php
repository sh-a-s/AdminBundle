<?php
namespace ITF\AdminBundle\Admin\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AdminAbstractType extends AbstractType
{
	private $container;

	/* @var \ITF\AdminBundle\Admin\Form\FormHelper */
	private $form_helper;

	public function getContainer()
	{
		return $this->container;
	}

	public function __construct(ContainerInterface $container = NULL)
	{
		$this->container = $container;
		$this->form_helper = $this->getContainer()->get('itf.admin.form.helper');
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getEntityManager()
	{
		return $this->container->get('doctrine.orm.default_entity_manager');
	}

	public function getFormHelper()
	{
		return $this->form_helper;
	}

	public function addDateTime(FormBuilderInterface &$builder, $attr, $options = array())
	{
		$default = array(
			'format'    => 'dd.MM.yyyy H:mm',
			'widget' => 'single_text',
			'attr' => array(
					'class' => 'bs-datetimepicker',
					'data-format' => 'DD.MM.YYYY H:mm'
			)
		);

		$options = array_merge($default, $options);
		$builder->add($attr, 'datetime', $options);
	}
}