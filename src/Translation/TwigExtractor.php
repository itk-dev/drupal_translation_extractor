<?php

namespace Drupal\itk_translation_extractor\Translation;

use Drupal\itk_translation_extractor\NodeVisitor\TranslationNodeVisitor;
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
        $visitor = null;
        foreach ($this->twig->getNodeVisitors() as $v) {
            if ($v instanceof TranslationNodeVisitor) {
                $visitor = $v;
                break;
            }
        }
        if (null === $visitor) {
            return;
        }

        $visitor->enable();

        $this->twig->parse($this->twig->tokenize(new Source($template, '')));

        foreach ($visitor->getMessages() as $message) {
            $catalogue->set(trim($message[0]), $this->prefix.trim($message[0]), $message[1] ?: $this->defaultDomain);
        }

        $visitor->disable();
    }
}
