<?php
namespace ITF\AdminBundle\Admin\Dashboard\Components;

class MorrisLineChart extends BaseChart implements ChartInterface
{
    public static function create()
    {
        return new MorrisLineChart();
    }
}