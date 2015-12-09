<?php
namespace ITF\AdminBundle\Admin\Form;

use ITF\AdminBundle\Admin\Service\AbstractServiceSetter;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;

class FormHelper extends AbstractServiceSetter
{
	private $accessor;
	private $action;
	private $valid_actions = array('add', 'edit');

	public function __construct(ContainerInterface $container)
	{
		$this->setContainer($container);
		$this->accessor = $this->getContainer()->get('property_accessor');
	}

	/**
	 * @param $value
	 *
	 * @return bool
	 */
	protected function isEmpty($value)
	{

		if ($value === NULL) {
			return true;
		}
		elseif (is_string($value) && strlen($value) == 0) {
			return true;
		}
		elseif ((is_object($value) || is_array($value)) && count($value) == 0) {
			return true;
		}

		return false;
	}

	private function isValidAction($action)
	{
		if (!in_array($action, $this->valid_actions)) {
			throw new LogicException(sprintf('Given action "%s" is not valid. Allowed: %a', $action, $this->valid_actions));
		}

		return true;
	}

	/**
	 * @param string|array $options
	 *
	 * @return $this
	 */
	public function setAction($options)
	{
		$action = NULL;

		if (is_array($options) && isset($options['attr']['type'])) {
			$action = $options['attr']['type'];
		} else if (is_string($options)) {
			$action = $options;
		}

		if (!empty($action) && $this->isValidAction($action)) {
			$this->action = $action;
		}

		return $this;
	}

	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param $property
	 * @param $value
	 *
	 * @return $this
	 */
	public function setDefaultValue(FormBuilderInterface &$builder, $property, $value)
	{
		$data = $builder->getData();

		if ($this->isEmpty($this->accessor->getValue($data, $property))) {
			$this->accessor->setValue($data, $property, $value);
			$builder->setData($data);
		}

		return $this;
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param $property
	 *
	 * @return $this
	 */
	public function setCurrentUserDefault(FormBuilderInterface &$builder, $property)
	{
		$current_user = $this->getContainer()->get('pn.user')->getUser();

		if ($current_user) {
			$this->setDefaultValue($builder, $property, $current_user);
		}

		return $this;
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param $property
	 * @param $action
	 *
	 * @return $this
	 */
	public function setDisabledAtAction(FormBuilderInterface &$builder, $property, $action)
	{
		if ($this->isValidAction($action) && $action === $this->getAction()) {
			$builder->get($property)->setDisabled(true);
		}

		return $this;
	}
}