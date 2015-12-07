<?php
namespace ITF\AdminBundle\Admin;

class AnnotationReader
{
	private $reader;

	public function __construct() {
		$this->reader = new \Doctrine\Common\Annotations\AnnotationReader();
	}

	public function getClassAnnotation($class)
	{
		$reflection = new \ReflectionClass($class);
		return $this->reader->getClassAnnotations($reflection);
	}

	public function getRepositoryClass($class)
	{

	}

	/* tester */
	public function isGedmoTree($class)
	{
		$metas = $this->getClassAnnotation($class);

		foreach($metas as $meta) {
			if ($meta instanceof \Gedmo\Mapping\Annotation\Tree) {
				return true;
			}
		}

		return false;
	}
}