<?php
namespace ITF\AdminBundle\Admin\Form\Search;

use ITF\AdminBundle\Admin\Form\AdminSearch;
use ITF\AdminBundle\Admin\Form\Search\Operators\OperatorExpression;
use ITF\AdminBundle\Admin\Form\Search\Operators\SearchOperatorInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class SearchFieldMapping
{
    const TYPE_ARRAY = 'array';
    const TYPE_INTEGER = 'integer';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_STRING = 'string';

    const RANGE_START = 'start';
    const RANGE_END = 'end';

    const OPERATOR_EQ = '=';
    const OPERATOR_GT = '>';
    const OPERATOR_LT = '<';
    const OPERATOR_GTE = '>=';
    const OPERATOR_LTE = '<=';
    const OPERATOR_NE = '!=';
    const OPERATOR_LIKE = 'LIKE';
    const OPERATOR_LIKE_APPROX = 'LIKE %...%';
    const OPERATOR_NOT_LIKE = 'NOT LIKE';
    const OPERATOR_IN = 'IN (...)';

    const OPERATOR_EXPR_EMPTY = false;
    const OPERATOR_USE_STRING = 'use_string';

    /** @var string */
    private $fieldName;

    /** @var string */
    private $type;

    /** @var integer */
    private $scale;

    /** @var integer */
    private $length;

    /** @var boolean */
    private $unique;

    /** @var boolean */
    private $nullable;

    /** @var integer */
    private $precision;

    /** @var boolean */
    private $id;

    /** @var string */
    private $columnName;

    /** @var mixed */
    private $request_value;

    /** @var AbstractSearchOperator */
    private $request_operator;

    /** @var SearchOperators */
    private $search_operators;

    public function __construct()
    {
        $this->search_operators = new SearchOperators();
    }

    /**
     * @param $array
     * @return SearchFieldMapping
     */
    public static function create($array)
    {
        $searchFieldMapping = new self;

        foreach($array as $attr => $value) {
            $method = 'set' . ucfirst($attr);

            if (method_exists($searchFieldMapping, $method)) {
                $searchFieldMapping->{$method}($value);
            }
        }

        return $searchFieldMapping;
    }
    
    public function renderFormField(FormBuilderInterface &$builder, $callback = NULL)
    {
        $fields = array();
        $skip_eq = [self::TYPE_DATETIME, self::TYPE_ARRAY];

        if (!in_array($this->getType(), $skip_eq)) {
            $fields[] = $builder->create($this->getRangedFieldName(self::RANGE_START), 'choice', array(
                //'mapped' => false,
                'label' => false,
                'choices' => $this->getSearchOperators()->toArray(),
                'data' => $this->getSearchOperators()->getDefaultSelected(),
                'attr' => array(
                    'data-range-type' => self::RANGE_START,
                    'data-range-title' => $this->getFieldName()
                )
            ));
        }

        switch($this->getType()) {
            case self::TYPE_STRING:
                $fields[] = $builder->create($this->getRangedFieldName(self::RANGE_END), 'text', array(
                    //'mapped' => false,
                    'attr' => array(
                        'data-range-type' => self::RANGE_END
                    ),
                    'required' => false
                ));
                break;

            case self::TYPE_INTEGER:
                $fields[] = $builder->create($this->getRangedFieldName(self::RANGE_END), 'integer', array(
                    //'mapped' => false,
                    'attr' => array(
                        'data-range-type' => self::RANGE_END
                    ),
                    'required' => false
                ));

                break;
            case self::TYPE_DECIMAL:
                $fields[] = $builder->create($this->getRangedFieldName(self::RANGE_END), 'number', array(
                    //'mapped' => false,
                    'attr' => array(
                        'data-range-type' => self::RANGE_END
                    ),
                    'required' => false
                ));


                break;

            /**
             * CUSTOM FIELDS
             */
            case self::TYPE_ARRAY:
                // dont add
                break;
            case self::TYPE_DATETIME:
                $fields[] = $builder->create($this->getRangedFieldName(self::RANGE_START), 'datetime', array(
                    //'mapped' => false,
                    'format'    => 'dd.MM.yyyy H:mm',
                    'widget' => 'single_text',
                    'attr' => array(
                        'data-range-type' => self::RANGE_START,
                        'data-range-title' => $this->getFieldName(),
                        'placeholder' => self::RANGE_START,
                        'class' => 'bs-datetimepicker',
                        'data-format' => 'DD.MM.YYYY H:mm'
                    ),
                    'required' => false
                ));

                $fields[] = $builder->create($this->getRangedFieldName(self::RANGE_END), 'datetime', array(
                    //'mapped' => false,
                    'format'    => 'dd.MM.yyyy H:mm',
                    'widget' => 'single_text',
                    'attr' => array(
                        'data-range-type' => self::RANGE_END,
                        'placeholder' => self::RANGE_END,
                        'class' => 'bs-datetimepicker',
                        'data-format' => 'DD.MM.YYYY H:mm'
                    ),
                    'required' => false
                ));

                break;

        }

        if (count($fields) > 0 && is_callable($callback)) {
            call_user_func($callback, $fields, $builder);
        }
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    public function getRangedFieldName($range_type)
    {
        return $this->getFieldName() . '_' . $range_type;
    }

    /**
     * @param string $fieldName
     *
     * @return SearchFieldMapping
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return SearchFieldMapping
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param int $scale
     *
     * @return SearchFieldMapping
     */
    public function setScale($scale)
    {
        $this->scale = $scale;

        return $this;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     *
     * @return SearchFieldMapping
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param boolean $unique
     *
     * @return SearchFieldMapping
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @param boolean $nullable
     *
     * @return SearchFieldMapping
     */
    public function setNullable($nullable)
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param int $precision
     *
     * @return SearchFieldMapping
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isId()
    {
        return $this->id;
    }

    /**
     * @param boolean $id
     *
     * @return SearchFieldMapping
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     *
     * @return SearchFieldMapping
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestValue()
    {
        return $this->request_value;
    }

    /**
     * @param mixed $request_value
     *
     * @return SearchFieldMapping
     */
    public function setRequestValue($request_value)
    {
        $this->request_value = $request_value;

        return $this;
    }

    /**
     * @return AbstractSearchOperator
     */
    public function getRequestOperator()
    {
        return $this->request_operator;
    }

    /**
     * @param AbstractSearchOperator $request_operator
     *
     * @return SearchFieldMapping
     */
    public function setRequestOperator(AbstractSearchOperator $request_operator)
    {
        $this->request_operator = $request_operator;

        return $this;
    }

    /**
     * @return SearchOperators
     */
    public function getSearchOperators()
    {
        return $this->search_operators;
    }
    
    /**
     *
     * @param AdminSearch $adminSearch
     *
     * @return OperatorExpression|false
     */
    public function getSearchOperatorExpr(AdminSearch $adminSearch)
    {
        $operator = $this->getSearchOperator($adminSearch);

        if ($operator !== self::OPERATOR_EXPR_EMPTY) {
            return $operator->getExpr();
        }

        return self::OPERATOR_EXPR_EMPTY;
    }

    /**
     * @param AdminSearch $adminSearch
     *
     * @return bool|AbstractSearchOperator|SearchOperatorInterface
     * @throws \Exception
     */
    public function getSearchOperator(AdminSearch $adminSearch)
    {
        $operator = $this->getRequestOperator();

        if ($operator instanceof SearchOperatorInterface) {
            $operator->setSearchFieldMapping($this, $adminSearch->getQBAliasReference($adminSearch->getEntityInstance()));

            if (!$operator->isEmpty()) {
                return clone $operator;
            }
        }

        return self::OPERATOR_EXPR_EMPTY;
    }
}