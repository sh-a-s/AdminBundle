<?php
namespace ITF\AdminBundle\Admin;

use AppBundle\Library\PN\AbstractServiceSetter;
use ITF\AdminBundle\Admin\Dashboard\DashboardInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Configuration extends AbstractServiceSetter
{
	private $config;
	private $bundle_config;
	private $ah;

	/**
	 * Configuration constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->setContainer($container);
		$this->ah = $this->getContainer()->get('itf.admin_helper');
		$this->config = $this->getContainer()->getParameter('itf_admin');

		// set bundle config
		if (isset($this->getConfig()['bundles']) && isset($this->getConfig()['bundles'][ $this->ah->getBundleNameShort() ])) {
			$this->bundle_config = $this->getConfig()['bundles'][ $this->ah->getBundleNameShort() ];
		}
	}


	/**
	 * Get dashboard class from current bundle
	 *
	 * @return false|DashboardInterface
	 */
	public function getDashboardService()
	{
		if (isset($this->getBundleConfig()['dashboard_service'])) {
			$dashboard_service = $this->getContainer()->get($this->getBundleConfig()['dashboard_service']);

			if (!$dashboard_service instanceof DashboardInterface) {
				throw new Exception(sprintf('Dashboard service "%s" configuration under itf_admin.bundles.[bundle].dashboard_service has to implement DashboardInterface', $this->getBundleConfig()['dashboard_service']));
			}

			return $dashboard_service;
		}

		return NULL;
	}


	/**
	 * Return AdminBundle Config
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return $this->config;
	}


	/**
	 * Return AdminBundle bundle config of current bundle
	 *
	 * @return array
	 */
	public function getBundleConfig()
	{
		return $this->bundle_config;
	}
}