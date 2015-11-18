<?php

namespace ITF\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogEntry
 *
 * @ORM\Table("admin__log")
 * @ORM\Entity
 */
class LogEntry
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime")
     */
    private $timestamp;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="event", type="string", length=255)
     */
    private $event;

    /**
     * @var string
     *
     * @ORM\Column(name="entity", type="string", length=255))
     */
    private $entity;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity_fk", type="integer")
     */
    private $entity_fk;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    public function __construct()
    {
        $this->setTimestamp(new \DateTime('now'));
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return LogEntry
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime 
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return LogEntry
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set event
     *
     * @param string $event
     * @return LogEntry
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return string 
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return LogEntry
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set entity
     *
     * @param string $entity
     * @return LogEntry
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return string 
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set entity_fk
     *
     * @param integer $entityFk
     * @return LogEntry
     */
    public function setEntityFk($entityFk)
    {
        $this->entity_fk = $entityFk;

        return $this;
    }

    /**
     * Get entity_fk
     *
     * @return integer 
     */
    public function getEntityFk()
    {
        return $this->entity_fk;
    }
}
