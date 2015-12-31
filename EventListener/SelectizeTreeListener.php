<?php
namespace ITF\AdminBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use ITF\AdminBundle\Admin\Tree\TreeInterface;

class SelectizeTreeListener implements EventSubscriber
{
	public function getSubscribedEvents()
	{
		return array(
			Events::prePersist,
			Events::preUpdate
		);
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
		$entity = $args->getEntity();
		$em = $args->getEntityManager();


	}
}

