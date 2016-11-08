<?php
namespace ITF\AdminBundle\Admin;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use ITF\AdminBundle\Entity\LogEntry;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\ClassNotFoundException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class AdminHelper
{
	protected $em;

	/* @var Controller $container */
	protected $container;

	protected $bundle;
	protected $bundle_name = '';
	protected $bundles;
	protected $entity;
	protected $entities;
	protected $entity_form_class_path = '\\Form\\Admin\\';
	protected $datatable;
	protected $http_request;

	public function __construct(EntityManager $entityManager, $container)
	{
		$this->em = $entityManager;
		$this->container = $container;

		// disable lifecycle callbacks for LogEntry
		$this->em->getClassMetadata(get_class(new LogEntry()))->setLifecycleCallbacks(array());
	}

	public function getRouter()
	{
		return $this->container->get('router');
	}

	public function getEntityManager()
	{
		return $this->em;
	}

	public function getBundles()
	{
		if (empty($this->bundles)) {
			$this->bundles = $this->container->get('service_container')->getParameter('kernel.bundles');
		}

		return $this->bundles;
	}

	public function setBundle($bundle)
	{
		$config = $this->container->get('itf.admin.config')->getConfig();
		$enabled_bundles = $config['enable_bundles'];
		$bundles = $this->getBundles();

		if (is_array($enabled_bundles) && count($enabled_bundles) > 0) {
			foreach($bundles as $_bundle => $path) {
				if (!in_array($_bundle, $enabled_bundles)) {
					unset($bundles[$_bundle]);
				}
			}
		}

		foreach($bundles as $bundle_name => $bundle_fq) {
			if (preg_match('/'.$bundle.'/i', $bundle_name)) {
				$this->bundle = $bundle_fq;
				$this->bundle_name = $bundle_name;
				break;
			}
		}

		return $this;
	}

	public function setEntity($entity)
	{
		$this->entity = $entity;

		return $this;
	}

	public function getBundle()
	{
		return $this->bundle;
	}

	public function getBundleName()
	{
		return $this->bundle_name;
	}

	public function getBundleNameShort()
	{
		return strtolower(preg_replace('/bundle/i', '', $this->getBundleName()));
	}

	public function setEntities()
	{
		$entities = array();
		$meta = $this->em->getMetadataFactory()->getAllMetadata();

		$bundle_namespace = substr($this->bundle, 0, strrpos($this->bundle, '\\'));
		$bundle_namespace_regex = str_replace('\\', '\\\\', $bundle_namespace);

		/* @var \Doctrine\ORM\Mapping\ClassMetadata $m */
		foreach ($meta as $m) {
			$_entity = $m->getName();

			// if bundle set, select entities only within bundle
			if (!empty($this->bundle_name)) {
				if (preg_match('/' . $bundle_namespace_regex . '/', $_entity)) {
					$entities[] = $_entity;
				}
			} else {
				$entities[] = $_entity;
			}
		}

		sort($entities);
		$this->entities = $entities;

		return $this;
	}

	public function getEntities()
	{
		if (empty($entities)) {
			$this->setEntities();
		}

		return $this->entities;
	}

	public function getEntityInfo($entity)
	{
		$entity = str_replace('\\', '\\\\', $entity);

		$info = array(
			'entity_short' => NULL,
			'entity_fq' => NULL,
			'query' => $entity,
			'bundle' => NULL,
			'bundle_short' => NULL,
			'namespace' => NULL,
			'repository' => NULL,
		);

		$meta = $this->em->getMetadataFactory()->getAllMetadata();

		/* @var \Doctrine\ORM\Mapping\ClassMetadata $m */
		foreach ($meta as $m) {
			//pre($m);
			$name = $m->getName();
			if (preg_match('/'.$entity.'$/i', $name)) {
				$rfl = $m->getReflectionClass();

				preg_match('/^((.*?)Bundle)/', $name, $match);

				$info['entity_fq'] = $name;
				$info['entity_short'] = str_replace($rfl->getNamespaceName() . '\\', '', $name);
				$info['bundle'] = $match[1];
				$info['bundle_short'] = strtolower($match[2]);
				$info['namespace'] = $rfl->getNamespaceName();
				$info['repository'] = $info['bundle'] . ':' . $info['entity_short'];

				return $info;
			}
		}

		return false;
	}

	/**
	 * @param $bundle
	 * @param $entity
	 *
	 * @return false|object
	 */
	public function getEntityInstanceByParam($bundle, $entity)
	{
		$meta = $this->em->getMetadataFactory()->getAllMetadata();

		/* @var \Doctrine\ORM\Mapping\ClassMetadata $m */
		foreach($meta as $m) {
			$_entity = $m->getName();
			if (preg_match('/'.$bundle.'.*?'.$entity.'/i', $_entity)) {
				return new $_entity();
			}
		}

		return false;
	}

