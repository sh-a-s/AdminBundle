<?php
namespace ITF\AdminBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use FOS\UserBundle\Model\UserInterface;
use ITF\AdminBundle\Entity\LogEntry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoggingListener implements EventSubscriber
{
	/* @var ContainerInterface  */
	private $container;

	/* @var \Doctrine\ORM\EntityManager*/
	private $em;

	/* @var \Doctrine\ORM\UnitOfWork $uow */
	private $uow;

	private $entity;


	public function getSubscribedEvents()
	{
		return array(
			Events::postPersist,
			Events::postUpdate,
			Events::postRemove
		);
	}

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function skipPropertyChange()
	{
		return array(
			'updated_at'
		);
	}

	protected function resolveArguments(LifecycleEventArgs $args)
	{
		$this->entity = $args->getObject();
		$this->em = $args->getEntityManager();
		$this->uow = $this->em->getUnitOfWork();
	}

	/**
	 * @return integer
	 */
	private function getUserId()
	{
		$user = $this->container->get('security.token_storage')->getToken()->getUser();

		return ($user instanceof UserInterface)
				? (int) $user->getId()
				: 0;
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	private function formatValue($value)
	{
		if (is_array($value)) {
			return implode(', ', $value);
		}
		elseif ($value instanceof \DateTime) {
			return $value->format('d.m.Y H:i:s');
		}
		elseif (is_object($value)) {
			if (method_exists($value, '__toString')) {
				return $value->__toString();
			} else {
				return NULL;
			}
		}

		return $value;
	}

	private function getEntityId()
	{
		$id = $this->entity->getId();

		if (strlen($id) == 0) {
			return 0;
		}

		return $id;
	}

	/**
	 * @param $event
	 * @param null $message
	 *
	 * @return LogEntry
	 */
	private function createLogEntry($event, $message = NULL)
	{
		$log = new LogEntry();
		$log
			->setEvent($event)
			->setUserId($this->getUserId())
			->setEntity(get_class($this->entity))
			->setEntityFk($this->getEntityId())
			->setMessage($message)
		;

		$this->em->persist($log);
		$this->em->flush();
	}

	public function check()
	{
		return !$this->entity instanceof LogEntry && is_object($this->container->get('security.token_storage')->getToken());
	}

	public function postPersist(LifecycleEventArgs $args)
	{
		$this->resolveArguments($args);
		if ($this->check()) {

			// get name
			$entity_name = NULL;
			if (method_exists($this->entity, '__toString')) {
				$entity_name = '"' . $this->entity->__toString() . '"';
			}

			$message = sprintf('Created entry %s', $entity_name);
			$this->createLogEntry(Events::postPersist, $message);
		}
	}


	public function postUpdate(LifecycleEventArgs $args)
	{
		$this->resolveArguments($args);

		if ($this->check()) {
			foreach ($this->uow->getEntityChangeSet($this->entity) as $property => $change) {
				list($before, $after) = $change;

				$this->formatValue($before);
				$this->formatValue($after);

				// if not to be skipped
				if (!in_array($property, $this->skipPropertyChange())) {
					$message = sprintf(
							'Changed "%s" from "%s" to "%s"',
							$property,
							$this->formatValue($before),
							$this->formatValue($after)
					);

					$this->createLogEntry(Events::postUpdate, $message);
				}
			}
		}
	}

	public function postRemove(LifecycleEventArgs $args)
	{
		$this->resolveArguments($args);

		if (!$this->entity instanceof LogEntry) {

			// get name
			$entity_name = NULL;
			if (method_exists($this->entity, '__toString')) {
				$entity_name = '"' . $this->entity->__toString() . '"';
			}

			$message = sprintf('Removed entry %s', $entity_name);
			$this->createLogEntry(Events::postRemove, $message);
		}
	}
}