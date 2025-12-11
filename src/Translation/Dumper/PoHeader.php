<?php

namespace Drupal\itk_translation_extractor\Translation\Dumper;

class PoHeader extends \Drupal\Component\Gettext\PoHeader
{
    public function __construct(string $langcode)
    {
        parent::__construct($langcode);
    }

    /**
     * The parent's missing setter.
     */
    public function setPluralForms(string $pluralForms): void
    {
        $this->pluralForms = $pluralForms;
    }

    public function __toString()
    {
        $output = trim(parent::__toString())."\n";
        $output .= '"Language: '.$this->langcode."\\n\"\n";
        $output .= "\n";

        return $output;
    }
}
