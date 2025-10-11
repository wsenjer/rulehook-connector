<?php

namespace RuleHook\Core;

if ( ! defined( 'ABSPATH' ) ) exit;


class Rate
{
    private string $id = '';

    private float $cost = -1.0;

    private string $label = '';

    private array $metaData = [];

    public function setMetaData(array $metaData): Rate
    {
        $this->metaData = $metaData;

        return $this;
    }

    public function getWoocommerceRate(): array
    {
        return [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'cost' => $this->getCost(),
            'meta_data' => $this->metaData,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): Rate
    {
        $this->id = $id;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;

    }

    public function setLabel(string $label): Rate
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return []
     */
    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * @return Rate
     */
    public function setCost(float $cost)
    {
        $this->cost = $cost;

        return $this;
    }
}
