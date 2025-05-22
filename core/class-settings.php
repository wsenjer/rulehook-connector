<?php

namespace RuleHook\Core;

class Settings
{
    private $title = '';

    private $handling_fees = 0;

    private $handling_fees_type = 'amount';

    private $calculation_method = 'sum';

    private $debug_mode = false;

    private $hide_other_methods = false;

    private $rules = [];

    public function __construct(array $settings)
    {
        $this->setDebugMode($settings['debug_mode'] === 'yes')
            ->setCalculationMethod($settings['calculation_method'])
            ->setHandlingFees(round(floatval($settings['handling_fees']['value']), 2))
            ->setHandlingFeesType($settings['handling_fees']['type'])
            ->setTitle($settings['title'])
            ->setHideOtherMethods($settings['hide_other_methods'] === 'yes');

        foreach ($settings['rules'] as $key => $settings_rule) {

        }

    }

    public function addRule($rules): Settings
    {
        $this->rules[] = $rules;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Settings
    {
        $this->title = $title;

        return $this;
    }

    public function getHandlingFees(): float
    {
        return $this->handling_fees;
    }

    public function setHandlingFees(float $handling_fees): Settings
    {
        $this->handling_fees = $handling_fees;

        return $this;
    }

    public function getHandlingFeesType(): string
    {
        return $this->handling_fees_type;
    }

    public function setHandlingFeesType(string $handling_fees_type): Settings
    {
        $this->handling_fees_type = $handling_fees_type;

        return $this;
    }

    public function getCalculationMethod(): string
    {
        return $this->calculation_method;
    }

    public function setCalculationMethod(string $calculation_method): Settings
    {
        $this->calculation_method = $calculation_method;

        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function setRules(array $rules): Settings
    {
        $this->rules = $rules;

        return $this;
    }

    public function isDebugMode(): bool
    {
        return $this->debug_mode;
    }

    public function setDebugMode(bool $debug_mode): Settings
    {
        $this->debug_mode = $debug_mode;

        return $this;
    }

    public function isHideOtherMethods(): bool
    {
        return $this->hide_other_methods;
    }

    public function setHideOtherMethods(bool $hide_other_methods): Settings
    {
        $this->hide_other_methods = $hide_other_methods;

        return $this;
    }
}
