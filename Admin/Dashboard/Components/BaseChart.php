<?php
namespace ITF\AdminBundle\Admin\Dashboard\Components;

abstract class BaseChart
{
    /** @var string */
    protected $property = 'created_at';
    
    /** @var integer */
    protected $days_range = 30;

    /** @var string */
    protected $format = 'Y-m-d';

    /** @var int */
    protected $maxValue = 0;
    
    /** @var array */
    protected $result = array();

    /** @var array */
    protected $prepared_result = array();

    /**
     * @return $this
     */
    public function prepareResult()
    {
        // init
        $date = $this->getRelativeDateByDays();
        $views = array();
        $last_timestamp = $date;

        // views
        for ($i = 0; $i <= $this->getDaysRange(); $i++) {
            $views[ date($this->getFormat(), $last_timestamp) ] = 0;
            $last_timestamp = strtotime('+1 day', $last_timestamp);
        }


        // collect to days
        foreach($this->getResult() as $key => $entry) {
            $day = date($this->getFormat(), strtotime($entry[ $this->getProperty() ]));
            $views[$day] = $views[$day] + 1;
        }

        // refactor
        $this->resetPreparedResult();
        foreach($views as $date => $count) {
            if ($count > $this->getMaxValue()) $this->setMaxValue($count);

            $this->addPreparedResult(
                array(
                    'date' => $date,
                    'value' => $count
                )
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param string $property
     *
     * @return BaseChart
     */
    public function setProperty($property)
    {
        $this->property = $property;

        return $this;
    }

    /**
     * @return int
     */
    public function getDaysRange()
    {
        return $this->days_range;
    }

    /**
     * @param int $days_range
     *
     * @return BaseChart
     */
    public function setDaysRange($days_range)
    {
        $this->days_range = $days_range;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     *
     * @return BaseChart
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array $result
     *
     * @return BaseChart
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @param int $maxValue
     *
     * @return BaseChart
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    /**
     * @param string $format
     * @return array
     */
    public function getPreparedResult($format = null)
    {
        switch($format) {
            case 'json':
                return json_encode($this->prepared_result);
            default:
                return $this->prepared_result;
        }
    }

    /**
     * @param array $array
     *
     * @return BaseChart
     */
    public function addPreparedResult($array)
    {
        $this->prepared_result[] = $array;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetPreparedResult()
    {
        $this->prepared_result = array();

        return $this;
    }
    
    /**
     * @param bool $format
     *
     * @return mixed
     */
    protected function getRelativeDateByDays($format = false)
    {
        $date = strtotime('-' . $this->getDaysRange() . ' days');
        
        if ($format !== false) {
            if (!is_string($format)) $format = $this->getFormat();
            
            return date($format, $date);
        }
        
        return $date;
    }
}