<?php
namespace ITF\AdminBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use ITF\AdminBundle\Admin\Tree\TreeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SelectizeTreeListener implements EventSubscriber
{
	/* @var ContainerInterface  */
	private $container;

	public function getSubscribedEvents()
	{
		return array(
			Events::prePersist,
			Events::preUpdate
		);
	}

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function prePersist(LifecycleEventArgs $args)
	{
		if ($args->getEntity() instanceof TreeInterface) {
			$this->handleSelectizeTree($args);
		}
	}

	public function preUpdate(LifecycleEventArgs $args)
	{
		if ($args->getEntity() instanceof TreeInterface) {
			$this->handleSelectizeTree($args);
		}
	}

	public function handleSelectizeTree(LifecycleEventArgs $args)
	{
		/* @var \ITF\AdminBundle\Admin\Tree\AbstractTreeEntity $entity */
		$entity = $args->getEntity();
		$em = $args->getEntityManager();
		/*$entity_info = $this->container->get('itf.admin_helper')->getEntityInfo(get_class($entity));
		$repositoy = $em->getRepository($entity_info['repository']);*/
		//pre($entity_info);

		// if bulk_add provided
		/*$bulk_add = $entity->getBulkAdd();
		if ($bulk_add) {
			$entity->setBulkAdd(null);
			$entries = explode("\n", str_replace("\r", "", $bulk_add));

			//exit;

		}*/


	}
}

