<?php
namespace ITF\AdminBundle\Admin\Tree;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
abstract class AbstractGedmoTreeEntity
{
	/**
	 * @Gedmo\TreeParent()
	 */
	protected $parent;

	/**
	 * @ORM\OrderBy({"lft" = "ASC"})
	 */
	protected $children;

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
	 * @Gedmo\TreeLeft()
	 * @ORM\Column(name="lft", type="integer")
	 */
	protected $lft;

	/**
	 * @Gedmo\TreeLevel()
	 * @ORM\Column(name="lvl", type="integer")
	 */
	protected $lvl;

	/**
	 * @Gedmo\TreeRight()
	 * @ORM\Column(name="rgt", type="integer")
	 */
	protected $rgt;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $updated_at;

	/**
	 * @Gedmo\TreeRoot
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $root;



	public function getRoot()
	{
		return $this->root;
	}

	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @return mixed
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param mixed $label
	 *
	 * @return $this
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param mixed $id
	 *
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;

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

	/**
	 * @param mixed $parent
	 *
	 * @return $this
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @param mixed $children
	 *
	 * @return $this
	 */
	public function setChildren($children)
	{
		$this->children = $children;

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
	 * @return mixed
	 */
	public function getRgt()
	{
		return $this->rgt;
	}

	/**
	 * @return mixed
	 */
	public function getLvl()
	{
		return (int) $this->lvl;
	}

	/**
	 * @param mixed $lvl
	 *
	 * @return AbstractGedmoTreeEntity
	 */
	public function setLvl($lvl)
	{
		$this->lvl = $lvl;

		return $this;
	}

	protected $indented_label;

	public function getIndentedLabel()
	{
		return str_repeat(" - ", $this->getLvl()). ' ' . $this->getLabel();
	}
}
