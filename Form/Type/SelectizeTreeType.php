<?php
namespace ITF\AdminBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectizeTreeType extends AbstractType
{
	private $container;

	private $defaults = array(
		'attr' => array(
			'class' => 'selectize',
			'data-type' => 'tree'
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

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @return ContainerInterface
	 */
	public function getContainer()
	{
		return $this->container;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSetData'));
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getEntityManager()
	{
		return $this->getContainer()->get('doctrine.orm.default_entity_manager');
	}

	public function onPreSetData(FormEvent $event)
	{
		// entity and property
		$entity = $event->getForm()->getParent()->getData();
		$property_name = $event->getForm()->getConfig()->getName();

		// accessor and repo
		$accessor = $this->getContainer()->get('property_accessor');
		$ah = $this->getContainer()->get('itf.admin_helper');
		$main_entity_info = $ah->getEntityInfo(get_class($entity));
		$repo = $ah->getEntityRepositoryReference($property_name, $main_entity_info['bundle']);

		// get data
		$data = $event->getData();
		$c_data = $accessor->getValue($entity, $property_name);

		// convert persistentcollection to array
		$current_data = array();
		foreach($c_data as $row) {
			$current_data[] = $row;
		}

		if (strlen($data) > 0) {
			// prepare data
			if (preg_match('/\,/', $data)) {
				$data = explode(',', $data);
			} else {
				$data = array($data);
			}

			$add = array();
			foreach($data as $id) {
				$entry = $repo->find($id);

				if ($entry !== NULL) {
					$add[] = $entry;
				}
			}

			// add
			$event->setData($add);
		} else {
			$event->setData(array());
		}


		//exit;
	}

	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		parent::buildView($view, $form, $options);

		// overwrite selectize attrs
		if (isset($options['selectize'])) {
			foreach ($options['selectize'] as $attr => $value) {
				$this->defaults['selectize'][ $attr ] = $value;
			}
		}

		$ah = $this->getContainer()->get('itf.admin_helper');
		$entity_name = $ah->getEntityNameFromClass($options['class']);

		// bundle/entity
		$this->defaults['selectize']['bundle'] = $ah->getBundleNameShort();
		$this->defaults['selectize']['entity'] = strtolower($entity_name);

		// value
		$this->defaults['value'] = null;
		$values = array();

		$this->defaults['selectize']['value'] = null;
		if (count($view->vars['data']) > 0) {
			foreach ($view->vars['data'] as $key => $value) {
				$values[] = $value->getId();
			}
			$this->defaults['selectize']['value'] = implode($values, ',');
		}

		foreach($this->defaults['selectize'] as $attr => $value) {
			$this->defaults['attr']['data-selectize-' . $attr] = $value;
		}

		// set init to int
		$this->defaults['selectize']['init'] = (int) $this->defaults['selectize']['init'];

		$view->vars = array_merge($view->vars, $this->defaults);
	}

	public function getParent()
	{
		return 'text';
	}

	public function getName()
	{
		return 'selectize_tree';
	}
}