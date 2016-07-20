<?php
namespace ITF\AdminBundle\Admin\Form;

use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use ITF\AdminBundle\Admin\Form\Search\Operators\BetweenSearchOperator;
use ITF\AdminBundle\Admin\Form\Search\Operators\OperatorExpression;
use ITF\AdminBundle\Admin\Form\Search\Operators\SearchOperatorInterface;
use ITF\AdminBundle\Admin\Form\Search\SearchAssociationFieldMapping;
use ITF\AdminBundle\Admin\Form\Search\SearchFieldMapping;
use ITF\AdminBundle\Admin\Form\Search\SearchOperators;
use ITF\AdminBundle\Admin\Service\AbstractServiceSetter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class AdminSearch extends AbstractServiceSetter
{
    const FIELD_TYPE_COMMON = 'common';
    const FIELD_TYPE_ASSOCIATION = 'association';

    const FORM_NAME = 'form';
    const FORM_METHOD = 'GET';

    /** @var Form */
    private $form;

    /** @var object */
    private $entity_instance;

    /** @var ArrayCollection */
    private $fieldMappings;

    /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata */
    private $meta;

    /** @var bool  */
    private $request_data_mapped = false;

    /** @var ArrayCollection */
    private $qb_alias_reference;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->fieldMappings = new ArrayCollection();
        $this->qb_alias_reference = new ArrayCollection();
    }

    public function createSearchForm($entity_instance)
    {
        $this->entity_instance = $entity_instance;

        // set field mappings
        $this->getFieldMappings($this->getEntityInstance(), true);

        $formBuilder = $this->getContainer()->get('form.factory')->createBuilder(self::FORM_NAME, $this->getRequest()->query->all(), array(
            'method' => self::FORM_METHOD
        ));

        foreach($this->fieldMappings as $fieldMapping) {
            /** @var SearchFieldMapping|SearchAssociationFieldMapping $fieldMapping */
            if ($fieldMapping instanceof SearchAssociationFieldMapping && true == false) { // disable
                foreach($fieldMapping->getFieldMappings() as $_fieldMapping) {
                    $_fieldMapping->renderFormField($formBuilder, function ($fields) use ($formBuilder, $_fieldMapping) {
                        foreach ($fields as $field) {
                            $formBuilder->add($field);
                        }
                    }, $_fieldMapping->getFieldName() . '_');
                }
            } else {
                $fieldMapping->renderFormField($formBuilder, function($fields) use ($formBuilder) {
                    foreach($fields as $field) {
                        $formBuilder->add($field);
                    }
                });
            }
        }

        // handle common field types

        /*foreach($this->getFieldMappingsOfType(self::FIELD_TYPE_COMMON) as $fieldMapping) {
            ** @var $fieldMapping SearchFieldMapping *
            $fieldMapping->renderFormField($formBuilder, function($fields) use ($formBuilder) {
                foreach($fields as $field) {
                    $formBuilder->add($field);
                }
            });
        }

        // handle assoc field types
        $assocMappings = $this->getFieldMappingsOfType(self::FIELD_TYPE_ASSOCIATION);
        foreach($this->getFieldMappingsOfType(self::FIELD_TYPE_ASSOCIATION) as $fieldMapping) {
            * @var $fieldMapping SearchAssociationFieldMapping *
            $fieldMapping->renderFormField($formBuilder, function($fields) use ($formBuilder) {
                foreach($fields as $field) {
                    $formBuilder->add($field);
                }
            }, $fieldMapping->getFieldName() . '_');
        }*/

        // add search button
        $formBuilder->add('submit', 'submit');

        // add current request to form
        $form = $formBuilder->getForm();
        $form->handleRequest($this->getContainer()->get('request'));

        return $form;
    }


    /**
     * check if submitted and data
     *
     * @param Request $request
     *
     * @return bool
     */
    public function isSubmitted(Request $request)
    {
        if ($request->isMethod(self::FORM_METHOD)) {
            switch ($request->getMethod()) {
                case Request::METHOD_GET:
                    return count($request->query->get(self::FORM_NAME)) > 0;
                    break;
                //case Request::METHOD_POST:break;
            }
        }

        return false;
    }

    /**
     * Array (
        [id_start] => 4
        [id_end] => 1
     * )
     *
     * - to -
     *
     * [id] => Array (
        [start] => 4
        [end] => 1
     * )
     *
     * @param Request $request
     *
     * @return array
     */
    public function mapRequestData(Request $request)
    {
        $data = $request->query->get(self::FORM_NAME);
        $data2 = array();
        $searchOperators = new SearchOperators();

        foreach($data as $attr => $value) {
            if (preg_match('/(.*?)\_(' . SearchFieldMapping::RANGE_START . '|' . SearchFieldMapping::RANGE_END . ')$/', $attr, $m)) {
                if (!isset($data2[$m[1]])) $data2[$m[1]] = array();

                $data2[ $m[1] ][ $m[2] ] = $value;
            }
        }

        foreach($this->fieldMappings as &$fieldMapping) {
            /** @var SearchFieldMapping $fieldMapping */
            $requestData = @$data2[ $fieldMapping->getFieldName() ];

            if ($requestData) {
                switch ($fieldMapping->getType()) {
                    case SearchFieldMapping::TYPE_DATETIME:
                        $fieldMapping->setRequestValue( [$requestData[ SearchFieldMapping::RANGE_START ], $requestData[ SearchFieldMapping::RANGE_END ]] );
                        $fieldMapping->setRequestOperator( new BetweenSearchOperator() );
                        break;
                    default:
                        $fieldMapping->setRequestValue( $requestData[ SearchFieldMapping::RANGE_END ] );
                        $fieldMapping->setRequestOperator( $searchOperators->get((int) $requestData[ SearchFieldMapping::RANGE_START ]) );
                        break;
                }
            }
        }
    }


    /**
     * @return \Doctrine\ORM\QueryBuilder
     * @throws \Exception
     */
    public function getQueryBuilder()
    {
        if (!$this->request_data_mapped) {
            throw new \Exception("Request data has to be mapped first");
        }

        $qb = $this->getEntityManager()->getRepository(get_class($this->getEntityInstance()))->createQueryBuilder($this->getQBAliasReference($this->getEntityInstance()));
        $andX = $qb->expr()->andX();

        //$qb->select('a.id');

        foreach($this->fieldMappings as $fieldMapping) {
            /** @var SearchFieldMapping $fieldMapping */
            $operatorExpression = $fieldMapping->getSearchOperatorExpr($this);

            if ($operatorExpression) {
                //dump($operatorExpression);
                $andX->add($operatorExpression->getExpression()->__toString());
                $qb->setParameter($operatorExpression->getParameter(), $operatorExpression->getValue());
                //$andX->add($expr);
            }
        }

        $qb->where($andX);

        //dump($qb->getQuery()->getSQL());exit;

        return $qb;
    }

    /**
     * @return OperatorExpression[]
     * @throws \Exception
     */
    public function getOperatorExpressions()
    {
        if (!$this->request_data_mapped) {
            throw new \Exception("Request data has to be mapped first");
        }

        $operatorExpressions = array();

        foreach($this->fieldMappings as $fieldMapping) {
            $operatorExpression = $fieldMapping->getSearchOperatorExpr($this);
            if ($operatorExpression) $operatorExpressions[] = $operatorExpression;
        }

        return $operatorExpressions;
    }

    /**
     * @return SearchOperatorInterface[]
     * @throws \Exception
     */
    public function getOperators()
    {
        if (!$this->request_data_mapped) {
            throw new \Exception("Request data has to be mapped first");
        }
    
        $operators = array();
        
        foreach($this->getFieldMappingsOfType(self::FIELD_TYPE_COMMON) as $fieldMapping) {
            /** @var SearchFieldMapping $fieldMapping */
            /** @var SearchOperatorInterface $operator */
            $operator = $fieldMapping->getSearchOperator($this);

            if ($operator !== SearchFieldMapping::OPERATOR_EXPR_EMPTY) {
                $operators[] = $operator;
            }

            unset($operator);
        }

        return $operators;
    }


    /**
     * @return \Doctrine\ORM\Query
     * @throws \Exception
     */
    public function getQuery()
    {
        return $this->getQueryBuilder()->getQuery();
    }

    private function getMetaData($class_name)
    {
        return $this->getEntityManager()->getMetadataFactory()->getMetadataFor($class_name);
    }

    /**
     * Get field mappings
     *
     * @param $entity_instance
     * @param bool $get_assoc_fields
     *
     * @return ArrayCollection
     */
    public function getFieldMappings($entity_instance, $get_assoc_fields = false, $save = true)
    {
        $meta = $this->getMetaData(get_class($entity_instance));
        $fieldMappings = new ArrayCollection();

        // field mappings
        foreach($meta->fieldMappings as $fieldMapping) {
            $fieldMappings->add(
                SearchFieldMapping::create($fieldMapping)
            );
        }

        // association field mappings
        if ($get_assoc_fields) {
            foreach ($meta->associationMappings as $associationMapping) {
                $mapping = SearchAssociationFieldMapping::create($associationMapping);

                // get meta for association
                if ($get_assoc_fields) {
                    $assocClassName = $mapping->getTargetEntity();
                    $assocInstance = new $assocClassName();
                    $assocFieldMappings = $this->getFieldMappings($assocInstance, false, false);

                    // add field mappings to assoc
                    $mapping->setFieldMappings($assocFieldMappings);
                }

                $fieldMappings->add(
                    $mapping
                );
            }
        }

        if ($save) {
            $this->request_data_mapped = true;
            $this->meta = $meta;
            $this->fieldMappings = $fieldMappings;
            $this->entity_instance = $entity_instance;
        }

        return $fieldMappings;
    }

    /**
     * @param string $type
     *
     * @return \Doctrine\Common\Collections\Collection
     * @throws \Exception
     */
    private function getFieldMappingsOfType($type = self::FIELD_TYPE_COMMON)
    {
        if ($this->fieldMappings === NULL) throw new \Exception("Field mapping needs to be set");

        return $this->fieldMappings
            ->filter(function($entry) use ($type) {
                if ($type === self::FIELD_TYPE_COMMON && !$entry instanceof SearchAssociationFieldMapping
                    || $type === self::FIELD_TYPE_ASSOCIATION && $entry instanceof SearchAssociationFieldMapping) {

                    return $entry;
                }

                return false;
            });
    }

    public function getQBAliasReference($class, $attribute = NULL)
    {
        if (!is_object($class)) {
            throw new \Exception("object must be of type object, type %s given.", gettype($class));
        }

        $class_name = get_class($class);

        // get alias of class
        $alias = NULL;
        foreach($this->qb_alias_reference->toArray() as $_class => $_alias) {
            if (isset($_alias[$class_name])) {
                $alias = $_alias[$class_name];
            }
        }

        // if not found
        if ($alias === NULL) {
            // if empty, set new
            if ($this->qb_alias_reference->count() == 0) {
                $alias = 'a';

            // get last and then count up
            } else {
                $lastAlias = $this->qb_alias_reference->last();
                $alias = ++$lastAlias[key($lastAlias)];
            }

            // add new
            $this->qb_alias_reference->add(array(
                $class_name => $alias
            ));
        }

        return $attribute === NULL
            ? $alias
            : $alias . '.' . $attribute
        ;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return object
     */
    public function getEntityInstance()
    {
        return $this->entity_instance;
    }
}