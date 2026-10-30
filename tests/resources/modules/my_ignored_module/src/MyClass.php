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
        t('ignored Global t');
        t('ignored Global t with context', [], ['context' => 'the context']);
        t('ignored Global t with context options',
            options: ['context' => 'another context']);

        $this->t('ignored This t');
        $this->t('ignored This t with context', [], ['context' => 'the context']);
        $this->t('ignored This t with context options',
            options: ['context' => 'another context']);

        $this->trans('ignored This trans');
        $this->trans('ignored This trans with context', [], ['context' => 'the context']);
        $this->trans('ignored This trans with context options',
            options: ['context' => 'another context']);

        new TranslatableMarkup('ignored New TranslatableMarkup');
        new TranslatableMarkup('ignored New TranslatableMarkup with context', [],
            ['context' => 'the context']);
        new TranslatableMarkup('ignored New TranslatableMarkup with context options',
            options: ['context' => 'another context']);
    }

    protected function trans($string, array $args = [], array $options = [])
    {
        return $this->t($string, $args, $options);
    }
}
