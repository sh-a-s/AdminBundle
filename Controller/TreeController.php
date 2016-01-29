<?php
namespace ITF\AdminBundle\Controller;

use AppBundle\Entity\Category;
use Doctrine\ORM\EntityRepository;
use Gedmo\Tree\Entity\Repository\AbstractTreeRepository;
use ITF\AdminBundle\Admin\Controller\ControllerResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TreeController
 * @package ITF\AdminBundle\Controller
 */
class TreeController extends Controller
{
	public function bulkAddAction(Request $request, $bundle, $entity)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		$ah->setEntity($entity);
		$entity = $ah->getEntityInstance($entity);

		// setup reponse
		$response = ControllerResponse::create($this)
			->setBundle($bundle)
			->setEntityName($entity)
			->setTemplate('ITFAdminBundle:Admin:edit.html.twig')
		;

		// get entity
		$em = $this->getDoctrine()->getManager();
		$entity = new \ITF\AdminBundle\Admin\Entity($entity, $this);
		if (!$entity) {
			throw $this->createNotFoundException(sprintf('Unable to find %e entity.', $entity->getName()));
		}

		// set id
		$response->setEntityId($entity->getEntity()->getId());

		// if tree
		if ($this->get('itf.admin.annotation_reader')->isGedmoTree($entity->getEntity())) {
			/* @var AbstractTreeRepository $repo */
			$repo = $ah->getEntityRepositoryReference($entity->getName());

			//pre($this->get('itf.admin.tree')->getFlatTree($repo, 29));


			// create bulk add form
			$bulkadd_form = $this->createFormBuilder(null, array(
				//'action' => $this->generateUrl('admin_tree_bulkadd_insert', $response->generateSimpleArray())
			))
				->add('parent', 'entity', array(
					'class' => $repo->getClassName()
				))
				->add('bulk_add', 'textarea', array(
					'attr' => array(
						'rows' => 10
					)
				))
				->add('submit', 'submit', array(
					'attr' => array('class' => 'btn btn-success')
				))
				->getForm();

			// post
			if ($request->isMethod('POST')) {
				$bulkadd_form->handleRequest($request);

				// get mapped data
				$bulkadd_parent = $bulkadd_form->get('parent')->getData();
				$bulkadd_entries = $bulkadd_form->get('bulk_add')->getData();
				$bulkadd_entries = preg_split('/[\r\n]+/', $bulkadd_entries, -1, PREG_SPLIT_NO_EMPTY);

				// go through entries
				if (count($bulkadd_entries) > 0 && is_object($bulkadd_parent)) {
					$entry_class = get_class($bulkadd_parent);

					foreach($bulkadd_entries as $entry) {
						$newEntry = new $entry_class();
						$newEntry
							->setLabel($entry)
							->setParent($bulkadd_parent)
						;

						$em->persist($newEntry);
					}
					$em->flush();
				}
			}

			// add data
			$response->addData('bulkadd_form', $bulkadd_form->createView());

			// add tree to return array
			$response
				->setFuncType('bulkadd')
				->setTreeHtml($this->get('itf.admin.tree')->getTreeListHTML($repo))
				->setTemplate('@ITFAdmin/Admin/tree/index.html.twig')
			;
		}

		return $response->createResponse();
	}













	/**
	 * @Route("/test")
	 * @return Response
	 */
	public function treetest()
	{
		$em = $this->getDoctrine()->getManager();

		$repo = $em->getRepository('AppBundle:Category');
		$options = array(
			'decorate' => true,
			'rootOpen' => '<ul>',
			'rootClose' => '</ul>',
			'childOpen' => '<li>',
			'childClose' => '</li>',
			'nodeDecorator' => function($node) {
				return '<a href="/page/'.$node['id'].'">'.$node['label'].'</a>';
			}
		);
		$htmlTree = $repo->childrenHierarchy(
			null,
			false,
			$options
		);

		/*$food = new Category();
		$food->setLabel('Food');

		$fruits = new Category();
		$fruits->setLabel('Fruits');
		$fruits->setParent($food);

		$vegetables = new Category();
		$vegetables->setLabel('Vegetables');
		$vegetables->setParent($food);

		$carrots = new Category();
		$carrots->setLabel('Carrots');
		$carrots->setParent($vegetables);

		$em->persist($food);
		$em->persist($fruits);
		$em->persist($vegetables);
		$em->persist($carrots);
		$em->flush();*/




		return $this->render('ITFAdminBundle:Default:text.html.twig', array(
			'bundle' => 'app',
			'entity_name' => 'category',
			'dashboard_title' => 'asdf',
			'tree_html' => $htmlTree
		));
	}

	/**
	 * @Route("/save_state/{bundle}/{entity}", name="api_admin_tree_save_state")
	 * @param Request $request
	 *
	 * @param $bundle
	 * @param $entity
	 *
	 * @return Response
	 */
	public function saveTreeStateAction(Request $request, $bundle, $entity)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		$ah->setEntity($entity);
		$repo = $ah->getEntityRepositoryReference($entity);

		$nodesArray = $request->request->get('array');

		$this->get('itf.admin.tree')->saveState($nodesArray, $repo);

		return new Response('ok');
	}

	/**
	 * @Route("/get_tree/{bundle}/{entity}/{format}/{type}", name="api_admin_tree_html", defaults={
	 *     "format": "html",
	 *     "type": "hierarchy"
	 * })
	 *
	 * @param Request $request
	 * @param $bundle
	 * @param $entity
	 * @param $format
	 * @param $type
	 *
	 * @return Response
	 */
	public function getTreeAction(Request $request, $bundle, $entity, $format, $type)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		$ah->setEntity($entity);
		$repo = $ah->getEntityRepositoryReference($entity);

		switch($format) {
			case 'json':
				switch($type) {
					case 'flat':
						$json = $this->get('itf.admin.tree')->getFlatTree($repo);
						break;
					default:
					case 'hierarchy':
						$json = $this->get('itf.admin.tree')->getTree($repo);
						break;
				}
				return new JsonResponse($json);
				break;
			default:
			case 'html':
				return new Response($this->get('itf.admin.tree')->getTreeListHTML($repo));
				break;
		}
	}

	/**
	 * @Route("/add_element/{bundle}/{entity}/{id}", name="api_admin_tree_add", defaults={"id" = 0})
	 * @param int $id
	 * @param $bundle
	 * @param $entity
	 *
	 * @return Response
	 */
	public function addElementAction($id = 0, $bundle, $entity)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);
		$ah->setEntity($entity);
		$repo = $ah->getEntityRepositoryReference($entity);

		$id = $this->get('itf.admin.tree')->addElement($id, $repo);

		return new JsonResponse($id);
	}
}