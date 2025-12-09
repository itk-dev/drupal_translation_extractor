<?php

use Drupal\Core\StringTranslation\StringTranslationTrait;

function t($string, array $args = [], array $options = [])
{
}

class MyClass
{
    use StringTranslationTrait;

    public function __construct()
    {
        t('Global t');
        t('Global t with context', [], ['context' => 'the context']);
        t('Global t with context options', options: ['context' => 'another context']);

        $this->t('This t');
        $this->t('This t with context', [], ['context' => 'the context']);
        $this->t('This t with context options', options: ['context' => 'another context']);

        $this->trans('This trans');
        $this->trans('This trans with context', [], ['context' => 'the context']);
        $this->trans('This trans with context options', options: ['context' => 'another context']);
    }

    protected function trans($string, array $args = [], array $options = [])
    {
        return $this->t($string, $args, $options);
    }
}
