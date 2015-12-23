<?php
namespace ITF\AdminBundle\Admin\Tree;

use Doctrine\ORM\EntityRepository;
use ITF\AdminBundle\Admin\Service\AbstractServiceSetter;
use ITF\AdminBundle\Admin\Tree\TreeInterface;

class Tree extends AbstractServiceSetter
{
	private $list_type = 'ol';


	/**
	 * @param EntityRepository $repository
	 *
	 * @return TreeInterface[] array
	 */
	public function getNodes(EntityRepository $repository)
	{
		return $repository->findBy(array(), array('lft' => 'ASC'));
	}


	/**
	 * @param EntityRepository $repository
	 *
	 * @return string
	 */
	public function getTreeListHTML(EntityRepository $repository)
	{
		$entries = $this->getNodes($repository);

		return $this->generateListHTML($entries, null);
	}


	/**
	 * @param TreeInterface[] $entries
	 * @param $level
	 *
	 * @return string
	 */
	public function generateListHTML($entries, $level)
	{
		$html = '';

		foreach($entries as $entry) {
			if ($entry->getParentId() == $level ) {
				$html .= '<li class="dd-item" id="item_' . $entry->getId() . '" data-id="' . $entry->getId() . '">';
				$html .= '<div class="dd-handle drag"><a class="sortable-label" href="#">' . $entry->getLabel() . '</a></div>';
				$html .= $this->generateListHTML($entries, $entry->getId());
				$html .= '</li>';
			}
		}

		return ($html == '' ? '' : '<ol class="dd-list">'. $html . "</ol>");
	}


	/**
	 * @param EntityRepository $repository
	 *
	 * @return array
	 */
	public function getTree(EntityRepository $repository)
	{
		$entries = $this->getNodes($repository);

		$stack = array();
		$arraySet = array();

		foreach($entries as $intKey => $entry) {
			$stackSize = count($stack); //how many opened tags?
			while($stackSize > 0 && $stack[$stackSize-1]['rgt'] < $entry->getLft()) {
				array_pop($stack); //close sibling and his childrens
				$stackSize--;
			}

			$link =& $arraySet;
			for ($i = 0; $i < $stackSize; $i++) {
				$link =& $link[$stack[$i]['index']]["children"]; //navigate to the proper children array
			}
			$tmp = array_push($link, array(
				'item' => $entry,
				'children' => array()
			));
			array_push($stack, array(
				'index' => ($tmp - 1),
				'rgt' => $entry->getRgt()
			));
		}

		return $arraySet;
	}

	public function saveState($nodesArray, EntityRepository $repository)
	{
		$em = $this->getEntityManager();

		foreach($nodesArray as $key => $node) {
			$entry = $repository->find($node['id']);

			// if not found, create new
			if ($entry === NULL) {
				$class = $repository->getClassName();
				$entry = new $class();
			}

			/* @var TreeInterface $entry */
			$entry
				->setLabel($node['label'])
				->setLft($node['lft'])
				->setRgt($node['rgt'])
				->setDepth($node['depth'])
			;

			/* @var TreeInterface|NULL $parent */
			$parent = strlen($node['parent_id'] > 0)
				? $repository->find($node['parent_id'])
				: NULL
			;
			$entry->setParent($parent);

			$em->persist($entry);
			$em->flush();
		}
	}
}