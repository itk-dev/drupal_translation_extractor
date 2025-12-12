<?php

namespace Drupal\drupal_translation_extractor\Translation;

use Drupal\drupal_translation_extractor\ItkTranslationExtractorTwigExtension;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
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

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    protected function extractTemplate(string $template, MessageCatalogue $catalogue): void
    {
        $visitor = $this->twig->getExtension(ItkTranslationExtractorTwigExtension::class)->getTranslationNodeVisitor();

        $visitor->enable();
        $this->twig->parse($this->twig->tokenize(new Source($template, '')));
        foreach ($visitor->getMessages() as $message) {
            $id = trim($message[0]);
            // $translation = Helper::joinStrings(...array_map(static fn (string $string) => '', [...Helper::splitStrings($id)]));
            $translation = PoItem::joinStrings(...array_map(fn (string $string) => $this->prefix.$string, PoItem::splitStrings($id)));
            $domain = $message[1] ?: PoItem::NO_CONTEXT;
            $catalogue->set($id, $translation, $domain);
        }

        $visitor->disable();
    }
}
