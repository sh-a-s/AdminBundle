<?php
namespace ITF\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMenuController extends Controller
{
	public function listEntitiesAction($bundle, $current_entity = NULL, $_route = NULL)
	{
		$ah = $this->get('itf.admin_helper');
		$ah->setBundle($bundle);

		$url = $this->generateUrl('admin_dashboard', array(
			'bundle' => $bundle
		));

		return $this->render('ITFAdminBundle:Admin:menu.html.twig', array(
			'menu' => $this->get('itf.admin.menu')->createAdminMenu($current_entity),
			'bundle' => $bundle,
			'_route' => @$_route
		));
	}
	
	public function topbarNavigationAction()
	{
		$config = $this->get('itf.admin.config')->getConfig();
		$frontend_route = isset($config['frontend_route']) ? $config['frontend_route'] : 'homepage';
		
		return $this->render('@ITFAdmin/Admin/topbar/navigation.html.twig', array(
			'frontend_route' => $frontend_route
		));
	}
}