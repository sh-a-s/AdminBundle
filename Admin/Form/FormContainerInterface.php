<?php
namespace ITF\AdminBundle\Admin\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface FormContainerInterface
{

	/**
	 * @return ContainerInterface
	 */
	public function getContainer();
}