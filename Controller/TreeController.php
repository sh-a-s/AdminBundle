<?php
namespace ITF\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TreeController
 * @package ITF\AdminBundle\Controller
 *
 * @Route("/tree")
 */
class TreeController extends Controller
{
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