	public function getEntityClassByName($name)
	{
		if (is_object($name)) {
			$name = $this->getEntityNameFromClass( get_class($name) );
		}

		foreach($this->getEntities() as $entity) {
			if (preg_match('/'.$name.'/i', $entity)) {
				$matches[] = $entity;
			}
		}

		// get closest match
		if (!empty($matches)) {
			$tmp = array();
			foreach($matches as $match) {
				$tmp[ strlen($match) ] = $match;
			}
			ksort($tmp);

			// return first
			foreach($tmp as $match) {
				return $match;
			}
		}

		return false;
	}

	public function getEntityNameFromClass($entity_class)
	{
		return substr(strrchr($entity_class, '\\'), 1);
	}

	public function getEntityName($entity_string, $output_format = NULL)
	{
		if (in_array($entity_string, $this->getEntities())) {
			$entity = $this->getEntityNameFromClass($entity_string);
		} else {
			$entity = $this->getEntityNameFromClass($this->getEntityClassByName($entity_string));
		}

		switch($output_format) {
			case 'strtolower':
				return strtolower($entity);
				break;
			case 'ucfirst':
				return ucfirst($entity);
				break;
			case 'lcfirst':
				return lcfirst($entity);
				break;
			default:
				return $entity;
				break;
		}
	}

	public function getEntityNameShort()
	{
		return $this->entity;
	}

	public function getEntityRepository($entity, $bundle = NULL)
	{
		if (empty($bundle)) {
			$bundle = $this->getBundleName();

			if (empty($bundle)) {
				throw new Exception("bundle not set");
			}
		}

		return $bundle . ":" . $this->getEntityName($entity);
	}

	/**
	 * @param $entity
	 * @param null $bundle
	 *
	 * @return \Doctrine\ORM\EntityRepository
	 */
	public function getEntityRepositoryReference($entity, $bundle = NULL)
	{
		return $this->getEntityManager()->getRepository($this->getEntityRepository($entity, $bundle));
	}

	protected function getClassMetadata($class)
	{
		return $this->em->getMetadataFactory()->getMetadataFor($class);
	}

	public function getEntityColumns($entity)
	{
		$meta = $this->getClassMetadata( $this->getEntityClassByName($entity) );

		if ($meta) {
			return $meta->getFieldNames();
		}

		return false;
	}

	public function getEntityFormTypeClass($entity)
	{
		$bundle_namespace = substr($this->bundle, 0, strrpos($this->bundle, '\\'));

		return $bundle_namespace . $this->entity_form_class_path . $this->getEntityName($entity) . "Type";
	}

	public function isEntityColumn($entity, $column_name)
	{
		return in_array($column_name, $this->getEntityColumns($entity));
	}

	public function getEntityAssociations($entity, $id = 0)
	{
		$meta = $this->getClassMetadata( $this->getEntityClassByName($entity) );

		$entity_assoc = array();
		//pre($meta->associationMappings);exit;
		foreach ($meta->associationMappings as $assoc_name => $assoc_type) {
			if ($assoc_type['mappedBy'] == $this->getEntityName($entity, 'lcfirst')) {

				// add id info
				$assoc_type['context'] = array(
					$assoc_type['mappedBy'] => ($id > 0) ? $id : false
				);
				$entity_assoc[] = $assoc_type;
			}
		}

		//pre($entity_assoc);exit;

		return $entity_assoc;
	}

	public function getEntityJoins($entity)
	{
		$meta = $this->getClassMetadata( $this->getEntityClassByName($entity) );

		$entity_joins = array();
		foreach ($meta->associationMappings as $assoc_name => $assoc_type) {
			if ($assoc_type['inversedBy'] == $this->getEntityName($entity, 'lcfirst')) {
				$entity_joins[] = $assoc_name;
			}
		}

		return $entity_joins;
	}

	public function getEntityInstance($entity)
	{
		$entity_class = $this->getEntityClassByName($entity);

		if (!class_exists($entity_class)) {
			throw new \LogicException(sprintf('Class "%s" loaded by "%s" not found.', $entity_class, $entity));
		}

		return new $entity_class();
	}

	protected function getEntityMethodReturn($entity_class, $method)
	{
		$return = array();

		// get exclude fields
		if (method_exists($entity_class, $method)) {
			$return = $entity_class->{$method}();

			if (!is_array($return)) {
				throw new Exception("array returned by entities method ".$method."() must be of type array, ".gettype($return)." given.");
			}
		}

		return $return;
	}

