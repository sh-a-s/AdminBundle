<?php
namespace ITF\AdminBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityToEntityIdTransformer implements DataTransformerInterface
{
    /* @var EntityManager $entityManager */
    private $entityManager;

    private $entity_class;

    public function __construct(EntityManager $manager, $entity_class)
    {
        $this->entityManager = $manager;
        $this->entity_class = $entity_class;
    }
    
    public function transform($value)
    {
        pre($value);exit;
        if ($value === null) {
            return '';
        }

        return $value->getId();
    }
    
    public function reverseTransform($id)
    {
        if (!$id) {
            return false;
        }

        $entity = $this->entityManager->getRepository($this->entity_class)->find($id);

        if ($entity === null) {
            throw new TransformationFailedException(sprintf(
                "An entity with ID %s does not exist",
                $id
            ));
        }

        return $entity;
    }
}