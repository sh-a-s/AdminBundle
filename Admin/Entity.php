<?php
namespace ITF\AdminBundle\Admin;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class Entity
{
	/**
	 * full qualified class name
	 * @var string
	 */
	protected $fq_class;

	/**
	 * default form class directory
	 * @var string
	 */
	protected $form_class_directory = "\\Form\\Admin\\";

	/**
	 * form class of entity (fq)
	 * @var string
	 */
	protected $form_class;

	/**
	 * entity class holder
	 * @var object
	 */
	protected $entity;

	/**
	 * entity class name
	 * @var string
	 */
	protected $entity_name;

	/**
	 * entity repository string
	 * @var string
	 */
	protected $entity_repo_class;

	/**
	 * entity namespace
	 * @var string
	 */
	protected $namespace;


	/**
	 * Entity associations
	 * @var array
	 */
	protected $entity_assoc;

	/**
	 * set namespace of entity
	 */
	public function setNamespace()
	{
		$this->namespace = (new \ReflectionObject($this->entity))->getNamespaceName();
	}

	/**
	 * get entity namespace
	 * @return string namespace
	 */
	public function getNamespace()
	{
		if (empty($this->namespace)) {
			$this->setNamespace();
		}

		return $this->namespace;
	}

	/**
	 * get full qualified class name of entity
	 * @return string
	 */
	public function getFQClassName()
	{
		if (empty($this->fq_class)) {
			$this->fq_class = get_class($this->entity);
		}

		return $this->fq_class;
	}

	/**
	 * get entity name
	 * @return string
	 */
	/*public function getEntityName($output_format = NULL)
	{
		if (empty($this->entity_name)) {
			$this->entity_name = substr(strrchr($this->getFQClassName(), '\\'), 1);
		}

		switch($output_format) {
			case 'strtolower':
				return strtolower($this->entity_name);
				break;
			default:
				return $this->entity_name;
				break;
		}
	}*/

	public function getName($output_format = NULL)
	{
		return $this->ah->getEntityName($this->entity_name, $output_format);
	}

	/**
	 * get parent namespace
	 * @return string
	 */
	protected function getParentNamespace()
	{
		return substr($this->getNamespace(), 0, strpos($this->getNamespace(), '\\'));
	}

	/**
	 * set form class directory
	 * @param $dir
	 */
	public function setFormClassDirectory($dir)
	{
		$this->form_class_directory = $dir;
	}

	/**
	 * get form type class full qualified
	 * @return string
	 */
	public function getFormTypeClass()
	{
		return $this->ah->getEntityFormTypeClass($this->getName());
	}

	/**
	 * get entity repository string
	 * @return string
	 */
	public function getRepository()
	{
		return $this->ah->getEntityRepository($this->getName());
	}

	/**
	 * get entity class
	 * @return object
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * @param string $entity
	 * @param Controller $container
	 *
	 * @throws \Exception
	 */
	public function __construct($entity, Controller $container)
	{
		$this->container = $container;
		$this->ah = $this->container->get('itf.admin_helper');

		/* @var $this->ah AdminHelper */

		if (is_string($entity)) {
			$entity_class = $this->ah->getEntityClassByName($entity);
			$entity = new $entity_class();

			$this->entity_name = $this->ah->getEntityName($entity);
		}
		else if (is_object($entity)) {
			$this->entity_name = $this->ah->getEntityNameFromClass(get_class($entity));
		} else {
			throw new Exception("entity must be of type string or object");
		}

		$this->entity = $entity;
	}

	/**
	 * map request data to entity
	 * @param Request $request
	 */
	public function setRequestData(Request $request)
	{
		// TODO: select joining value
		foreach($request->query->all() as $attr => $value) {
			$destination_entity = ucfirst($attr);

			if (method_exists($this->entity, 'set' . $destination_entity)) {
				// get entity
				${$attr} = $this->ah->getEntityManager()->getRepository( $this->ah->getEntityRepository($destination_entity) )->find($value);

				if (!empty(${$attr})) {
					$this->entity->{'set' . $destination_entity}(${$attr});
				}
			}
		}
	}

	public function getEntityAssociations()
	{
		return $this->ah->getEntityAssociations($this->getName('strtolower'), $this->getEntity()->getId());
	}

	/**
	 * Get entity associations
	 * @return array

	public function getEntityAssociations()
	{
		$classData = $this->em->getClassMetadata($this->getEntityRepository());

		if (empty($this->entity_assoc)) {
			$entity_assoc = array();
			foreach ($classData->associationMappings as $assoc_name => $assoc_type) {
				if ($assoc_type['mappedBy'] == $this->getEntityName('strtolower')) {
					$assoc_type['context'] = array(
						$assoc_type['mappedBy'] => $this->getEntity()->getId()
					);
					$entity_assoc[] = $assoc_type;
				}
			}

			$this->entity_assoc = $entity_assoc;
		}

		return $this->entity_assoc;
	}
	 * */
}