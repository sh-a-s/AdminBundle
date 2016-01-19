<?php
namespace ITF\AdminBundle\Admin\Tree;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\Entity;
use ITF\AdminBundle\Admin\Service\AbstractServiceSetter;
use ITF\AdminBundle\Admin\Tree\TreeInterface;

class Tree extends AbstractServiceSetter
{
	private $tree_url_function;




	/**
	 * @param EntityRepository $repository
	 *
	 * @param array $criteria
	 * @param array $order_by
	 * @param null $limit
	 * @param null $offset
	 *
	 * @return TreeInterface[] array
	 */
	public function getNodes(EntityRepository $repository, $criteria = array(), $order_by = array('lft' => 'ASC'), $limit = NULL, $offset = NULL)
	{
		return $repository->findBy($criteria, $order_by, $limit, $offset);
	}


	/**
	 * @param EntityRepository $repository
	 *
	 * @param int $active_id
	 *
	 * @return string
	 */
	public function getTreeListHTML(EntityRepository $repository, $active_id = 0)
	{
		$entries = $this->getNodes($repository);

		if ($active_id == 0) {
			$active_id = (int) $this->getRequest()->attributes->get('id');
		}

		return $this->generateListHTML($entries, null, $active_id);
	}


	/**
	 * @param TreeInterface[] $entries
	 * @param $level
	 * @param int $active_id
	 * @param null $edit_path_func
	 *
	 * @return string
	 */
	public function generateListHTML($entries, $level, $active_id = 0, $edit_path_func = NULL)
	{
		$html = '';

		foreach($entries as $entry) {
			if ($entry->getParentId() == $level ) {
				$id = $entry->getId();

				if ($edit_path_func instanceof \Closure) {
					$edit_path = $edit_path_func($entry);
				} else {
					$edit_path = $this->getContainer()->get('itf.admin_helper')->getEditPath($id);
				}

				$active = $id == $active_id
					? 'dd-active'
					: null;

				$html .= '<li class="dd-item ' .$active. '" id="item_' . $id . '" data-id="' . $id . '">';
				$html .= '<div class="dd-handle drag"><a class="sortable-label" href="'.$edit_path.'">' . $entry->getLabel() . '</a></div>';
				$html .= $this->generateListHTML($entries, $id, $active_id);
				$html .= '</li>';
			}
		}

		return ($html == '' ? '' : '<ol class="dd-list">'. $html . "</ol>");
	}


	/**
	 * @param EntityRepository $repository
	 *
	 * @param null $nodes
	 *
	 * @return array
	 */
	public function getTree(EntityRepository $repository, $nodes = null)
	{
		if ($nodes === NULL) {
			$entries = $this->getNodes($repository);
		} else {
			$entries = $nodes;
		}

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
				'item' => $this->treeInterfaceObjectToArray($entry),
				'children' => array()
			));
			array_push($stack, array(
				'index' => ($tmp - 1),
				'rgt' => $entry->getRgt()
			));
		}

		return $arraySet;
	}

	public function getFlatTree(EntityRepository $repository, $nodes = null)
	{
		if ($nodes === NULL) {
			$entries = $this->getNodes($repository);
		} else {
			$entries = $nodes;
		}

		foreach($entries as $key => $entry) {
			$entries[$key] = $this->treeInterfaceObjectToArray($entry);
		}

		return $entries;
	}

	protected function treeInterfaceObjectToArray(TreeInterface $entry)
	{
		return array(
			'id' => $entry->getId(),
			'label' => $entry->getLabel(),
			'lft' => $entry->getLft(),
			'rgt' => $entry->getRgt(),
			'depth' => $entry->getDepth(),
			'parent_id' => $entry->getParentId()
		);
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

	public function deleteElement(TreeInterface $entry, EntityRepository $repository)
	{
		$em = $this->getEntityManager();
		$qb = $repository->createQueryBuilder('a');
		$queries = array();
		$params = array(
			'lft' => $entry->getLft(),
			'rgt' => $entry->getRgt()
		);

		// if has children
		if ($entry->getRgt() - $entry->getLft() > 1) {
			$queries[] = $qb
				->delete()
				->where('a.lft BETWEEN :lft AND :rgt')
				->setParameters($params)
				->getQuery()
			;

			// update lft
			$qb
				->update()
				->set('a.lft', 'a.lft - :diff')
				->where('a.lft > :rgt')
				->setParameters(array(
					'rgt' => $entry->getRgt(),
					'diff' => round($entry->getRgt() - $entry->getLft() + 1)
				))
				->getQuery()
			;

			// update rgt
			$queries[] = $qb
				->update()
				->set('a.rgt', 'a.rgt - :diff')
				->where('a.rgt > :rgt')
				->setParameters(array(
					'rgt' => $entry->getRgt(),
					'diff' => round($entry->getRgt() - $entry->getLft() + 1)
				))
				->getQuery()
			;
		}

		foreach($queries as $query) {
			/* @var \Doctrine\ORM\Query $query */
			$query->getResult();
		}

		$em->remove($entry);
		$em->flush();
	}

	/**
	 * @param int $id
	 * @param EntityRepository $repository
	 *
	 * @param null $label
	 *
	 * @return bool
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function addElement($id = 0, EntityRepository $repository, $label = NULL)
	{
		$em = $this->getEntityManager();
		$qb = $repository->createQueryBuilder('a');
		$class = $repository->getClassName();

		/* @var TreeInterface $entry */
		$entry = new $class();
		if (!empty($label)) {
			$entry->setLabel($label);
		}

		if ($id == 0) {
			// get max_right
			$query = $qb
				->select('MAX(a.rgt) as max_rgt')
				->getQuery()
				->getSingleResult()
			;

			if (isset($query['max_rgt'])) {
				$lft = $query['max_rgt'];
				$rgt = $lft + 1;

				/* @var TreeInterface $entry */
				$entry
					->setLft($lft)
					->setRgt($rgt)
					->setDepth(1)
				;

				$em->persist($entry);
				$em->flush();

				return $entry->getId();
			}

		// add to parent
		} else {
			/* @var TreeInterface|NULL $parent */
			$parent = $repository->find($id);

			//dump($parent);

			if ($parent !== NULL) {
				$lft = $parent->getRgt();
				$rgt = $lft + 1;

				$queries = array();

				// update rgt
				$queries[] = $qb
					->update()
					->set('a.rgt', 'a.rgt + 2')
					->where('a.rgt >= :lft')
					->setParameter('lft', $lft)
					->getQuery()
					->getResult()
				;

				// update lft
				$queries[] = $qb
					->update()
					->set('a.lft', 'a.lft + 2')
					->where('a.lft > :lft')
					->setParameter('lft', $lft)
					->getQuery()
					->getResult()
				;

				// set entry
				$entry
					->setLft($lft)
					->setRgt($rgt)
					->setParent($parent)
					->setDepth($parent->getDepth() + 1)
				;

				$em->persist($entry);
				$em->flush();

				return $entry->getId();
			}
		}

		return false;
	}
}