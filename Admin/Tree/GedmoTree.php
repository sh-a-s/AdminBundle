<?php
namespace ITF\AdminBundle\Admin\Tree;

use ITF\AdminBundle\Admin\AdminHelper;

class GedmoTree
{
	private $ah;

	public function __construct(AdminHelper $adminHelper)
	{
		$this->ah = $adminHelper;
	}

	/**
	 * @param \Gedmo\Tree\Entity\Repository\NestedTreeRepository $repository
	 *
	 * @return string
	 */
	public function getTreeListHTML($repository)
	{
		$options = array(
			'decorate' => true,
			'rootOpen' => '<ul>',
			'rootClose' => '</ul>',
			'childOpen' => '<li>',
			'childClose' => '</li>',
			'nodeDecorator' => function($node) {
				return '<a href="'.$this->ah->getEditPath($node['id']).'">'.$node['title'].'</a>';
			}
		);

		return $repository->childrenHierarchy(
			null, /* starting from root nodes */
			false, /* true: load all children, false: only direct */
			$options
		);
	}
}