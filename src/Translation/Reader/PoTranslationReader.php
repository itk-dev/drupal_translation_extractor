<?php

namespace Drupal\drupal_translation_extractor\Translation\Reader;

use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;

class PoTranslationReader implements TranslationReaderInterface
{
    public function read(string $directory, MessageCatalogue $catalogue): void
    {
        $loader = new PoFileLoader();
        $loader->load($directory, $catalogue->getLocale());
    }
}
