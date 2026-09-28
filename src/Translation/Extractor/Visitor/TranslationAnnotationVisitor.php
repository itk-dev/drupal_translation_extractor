<?php

namespace Drupal\drupal_translation_extractor\Translation\Extractor\Visitor;

use Drupal\Component\Annotation\Doctrine\DocParser;
use Drupal\Core\Annotation\Translation;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use PhpParser\Node;

/**
 * Lifted from \Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor.
 *
 * @see \Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor.
 */
final class TranslationAnnotationVisitor extends AbstractVisitor
{
    private readonly DocParser $parser;

    public function __construct()
    {
        $this->parser = new DocParser();
        $this->parser->setIgnoreNotImportedAnnotations(true);
        $this->parser->setImports([
            'translation' => Translation::class,
        ]);
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return null;
        }

        $comment = $node->getDocComment();
        if (null !== $comment) {
            $text = $comment->getText();
            $translations = $this->extractTranslations($text);
            foreach ($translations as $translation) {
                $this->addMessageToCatalogue($translation['message'], domain: $translation['context'] ?? PoItem::NO_CONTEXT, line: $comment->getStartLine());
            }
        }

        return null;
    }

    /**
     * Extract @Translation(…) expressions.
     *
     * @see https://git.drupalcode.org/project/drupal/-/blob/main/core/lib/Drupal/Core/Annotation/Translation.php
     *
     * @return list<array{
     *   message: string,
     *   context: string|null
     * }>
     */
    private function extractTranslations(string $text): array
    {
        $translations = [];

        $annotations = $this->parser->parse($text);
        foreach ($annotations as $annotation) {
            if (!$annotation instanceof Translation) {
                continue;
            }

            $translation = $annotation->get();
            $translations[] = [
                'message' => $translation->getUntranslatedString(),
                'context' => $translation->getOption('context') ?: PoItem::NO_CONTEXT,
            ];
        }

        return $translations;
    }
}
