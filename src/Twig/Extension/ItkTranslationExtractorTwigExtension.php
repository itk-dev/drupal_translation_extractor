<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Twig\Extension;

use Drupal\drupal_translation_extractor\Twig\NodeVisitor\TranslationNodeVisitor;
use Twig\Extension\AbstractExtension;

/**
 * Twig extension.
 */
final class ItkTranslationExtractorTwigExtension extends AbstractExtension
{
    public function __construct(
        private ?TranslationNodeVisitor $translationNodeVisitor = null,
    ) {
    }

    public function getNodeVisitors(): array
    {
        return [
            $this->getTranslationNodeVisitor(),
        ];
    }

    public function getTranslationNodeVisitor(): TranslationNodeVisitor
    {
        return $this->translationNodeVisitor ?: $this->translationNodeVisitor = new TranslationNodeVisitor();
    }
}
