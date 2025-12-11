<?php

namespace Drupal\itk_translation_extractor\Translation;

use Drupal\itk_translation_extractor\ItkTranslationExtractorTwigExtension;
use Drupal\itk_translation_extractor\Translation\Dumper\PoItem;
use Symfony\Bridge\Twig\Translation\TwigExtractor as BaseTwigExtractor;
use Symfony\Component\Translation\MessageCatalogue;
use Twig\Environment;
use Twig\Source;

class TwigExtractor extends BaseTwigExtractor
{
    /**
     * Prefix for found message.
     */
    private string $prefix = '';

    public function __construct(
        private Environment $twig,
    ) {
        parent::__construct($this->twig);
    }

    protected function extractTemplate(string $template, MessageCatalogue $catalogue): void
    {
        $visitor = $this->twig->getExtension(ItkTranslationExtractorTwigExtension::class)->getTranslationNodeVisitor();

        $visitor->enable();
        $this->twig->parse($this->twig->tokenize(new Source($template, '')));
        foreach ($visitor->getMessages() as $message) {
            $id = trim($message[0]);
            // $translation = Helper::joinStrings(...array_map(static fn (string $string) => '', [...Helper::splitStrings($id)]));
            $translation = $this->prefix.trim($message[0]);
            $domain = $message[1] ?: PoItem::NO_CONTEXT;
            $catalogue->set($id, $translation, $domain);
        }

        $visitor->disable();
    }
}