	public function getEntityListOptions($entity)
	{
		// default empty
		$entity_options = array(
			'fields_list' => array(),
			'exclude_fields' => array(),
			'column_names' => array()
		);

		// init entity
		$entity_class = $this->getEntityInstance($entity);

		// get exclude fields
		$entity_options['fields_list'] = $this->getEntityMethodReturn($entity_class, 'adminSetFieldsList');
		$entity_options['exclude_fields'] = $this->getEntityMethodReturn($entity_class, 'adminExcludeFieldsList');
		$entity_options['column_names'] = $this->getEntityMethodReturn($entity_class, 'adminSetColumnsTitleList');

		return $entity_options;
	}

	public function getDatatablesListColumns($entity)
	{
		$entity_options = $this->getEntityListOptions($entity);

		$entity_fields = array();
		$entity_columns = $this->getEntityColumns($entity);

		// if fields_list set
		if (!empty($entity_options['fields_list'])) {
			foreach($entity_options['fields_list'] as $column_name => $column_attr) {
				if (in_array($column_attr, $entity_columns)) {
					$entity_fields[ $column_name ] = $this->dtGetEntityColumnWithAlias($entity, $column_attr);

				// if join column (user.username)
				} elseif (preg_match('/([a-z\_]+)\.([a-z\_]+)/i', $column_attr, $m)) {
					list($column_attr, $join_entity, $join_entity_attr) = $m;

					/*pre($this->getEntityJoins($entity));
					pre('$column_attr: ' . $column_attr);
					pre('$join_entity: ' . $join_entity);
					pre('$join_entity_attr: ' . $join_entity_attr);

					pre('join exists: '.in_array($join_entity, $this->getEntityJoins($entity)));

					pre('entity: '.$entity);
					pre($this->getEntityJoins($entity));*/

					// if join exists
					if (in_array($join_entity, $this->getEntityJoins($entity))) {
						// if column exists in join entity
						if (in_array($join_entity_attr, $this->getEntityColumns($join_entity))) {

							// add entity
							$this->dtAddJoinEntity($entity, $join_entity);

							// add column
							$entity_fields[ $column_name ] = $this->dtGetEntityColumnWithAlias($join_entity, $join_entity_attr);
						}
					}
				}
			}
		} else {
			foreach ($this->getEntityColumns($entity) as $column) {
				$column_name = $column;

				// column name
				if (isset($entity_options['column_names'][ $column ])) {
					$column_name = $entity_options['column_names'][ $column ];
				}

				// exclude fields
				if (!in_array($column, $entity_options['exclude_fields'])) {
					$entity_fields[ $column_name ] = $this->dtGetEntityColumnWithAlias($entity, $column);
				}
			}
		}

		// add identifier
		$entity_fields['_identifier_'] = $this->dtGetEntityColumnWithAlias($entity, 'id');

		return $entity_fields;
	}


	public function initDatatable()
	{
		$this->datatable = array(
			/* [abc] => [entity] (low) */
			'entities' => array(),
			'joins' => array(),
			'where' => array(),
		);

		$this->datatable['join_exp'] = \Doctrine\ORM\Query\Expr\Join::LEFT_JOIN;
	}

	protected function dtAddEntity($entity)
	{
		// get last char
		end($this->datatable['entities']);
		$last_char = key($this->datatable['entities']);
		reset($this->datatable['entities']);

		if (empty($last_char)) {
			$char = 'a';
		} else {
			$char = ++$last_char;
		}

		$this->datatable['entities'][$char] = $entity;
	}

	protected function dtAddJoinEntity($entity, $join_entity)
	{
		if (!$this->dtEntitySet($join_entity)) {
			$this->dtAddEntity($join_entity);

			/* ->addJoin('x.user', 'y', $join_exp) */
			$this->datatable['joins'][] = array(
				'entity_join' => $this->dtGetCharByEntity($entity) . '.' .$join_entity,
				'entity_char' => $this->dtGetCharByEntity($join_entity)
			);
		}

		return false;
	}

	public function dtGetColumnByProperty($property, $fields)
	{
		foreach($fields as $title => $db_property) {
			if (preg_match('/'.$property.'/', $db_property)) {
				return $fields[$title];
			}
		}

		return false;
	}

	protected function dtGetEntityByChar($char)
	{
		if (isset($this->datatable['entities'][$char])) {
			return $this->datatable['entities'][$char];
		}

		return false;
	}

	protected function dtGetCharByEntity($entity)
	{
		foreach($this->datatable['entities'] as $char => $_entity) {
			if ($entity == $_entity) {
				return $char;
			}
		}

		return false;
	}

	protected function dtEntitySet($entity)
	{
		return in_array($entity, $this->datatable['entities']);
	}

	protected function dtGetEntityColumnWithAlias($entity, $column)
	{
		if (!$this->dtEntitySet($entity)) {
			throw new Exception("entity ".$entity." not set in datatables info array");
		}

		return $this->dtGetCharByEntity($entity) . "." . $column;
	}

