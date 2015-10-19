<?php

namespace ITF\AdminBundle\Controller;

use ITF\AdminBundle\Admin\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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

        return $ah->getDatatable($entity, $request)->setDatatableId($table_id);
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
     * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
    public function indexAction($bundle, $entity, $join_context = false, $context = array(), $table_id = 0, Request $request)
    {
        $ah = $this->get('itf.admin_helper');
        $ah->setBundle($bundle);

        // table id
        if (empty($table_id)) {
            $table_id = 'dt-' . $entity . time();
        }

        // init dt
		$this->_datatable($bundle, $entity, $table_id);

        // switch template
        $template = 'ITFAdminBundle:Admin:index.html.twig';
        if ($join_context) {
            $template = 'ITFAdminBundle:Admin:datatable.html.twig';
        }

		return $this->render($template, array(
            'table_id' => $table_id,
            'bundle' => $ah->getBundleNameShort(),
			'entity' => $ah->getEntityName($entity, 'strtolower'),
			'entity_name' => $ah->getEntityName($entity, 'strtolower'),
            'context' => (!empty($context)) ? $context : $ah->dtGetContext($request)
		));
    }

    public function dashboardAction($bundle)
    {
        return $this->render('ITFAdminBundle::admin_base.html.twig', array(
            'bundle' => $bundle,
            'entity' => NULL,
            'entity_name' => NULL
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

        $em = $this->getDoctrine()->getManager();

        $entity = new Entity($entity, $this);
        $form = $this->createActionForm($entity, 'add');
        $form->handleRequest($request);

        if ($form->isValid()) {
            // upload
            $entityUpload = new EntityUpload($entity->getEntity());
            $entityUpload->upload();

            $em->persist($entity->getEntity());
            $em->flush();
            $em->clear();

            // to edit
            if ($form->get('submit_stay')->isClicked()) {
                return $this->redirect($this->generateUrl('admin_edit', array(
                    'id' => $entity->getEntity()->getId(),
                    'entity' => $entity->getName('strtolower'),
                    'bundle' => $ah->getBundleNameShort(),
                    /*'entity_name'   => $entity->getName('strtolower')*/
                )));
            }

            // to list
            return $this->redirect($this->generateUrl('admin_list', array(
                'entity' => $entity->getName('strtolower'),
                'bundle' => $ah->getBundleNameShort(),
                /*'entity_name'   => $entity->getName('strtolower')*/
            )));
        }

        return $this->render('ITFAdminBundle:Admin:new.html.twig', array(
            'entity' => $entity->getEntity(),
            'form'   => $form->createView(),
            'bundle' => $ah->getBundleNameShort(),
            'entity_name'   => $entity->getName('strtolower')
        ));
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

        $entity = new Entity($entity, $this);

        // set request data
        $entity->setRequestData($request);

        // get form
		$form = $this->createActionForm($entity, 'add');

        return $this->render('ITFAdminBundle:Admin:new.html.twig', array(
            'entity'        => $entity->getEntity(),
            'bundle'        => $ah->getBundleNameShort(),
            'form'          => $form->createView(),
            'entity_name'   => $entity->getName('strtolower')
        ));
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
        // set locale
        $entity_locale = $this->default_locale;
        if (strlen($request->get('locale')) > 0) {
            $entity_locale = $request->get('locale');
        }

		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository( $ah->getEntityRepository($entity) )->find($id);

        // locale
		$is_translatable = false;
        if (method_exists($entity, 'setTranslatableLocale')) {
            $entity->setTranslatableLocale($entity_locale);
            $em->refresh($entity);
			$is_translatable = true;
        }

        $entity = new Entity($entity, $this);

        if (!$entity) {
            throw $this->createNotFoundException(sprintf('Unable to find %e entity.', $entity->getName()));
        }

		$form = $this->createActionForm($entity, 'edit', $entity_locale);
        $deleteForm = $this->createDeleteForm($entity->getName(), $id);

        return $this->render('ITFAdminBundle:Admin:edit.html.twig', array(
            'entity'      => $entity->getEntity(),
            'bundle'      => $ah->getBundleNameShort(),
            'form'        => $form->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity_assoc'=> $entity->getEntityAssociations(),
            'entity_name' => $entity->getName('strtolower'),
			'entity_translatable' => $is_translatable
        ));
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
        $entity_locale = $this->default_locale;
        if (strlen($request->get('locale')) > 0) {
            $entity_locale = $request->get('locale');
        }

		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository( $ah->getEntityRepository($entity) )->find($id);

        // locale
        if (method_exists($entity, 'setTranslatableLocale')) {
            $entity->setTranslatableLocale($entity_locale);
        }

        $entity = new Entity($entity, $this);

		if (!$entity) {
			throw $this->createNotFoundException(sprintf('Unable to find %e entity.', $entity->getName()));
		}

		$deleteForm = $this->createDeleteForm($entity->getName(), $id);
		$form = $this->createActionForm($entity, 'edit');
		$form->handleRequest($request);

		if ($form->isValid()) {
            // upload
            $entityUpload = new EntityUpload($entity->getEntity());
            $entityUpload->upload();

            //$em->persist($entity);
			$em->flush();

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

        return $this->render('ITFAdminBundle:Admin:edit.html.twig', array(
            'entity'      => $entity->getEntity(),
            'bundle'      => $ah->getBundleNameShort(),
            'edit_form'   => $form->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
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

            $em->remove($entity);
            $em->flush();
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
		$form = $this->createForm(new $type_class(), $entity->getEntity(), array(
			'action' => $action,
			'method' => $method,
			'attr' => array(
				'type' => $type
			)
		));

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
		$locales = array('en', 'de', 'fr', 'it');
		$Entity = new Entity($entity, $this);

		$request = Request::createFromGlobals();
		$current_locale = $request->get('locale');
		if (empty($current_locale)) {
			$current_locale = $this->default_locale;
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
}
