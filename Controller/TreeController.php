<?php
namespace ITF\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
}