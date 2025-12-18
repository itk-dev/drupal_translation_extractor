<?php

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

function t($string, array $args = [], array $options = [])
{
}

class MyClass
{
    use StringTranslationTrait;

    public function f(): void
    {
        t('Global t');
        t('Global t with context', [], ['context' => 'the context']);
        t('Global t with context options',
            options: ['context' => 'another context']);

        $this->t('This t');
        $this->t('This t with context', [], ['context' => 'the context']);
        $this->t('This t with context options',
            options: ['context' => 'another context']);

        $this->trans('This trans');
        $this->trans('This trans with context', [], ['context' => 'the context']);
        $this->trans('This trans with context options',
            options: ['context' => 'another context']);

        new TranslatableMarkup('New TranslatableMarkup');
        new TranslatableMarkup('New TranslatableMarkup with context', [],
            ['context' => 'the context']);
        new TranslatableMarkup('New TranslatableMarkup with context options',
            options: ['context' => 'another context']);
    }

    protected function trans($string, array $args = [], array $options = [])
    {
        return $this->t($string, $args, $options);
    }
}
