<?php
namespace ITF\AdminBundle\Admin\Upload;

use ITF\AdminBundle\Admin\Service\AbstractServiceSetter;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class Asset extends AbstractServiceSetter
{
	public function __construct(ContainerInterface $container)
	{
		$this->setContainer($container);
		$this->mapping = $this->getContainer()->get('vich_uploader.property_mapping_factory');
	}

	/**
	 * @param $entity
	 * @param $mapping_name
	 *
	 * @return PropertyMapping
	 */
	protected function getMapping($entity, $mapping_name)
	{
		$mapping = $this->mapping->fromObject($entity, NULL, $mapping_name);

		if (count($mapping) == 0) {
			throw new Exception("error getting property mapping");
		}

		return $mapping[0];
	}

	/**
	 * @param null $image
	 *
	 * @return string
	 */
	protected function getDefaultImage($image = NULL)
	{
		if (!empty($image) && is_file($this->getContainer()->getParameter('kernel.root_dir').'/../web/' . $image)) {
			return $image;
		}

		return 'lib/img/transparent.png';
	}


	/**
	 * @param $entity
	 * @param $mapping_name
	 * @param null $default
	 *
	 * @return string
	 */
	public function asset($entity, $mapping_name, $default = NULL)
	{
		$mapping = $this->getMapping($entity, $mapping_name);

		$file = $mapping->getUriPrefix() . '/' . $mapping->getFileName($entity);

		if (!is_file($this->getContainer()->getParameter('kernel.root_dir').'/../web' . $file)) {
			return $this->getDefaultImage($default);
		}

		return $file;
	}
}