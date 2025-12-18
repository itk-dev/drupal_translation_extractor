<?php

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MyClassDrupal
{
    use StringTranslationTrait;

    public function f(): void
    {
        $count = 87;

        new PluralTranslatableMarkup($count, 'One horse', '@count horses');
        new PluralTranslatableMarkup($count, 'One horse', '@count horses', [], ['context' => 'the context']);
        new PluralTranslatableMarkup($count, 'One horse', '@count horses', options: ['context' => 'another context']);

        $this->formatPlural($count, 'One ant', '@count ants');
        $this->formatPlural($count, 'One ant', '@count ants', [], ['context' => 'the context']);
        $this->formatPlural($count, 'One ant', '@count ants', options: ['context' => 'another context']);
    }
}