	/* admin/app/post?context[brand]=1 */
	public function dtHandleRequest($entity, Request $request)
	{
		$context = $request->get('context');
		if (empty($context)) {
			$context = $request->query->get('context');
		}

		// TODO: more than just [context]
		if ($context) {
			foreach ($context as $attr => $value) {
				// check if join valid
				if (in_array($attr, $this->getEntityJoins($entity))) {
					// add entity if not exist
					if (!$this->dtEntitySet($attr)) {
						$this->dtAddJoinEntity($entity, $attr);
					}

					// add where
					$this->datatable['where']['string'] = $this->dtGetCharByEntity($attr) . '.id = :id';
					$this->datatable['where']['params'] = array('id' => $value);
				}
			}
		}
	}

	public function dtGetContext(Request $request)
	{
		return $request->query->get('context');
	}

	/**
	 * @param null $current_entity
	 * @return array
	 * @deprecated
	 */
	public function getAdminMenu($current_entity = NULL)
	{
		return $this->container->get('itf.admin.menu')->createAdminMenu($current_entity);
	}

	public function isLoggedIn()
	{
		if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
			|| $this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

			return true;
		}

		return false;
	}

	public function disableProfiler()
	{
		if ($this->container->has('profiler')) {
			$this->container->get('profiler')->disable();
		}
	}

	public function getUser()
	{
		return $this->container->get('security.token_storage')->getToken()->getUser();
	}

	public function getDatatable($entity, Request $request = NULL)
	{
		// init
		$this->initDatatable();
		$this->dtAddEntity($entity);

		// create datatable
		/* @var \Ali\DatatableBundle\Util\Datatable $datatable */
		$datatable = $this->container->get('datatable');

		// set entity
		$datatable->setEntity($this->getEntityRepository($entity), $this->dtGetCharByEntity($entity));

		// set fields
		$fields = $this->getDatatablesListColumns($entity);
		$fields_count = count($fields);
		$datatable->setFields($fields);

		// handle request
		if (!empty($request)) {
			$this->dtHandleRequest($entity, $request);

			if (!empty($this->datatable['where'])) {
				$datatable->setWhere($this->datatable['where']['string'], $this->datatable['where']['params']);
			}
		}

		// add joins
		foreach($this->datatable['joins'] as $join) {
			$datatable->addJoin($join['entity_join'], $join['entity_char'], $this->datatable['join_exp']);
		}

		// set renderer
		$datatable->setRenderer(
			function(&$data) {
				foreach($data as $key => $value) {
					// array
					if (is_array($value)) {
						$data[$key] = implode(', ', $value);
					}

					// datetime
					if ($value instanceof \DateTime) {
						$data[$key] = $value->format('d.m.Y');
					}
				}
			}
		);

		// set action renderer
		$datatable->setRenderers(
			array(
				($fields_count - 1) => array(
				//'_identifier_' => array(
					'label' => '_identifier_',
					'view' => '@ITFAdmin/Admin/helper/dt_renderer.html.twig',
					'params' => array(
						'edit_route' => 'admin_edit',
						'bundle' => $this->getBundleNameShort(),
						'entity' => $this->getEntityName($entity, 'strtolower'),
						'has_edit' => class_exists($this->getEntityFormTypeClass($entity))
					)
				)
			)
		);

		// set search
		$datatable->setSearch(true);

		// set order
		$listConfig = $this->container->get('itf.admin.config')->getEntityListConfig();
		if ($listConfig) {
			$order_field = $this->dtGetColumnByProperty($listConfig['order_property'], $fields);
			$order_direction = @$listConfig['order_direction'];

			if ($order_field && $order_direction) {
				$datatable->setOrder($order_field, $order_direction);
			}
		}

		// set mass action
		/*$datatable->setHasAction(true);
		$datatable->setMultiple(
				array(
					'delete' => array(
						'title' => 'Delete',
						'route' => 'admin_mass_delete' // path to multiple delete route
					)
				)
			);*/

		/** @var \Doctrine\ORM\QueryBuilder $qb */
		/*$qb = $datatable->getQueryBuilder()->getDoctrineQueryBuilder();
		dump($qb->getQuery()->getSQL());*/

		//pre($this->dtGetColumnByProperty('id', $fields));exit;



		return $datatable;
	}

	/* routing */
	public function getEditPath($id, $entity = NULL, $bundle = NULL)
	{
		if (empty($entity)) $entity = $this->entity;
		if (empty($bundle)) $bundle = $this->getBundleNameShort();

		return $this->getRouter()->generate('admin_edit', array(
			'id' => $id,
			'entity' => $entity,
			'bundle' => $bundle
		));
	}
}
