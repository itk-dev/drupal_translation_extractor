<?php

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MyClassDrupal
{
    use StringTranslationTrait;

    public function f(): void
    {
        $count = 87;

        new PluralTranslatableMarkup($count, 'ignored One horse', 'ignored @count horses');
        new PluralTranslatableMarkup($count, 'ignored One horse', 'ignored @count horses', [], ['context' => 'the context']);
        new PluralTranslatableMarkup($count, 'ignored One horse', 'ignored @count horses', options: ['context' => 'another context']);

        $this->formatPlural($count, 'ignored One ant', 'ignored @count ants');
        $this->formatPlural($count, 'ignored One ant', 'ignored @count ants', [], ['context' => 'the context']);
        $this->formatPlural($count, 'ignored One ant', 'ignored @count ants', options: ['context' => 'another context']);
    }
}
