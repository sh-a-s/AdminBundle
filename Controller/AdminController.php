<?php

namespace ITF\AdminBundle\Controller;

use ITF\AdminBundle\Admin\Controller\ControllerResponse;
use ITF\AdminBundle\Admin\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use ITF\UploadBundle\Upload\EntityUpload;
use Sensio\Bundle\FrameworkExtraBundle\DependencyInjection\Configuration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


/**
 * Admin controller.
 *
 */
class AdminController extends Controller
{
	protected $default_locale = 'en';


	/**
	 * set datatable configs
	 *
	 * @return \Ali\DatatableBundle\Util\Datatable
	 */
	private function _datatable($bundle, $entity, $table_id)
	{
		$request = $this->container->get('request_stack')->getCurrentRequest();

		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);

		$db = $ah->getDatatable($entity, $request);

		return $db->setDatatableId($table_id);
	}

	/**
	 * Grid action
	 * @return Response
	 */
	public function gridAction($bundle, $entity, $table_id)
	{
		return $this->_datatable($bundle, $entity, $table_id)->execute();
	}

	/**
	 * Lists all entity entries
	 *
	 * @param $bundle
	 * @param $entity
	 * @param bool|false $join_context
	 * @param array $context
	 * @param int $table_id
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function indexAction($bundle, $entity, $join_context = false, $context = array(), $table_id = 0, Request $request)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		$ah->setEntity($entity);
		$entity_instance = $ah->getEntityInstance($entity);

		//$ah->disableProfiler();

		// setup return
		$response = ControllerResponse::create($this)
			->setBundle($ah->getBundleNameShort())
			->setEntity($entity_instance)
			->setEntityName($ah->getEntityName($entity, 'strtolower'))
			->setTemplate('ITFAdminBundle:Admin:index.html.twig')
		;

		// if tree
		if ($this->get('itf.admin.annotation_reader')->isGedmoTree($entity_instance)) {
			$repo = $ah->getEntityRepositoryReference($entity);

			// add tree to return array
			$response
				->setTreeHtml($this->get('itf.admin.tree')->getTreeListHTML($repo))
				->setTemplate('@ITFAdmin/Admin/tree/index.html.twig')
			;

			return $response->createResponse();

			// if default
		}

		// table id
		if (empty($table_id)) {
			$table_id = 'dt-' . $entity . time();
		}
		$response->setTableId($table_id);

		// init dt
		$this->_datatable($bundle, $entity, $response->getTableId());

		// if join context, change template
		if ($join_context) {
			$response->setTemplate('ITFAdminBundle:Admin:datatable.html.twig');
		}

		// add context
		$response->setContext((!empty($context)) ? $context : $ah->dtGetContext($request));

		return $response->createResponse();
	}

	/**
	 * @param $bundle
	 *
	 * @return Response
	 */
	public function dashboardAction($bundle)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		
		// get dashboard service if available and wrap adapter
		$dashboard = $this->get('itf.admin.factory')->createDashboardAdapter(
			$this->get('itf.admin.config')->getDashboardService()
		);

		return $this->render('ITFAdminBundle::admin_base.html.twig', array(
			'bundle' => $bundle,
			'entity' => NULL,
			'entity_name' => NULL,
			'dashboard_title' => $dashboard->getTitle(),
			'dashboard_html' => $dashboard->renderView()
		));
	}

	public function multipleDeleteAction()
	{
		$data = $this->getRequest()->get('dataTables');
		$ids  = $data['actions'];
		pre($ids);
	}


	/**
	 * Creates a new entity entry.
	 *
	 * @param $bundle
	 * @param $entity
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function createAction($bundle, $entity, Request $request)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		$ah->setEntity($entity);

		// setup reponse
		$response = ControllerResponse::create($this)
			->setBundle($bundle)
			->setEntityName($entity)
			->setTemplate('ITFAdminBundle:Admin:new.html.twig')
		;

		// map
		$em = $this->getDoctrine()->getManager();
		$entity = new \ITF\AdminBundle\Admin\Entity($entity, $this);
		$form = $this->createActionForm($entity, 'add');
		$form->handleRequest($request);

		// if valid
		if ($form->isValid()) {
			$this->setLogging(true);
			$em->persist($entity->getEntity());
			$em->flush();
			$em->clear();
			$this->setLogging(false);

			// to edit
			if ($form->get('submit_stay')->isClicked()) {
				// add entity
				$response->setEntity($entity->getEntity());

				// set id
				$response->setEntityId($entity->getEntity()->getId());
				return $response->createRedirectToEdit();
			}

			// to list
			return $response->createRedirectToList();
		}

		// set form
		$response->setForm($form->createView());
		return $response->createResponse();
	}


	/**
	 * Displays a form to create an entity entry.
	 *
	 * @param $bundle
	 * @param Request $request
	 * @param $entity
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function newAction($bundle, Request $request, $entity)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		$ah->setEntity($entity);
		$entity_config = $this->get('itf.admin.config')->getEntityConfig();

		// setup response
		$response = ControllerResponse::create($this)
			->setBundle($bundle)
			->setEntityName($entity)
			->setTemplate('ITFAdminBundle:Admin:new.html.twig')
		;

		// set request data
		$entity = new \ITF\AdminBundle\Admin\Entity($entity, $this);
		$entity->setRequestData($request);

		// get form
		$form = $this->createActionForm($entity, 'add');
		$response->setForm($form->createView());

		// if template set
		if (isset($entity_config['template']['new'])) {
			$response->setTemplate($entity_config['template']['new']);
		}

		return $response->createResponse();
	}


	/**
	 * Displays a form to edit an existing entity.
	 *
	 * @param $bundle
	 * @param $entity
	 * @param $id
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function editAction($bundle, $entity, $id, Request $request)
	{
		$translatable = $this->container->get('gedmo.listener.translatable');
		$translatable->setTranslationFallback(false);

		// set locale
		$entity_locale = $this->getDefaultLocale();
		if (strlen($request->get('locale')) > 0) {
			$entity_locale = $request->get('locale');
		}

		// setup admin_helper
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		$ah->setEntity($entity);
		$entity_config = $this->get('itf.admin.config')->getEntityConfig();

		// setup reponse
		$response = ControllerResponse::create($this)
			->setBundle($bundle)
			->setEntityName($entity)
			->setTemplate('ITFAdminBundle:Admin:edit.html.twig')
		;

		// get entity
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository( $ah->getEntityRepository($entity) )->find($id);

		// locale
		$is_translatable = false;
		if (method_exists($entity, 'setTranslatableLocale')) {
			$entity->setTranslatableLocale($entity_locale);
			$em->refresh($entity);
			$is_translatable = true;
		}

		$entity = new \ITF\AdminBundle\Admin\Entity($entity, $this);
		if (!$entity) {
			throw $this->createNotFoundException(sprintf('Unable to find %e entity.', $entity->getName()));
		}


		$form = $this->createActionForm($entity, 'edit', $entity_locale);
		$deleteForm = $this->createDeleteForm($entity->getName(), $id);

		$response
			->setDeleteForm($deleteForm->createView())
			->setForm($form->createView())
			->setEntity($entity->getEntity())
			->setEntityAssoc($entity->getEntityAssociations())
			->setEntityTranslatable($is_translatable)
		;

		// if tree
		if ($this->get('itf.admin.annotation_reader')->isGedmoTree($entity->getEntity())) {
			$repo = $ah->getEntityRepositoryReference($entity->getName());

			// add tree to return array
			$response
				->setTreeHtml($this->get('itf.admin.tree')->getTreeListHTML($repo))
				->setTemplate('@ITFAdmin/Admin/tree/index.html.twig')
			;
		}

		// if template set
		if (isset($entity_config['template']['edit'])) {
			$response->setTemplate($entity_config['template']['edit']);
		}

		return $response->createResponse();
	}


	/**
	 * Edits an existing entity.
	 *
	 * @param $bundle
	 * @param Request $request
	 * @param $entity
	 * @param $id
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function updateAction($bundle, Request $request, $entity, $id)
	{
		// set locale
		$entity_locale = $this->getDefaultLocale();
		if (strlen($request->get('locale')) > 0) {
			$entity_locale = $request->get('locale');
		}

		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);

		// setup reponse
		$response = ControllerResponse::create($this)
			->setBundle($bundle)
			->setEntityName($entity)
			->setTemplate('ITFAdminBundle:Admin:edit.html.twig')
		;

		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository( $ah->getEntityRepository($entity) )->find($id);

		// locale
		$is_translatable = false;
		if (method_exists($entity, 'setTranslatableLocale')) {
			$entity->setTranslatableLocale($entity_locale);
			$em->refresh($entity);
			$is_translatable = true;
		}

		$entity = new \ITF\AdminBundle\Admin\Entity($entity, $this);
		$response
			->setEntity($entity->getEntity())
			->setEntityAssoc($entity->getEntityAssociations())
			->setEntityTranslatable($is_translatable)
		;

		if (!$entity) {
			throw $this->createNotFoundException(sprintf('Unable to find %e entity.', $entity->getName()));
		}

		$deleteForm = $this->createDeleteForm($entity->getName(), $id);
		$form = $this->createActionForm($entity, 'edit');
		$form->handleRequest($request);

		if ($form->isValid()) {
			$this->setLogging(true);
			$em->persist($entity->getEntity());
			$em->flush();
			$this->setLogging(false);

			// clear
			$em->clear();
			$em->getConnection()->getConfiguration()->setSQLLogger(null);
			gc_collect_cycles();

			// to edit
			if ($form->get('submit_stay')->isClicked()) {
				return $this->redirect($this->generateUrl('admin_edit', array(
					'id' => $id,
					'entity' => $entity->getName('strtolower'),
					'bundle' => $ah->getBundleNameShort(),
					'locale' => $entity_locale
				)));
			}

			// to list
			return $this->redirect($this->generateUrl('admin_list', array(
				'entity' => $entity->getName('strtolower'),
				'bundle' => $ah->getBundleNameShort()
			)));
		}

		$response
			->setForm($form->createView())
			->setDeleteForm($deleteForm->createView())
		;

		return $response->createResponse();
	}

	private function setLogging($bool)
	{
		$this->container->get('itf.listener.orm.logger')->setLogging($bool);

		//$this->container->get('session')->set('itf_admin.logging.enabled', $bool);
	}

	/**
	 * Deletes an entity.
	 *
	 * @param $bundle
	 * @param Request $request
	 * @param $entity
	 * @param $id
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteAction($bundle, Request $request, $entity, $id)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);

		$_entity = $entity;
		$form = $this->createDeleteForm($entity, $id);
		$form->handleRequest($request);

		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$entity = $em->getRepository( $ah->getEntityRepository($entity) )->find($id);

			if (!$entity) {
				throw $this->createNotFoundException(sprintf('Unable to find %e entity.', $entity->getName()));
			}

			// tree
			if ($this->get('itf.admin.annotation_reader')->isTree($entity)) {
				$repo = $ah->getEntityRepositoryReference($_entity);

				/* @var \ITF\AdminBundle\Admin\Tree\TreeInterface $entity */
				$this->get('itf.admin.tree')->deleteElement($entity, $repo);

			} else {
				$this->setLogging(true);
				$em->remove($entity);
				$em->flush();
				$this->setLogging(false);
			}
		}

		return $this->redirect($this->generateUrl('admin_list', array(
			'entity' => $_entity,
			'bundle' => $ah->getBundleNameShort()
		)));
	}

	/**
	 * @param Entity $entity
	 * @param $type
	 *
	 * @return \Symfony\Component\Form\Form
	 */
	private function createActionForm($entity, $type, $entity_locale = NULL)
	{
		$ah = $this->get('itf.admin_helper');
		$type_class = $entity->getFormTypeClass();

		switch($type) {
			default:
			case 'add':
				$method = 'POST';
				$action = $this->generateUrl('admin_create', array(
					'bundle' => $ah->getBundleNameShort(),
					'entity' => $entity->getName('strtolower')
				));
				$submit_label = 'Create';
				break;
			case 'edit':
				$method = 'PUT';
				$infoArray = array(
					'bundle' => $ah->getBundleNameShort(),
					'entity' => $entity->getName('strtolower'),
					'id' => $entity->getEntity()->getId()
				);
				if (!empty($entity_locale)) {
					$infoArray['locale'] = $entity_locale;
				}

				$action = $this->generateUrl('admin_update', $infoArray);
				$submit_label = 'Update';
				break;
		}

		// create form
		$form = $this->createForm(new $type_class($this->container), $entity->getEntity(), array(
			'action' => $action,
			'method' => $method,
			'attr' => array(
				'type' => $type
			)
		));

		// if tree add root
		/*if ($this->get('itf.admin.annotation_reader')->isGedmoTree($entity->getEntity())) {
			$repo = $ah->getEntityRepositoryReference($entity->getName());

			$this->get('itf.admin.gedmo.tree.form')->handleFormNew($form, $repo, $entity->getFQClassName());
		}*/

		$form->add('submit_stay', 'submit', array(
			'label' => $submit_label,
			'attr' => array('class' => 'btn btn-success')
		));

		$form->add('submit', 'submit', array(
			'label' => $submit_label.' & back',
			'attr' => array('class' => 'btn btn-success')
		));


		return $form;
	}

	/**
	 * Creates a form to delete a User entity by id.
	 *
	 * @param mixed $id The entity id
	 *
	 * @return \Symfony\Component\Form\Form The form
	 */
	private function createDeleteForm($entity, $id)
	{
		$ah = $this->get('itf.admin_helper');

		return $this->createFormBuilder()
			->setAction($this->generateUrl('admin_delete', array(
				'bundle' => $ah->getBundleNameShort(),
				'entity' => $ah->getEntityName($entity, 'strtolower'),
				'id' => $id
			)))
			->setMethod('DELETE')
			->add('submit', 'submit', array('label' => 'Delete', 'attr' => array('class' => 'btn btn-danger')))
			->getForm()
			;
	}

	public function entityLanguageSwitchAction($bundle, $entity, $id)
	{
		$locales = $this->getParameter('locales');
		$Entity = new \ITF\AdminBundle\Admin\Entity($entity, $this);

		$request = Request::createFromGlobals();
		$current_locale = $request->get('locale');
		if (empty($current_locale)) {
			$current_locale = $this->getDefaultLocale();
		}

		$items = array();
		foreach($locales as $locale) {
			$items[] = array(
				'label' => strtoupper($locale),
				'active' => ($locale == $current_locale),
				'url' => $this->generateUrl('admin_edit', array(
					'bundle' => $bundle,
					'entity' => $Entity->getName('strtolower'),
					'id' => $id,
					'locale' => $locale
				))
			);
		}

		return $this->render('@ITFAdmin/Admin/helper/bs.btn-groups.html.twig', array(
			'items' => $items
		));
	}

	private function getDefaultLocale()
	{
		return $this->getParameter('locale');
	}
}
