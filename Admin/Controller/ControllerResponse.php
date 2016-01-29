<?php
namespace ITF\AdminBundle\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class ControllerResponse
{
	private $controller;
	private $func_type;
	private $template;
	private $bundle;
	private $entity;
	private $entity_name;
	private $tree_html;
	private $table_id;
	private $context;
	private $entity_id;
	private $form;
	private $delete_form;
	private $entity_assoc;
	private $entity_translatable;
	private $data = array();

	public function __construct(Controller $controller)
	{
		$this->controller = $controller;
		$this
			->setFuncType('default')
			->setBundle('app')
		;
	}

	public static function create($controller)
	{
		return new ControllerResponse($controller);
	}

	public function createResponse()
	{
		return $this->controller->render($this->getTemplate(), $this->__toArray());
	}

	public function createRedirectToEdit()
	{
		return $this->controller->redirect($this->controller->generateUrl('admin_edit', array(
			'id' => $this->getEntityId(),
			'bundle' => $this->getBundle(),
			'entity' => $this->getEntityName()
		)));
	}

	public function createRedirectToList()
	{
		return $this->controller->redirect($this->controller->generateUrl('admin_list', array(
			'bundle' => $this->getBundle(),
			'entity' => $this->getEntityName()
		)));
	}

	/**
	 * @return mixed
	 */
	public function getFuncType()
	{
		return $this->func_type;
	}

	/**
	 * @param mixed $func_type
	 *
	 * @return ControllerResponse
	 */
	public function setFuncType($func_type)
	{
		$this->func_type = $func_type;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param mixed $template
	 *
	 * @return ControllerResponse
	 */
	public function setTemplate($template)
	{
		$this->template = $template;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getBundle()
	{
		return $this->bundle;
	}

	/**
	 * @param mixed $bundle
	 *
	 * @return ControllerResponse
	 */
	public function setBundle($bundle)
	{
		$this->bundle = $bundle;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * @param mixed $entity
	 *
	 * @return ControllerResponse
	 */
	public function setEntity($entity)
	{
		$this->entity = $entity;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getTreeHtml()
	{
		return $this->tree_html;
	}

	/**
	 * @param mixed $tree_html
	 *
	 * @return ControllerResponse
	 */
	public function setTreeHtml($tree_html)
	{
		$this->tree_html = $tree_html;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getTableId()
	{
		return $this->table_id;
	}

	/**
	 * @param mixed $table_id
	 *
	 * @return ControllerResponse
	 */
	public function setTableId($table_id)
	{
		$this->table_id = $table_id;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @param mixed $context
	 *
	 * @return ControllerResponse
	 */
	public function setContext($context)
	{
		$this->context = $context;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEntityId()
	{
		return $this->entity_id;
	}

	/**
	 * @param mixed $entity_id
	 *
	 * @return ControllerResponse
	 */
	public function setEntityId($entity_id)
	{
		$this->entity_id = $entity_id;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getForm()
	{
		return $this->form;
	}

	/**
	 * @param mixed $form
	 *
	 * @return ControllerResponse
	 */
	public function setForm($form)
	{
		$this->form = $form;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDeleteForm()
	{
		return $this->delete_form;
	}

	/**
	 * @param mixed $delete_form
	 *
	 * @return ControllerResponse
	 */
	public function setDeleteForm($delete_form)
	{
		$this->delete_form = $delete_form;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEntityAssoc()
	{
		return $this->entity_assoc;
	}

	/**
	 * @param mixed $entity_assoc
	 *
	 * @return ControllerResponse
	 */
	public function setEntityAssoc($entity_assoc)
	{
		$this->entity_assoc = $entity_assoc;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEntityTranslatable()
	{
		return $this->entity_translatable;
	}

	/**
	 * @param mixed $entity_translatable
	 *
	 * @return ControllerResponse
	 */
	public function setEntityTranslatable($entity_translatable)
	{
		$this->entity_translatable = $entity_translatable;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEntityName()
	{
		return $this->entity_name;
	}

	/**
	 * @param mixed $entity_name
	 *
	 * @return ControllerResponse
	 */
	public function setEntityName($entity_name)
	{
		$this->entity_name = $entity_name;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param array $data
	 *
	 * @return ControllerResponse
	 */
	public function setData(array $data)
	{
		$this->data = $data;

		return $this;
	}

	public function addData($key, $attr)
	{
		$this->data[$key] = $attr;

		return $this;
	}

	public function generateSimpleArray()
	{
		return array(
			'bundle' => @$this->getBundle(),
			'entity' => @$this->getEntityName(),
			'id' => @$this->getEntityId()
		);
	}

	public function __toArray()
	{
		$array = array();

		foreach(get_class_vars(__CLASS__) as $property => $value) {
			try {
				$value = $this->controller->get('property_accessor')->getValue($this, $property);

				if ($value !== NULL) {
					$array[$property] = $value;
				}

				//$array[$property] = $this->controller->get('property_accessor')->getValue($this, $property);
			} catch (NoSuchPropertyException $e) {}
		}

		return $array;
	}
}