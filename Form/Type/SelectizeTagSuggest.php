<?php
namespace ITF\AdminBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectizeTagSuggest extends AbstractType
{
	private $container;

	private $defaults = array(
		'attr' => array(
			'class' => 'selectize',
			'data-type' => 'suggest',
		),
		'selectize' => array(
			'searchfield' => 'label',
			'searchfield_method' => 'label', // method call
			'hintfield' => 'id',
			'route' => 'api_v_',
			'entity' => NULL

			/* attr = query, value = id */
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

	public function __construct()
	{
		$this->defaults['query_builder'] = function(EntityRepository $e) {
			return $e->createQueryBuilder('a')
				->setMaxResults(1)
				;
		};
	}

	/**
	 * @return ContainerInterface
	 */
	public function getContainer()
	{
		return $this->container;
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
		$this->defaults['selectize']['entity'] = $form->getData();

		foreach($this->defaults['selectize'] as $attr => $value) {
			$this->defaults['attr']['data-selectize-' . $attr] = $value;
		}

		$view->vars = array_merge($view->vars, $this->defaults);
	}

	public function getParent()
	{
		return 'entity';
	}

	public function getName()
	{
		return 'selectize_suggest';
	}
}