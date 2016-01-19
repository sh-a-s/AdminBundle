<?php
namespace ITF\AdminBundle\Admin\Tree;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractTreeEntity implements TreeInterface
{
	/**
	 * @ORM\Column(type="integer", name="id")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=64, nullable=true, name="label")
	 */
	protected $label;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="lft")
	 */
	protected $lft;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="depth")
	 */
	protected $depth;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="rgt")
	 */
	protected $rgt;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $updated_at;

	protected $parent;

	protected $bulk_add;


	public function getBulkAdd()
	{
		return $this->bulk_add;
	}

	public function setBulkAdd($bulk_add)
	{
		$this->bulk_add = $bulk_add;

		return $this;
	}


	public function getId()
	{
		return $this->id;
	}

	public function setLabel($title)
	{
		$this->label = $title;

		return $this;
	}

	public function getLabel()
	{
		return $this->label;
	}

	public function setParent(TreeInterface $parent = null)
	{
		$this->parent = $parent;

		return $this;
	}

	/**
	 * @return TreeInterface
	 */
	public function getParent()
	{
		return $this->parent;
	}

	public function getParentId()
	{
		if ($this->getParent() !== NULL) {
			return $this->getParent()->getId();
		}

		return null;
	}

	public function setLft($lft)
	{
		$this->lft = $lft;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getLft()
	{
		return $this->lft;
	}

	/**
	 * @param mixed $depth
	 *
	 * @return TreeInterface
	 */
	public function setDepth($depth)
	{
		$this->depth = $depth;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDepth()
	{
		return $this->depth;
	}

	/**
	 * @return mixed
	 */
	public function getRgt()
	{
		return $this->rgt;
	}

	/**
	 * @param mixed $rgt
	 *
	 * @return TreeInterface
	 */
	public function setRgt($rgt)
	{
		$this->rgt = $rgt;

		return $this;
	}

	/**
	 * Set updated_at
	 *
	 * @param \DateTime $updatedAt
	 *
	 * @return $this
	 */
	public function setUpdatedAt($updatedAt)
	{
		$this->updated_at = $updatedAt;

		return $this;
	}

	/**
	 * Get updated_at
	 *
	 * @return \DateTime
	 */
	public function getUpdatedAt()
	{
		return $this->updated_at;
	}

	public function __toString()
	{
		return (string) $this->getLabel();
	}
}
