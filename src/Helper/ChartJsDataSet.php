<?php

namespace App\Helper;

class ChartJsDataSet
{
    /** @var string */
    private string $label;
    /** @var array */
    private array $data = [];
    /** @var string */
    private string $borderColor;
    /** @var string */
    private string $backgroundColor;
    /** @var bool */
    private bool $hidden = false;
    /** @var string  */
    private string $yAxisID = "y";

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return ChartJsDataSet
     */
    public function setLabel(string $label): ChartJsDataSet
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return ChartJsDataSet
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function getBorderColor(): string
    {
        return $this->borderColor;
    }

    /**
     * @param string $borderColor
     * @return ChartJsDataSet
     */
    public function setBorderColor(string $borderColor): ChartJsDataSet
    {
        $this->borderColor = $borderColor;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     * @return ChartJsDataSet
     */
    public function setBackgroundColor(string $backgroundColor): ChartJsDataSet
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     * @return ChartJsDataSet
     */
    public function setHidden(bool $hidden): ChartJsDataSet
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function addData(mixed $data)
    {
        $this->data[] = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function getYAxisID(): string
    {
        return $this->yAxisID;
    }

    /**
     * @param string $yAxisID
     * @return ChartJsDataSet
     */
    public function setYAxisID(string $yAxisID): ChartJsDataSet
    {
        $this->yAxisID = $yAxisID;
        return $this;
    }

    public function serialize()
    {
        return [
            "label"=>$this->getLabel(),
            "backgroundColor"=>$this->getBackgroundColor(),
            "borderColor"=>$this->getBorderColor(),
            "hidden"=>$this->isHidden(),
            "data"=>$this->getData(),
            "yAxisID"=>$this->getYAxisID(),
        ];
    }


}