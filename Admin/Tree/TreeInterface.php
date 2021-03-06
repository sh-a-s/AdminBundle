<?php
namespace ITF\AdminBundle\Admin\Tree;

interface TreeInterface
{
	public function getId();

	/**
	 * @param $lft
	 *
	 * @return $this
	 */
	public function setLft($lft);
	public function getLft();

	/**
	 * @param $rgt
	 *
	 * @return $this
	 */
	public function setRgt($rgt);
	public function getRgt();


	/**
	 * @param $depth
	 *
	 * @return $this
	 */
	public function setDepth($depth);
	public function getDepth();

	/**
	 * @param $label
	 *
	 * @return $this
	 */
	public function setLabel($label);
	public function getLabel();

	/**
	 * @param TreeInterface $parent
	 *
	 * @return TreeInterface|null
	 */
	public function setParent(TreeInterface $parent);
	public function getParent();
	public function getParentId();
}