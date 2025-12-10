<?php

namespace Drupal\itk_translation_extractor\Translation;

use Drupal\itk_translation_extractor\ItkTranslationExtractorTwigExtension;
use Symfony\Bridge\Twig\Translation\TwigExtractor as BaseTwigExtractor;
use Symfony\Component\Translation\MessageCatalogue;
use Twig\Environment;
use Twig\Source;

class TwigExtractor extends BaseTwigExtractor
{
    /**
     * Default domain for found messages.
     */
    private string $defaultDomain = '';

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
            $catalogue->set(trim($message[0]), $this->prefix.trim($message[0]), $message[1] ?: $this->defaultDomain);
        }

        $visitor->disable();
    }
}
