<?php
namespace ITF\AdminBundle\Admin\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractServiceSetter
{
	/* @var ContainerInterface $container */
	protected $container;

	public function __construct(ContainerInterface $container)
	{
		$this->setContainer($container);
	}

	protected function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getEntityManager()
	{
		return $this->container->get('doctrine.orm.default_entity_manager');
	}

	/**
	 * @return \ITF\YamlConfigBundle\Yaml\YamlConfig
	 */
	public function yconf()
	{
		return $this->container->get('itf.yconf');
	}

	/**
	 * @return ContainerInterface
	 */
	protected function getContainer()
	{
		return $this->container;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Session\Session
	 */
	protected function getSession()
	{
		return $this->container->get('session');
	}

	/**
	 * @return \Symfony\Component\Form\FormFactory
	 */
	protected function getFormFactory()
	{
		return $this->container->get('form.factory');
	}

	/**
	 * @param $type
	 * @param null $data
	 * @param array $options
	 *
	 * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
	 */
	public function createForm($type, $data = null, array $options = array())
	{
		return $this->getFormFactory()->create($type, $data, $options);
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	protected function getRequest()
	{
		return $this->container->get('request');
	}

	/**
	 * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
	 */
	protected function getRouter()
	{
		return $this->container->get('router');
	}

	/**
	 * @param $route
	 * @param array $parameters
	 * @param bool $referenceType
	 *
	 * @return string
	 */
	protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
	{
		return $this->getRouter()->generate($route, $parameters, $referenceType);
	}
}