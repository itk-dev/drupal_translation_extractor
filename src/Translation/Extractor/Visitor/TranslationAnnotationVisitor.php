<?php

namespace Drupal\drupal_translation_extractor\Translation\Extractor\Visitor;

use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use PhpParser\Node;

/**
 * Lifted from \Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor.
 *
 * @see \Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor.
 */
final class TranslationAnnotationVisitor extends AbstractVisitor
{
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

        // Escape string literals to make it easier to extract @Translation(…).
        $strings = [];
        $text = preg_replace_callback('/"(?<value>[^"]*)"/', static function ($match) use (&$strings) {
            $placeholder = sprintf('__string_%d__', count($strings));
            $strings[$placeholder] = $match['value'];

            return $placeholder;
        }, $text);

        // Extract @Translation(…). String have been escaped and we assume that no the first `)` after `@Translation(` is the matching one.
        // Due to regex laziness we/I cannot extract the message string and an optional context in one go.
        // preg_match_all('/@Translation\((?<message>[a-z0-9_]+)[^)]*?(?:,[[:space:]]*context[[:space:]]*=[[:space:]]*(?<context>[a-z0-9_]+))?[^)]*?\)/', $text, $matches, PREG_SET_ORDER);
        // Therefore, we first get the message and then check for any context.
        preg_match_all('/@Translation\((?<message>[a-z0-9_]+)[^)]*\)/', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $message = $strings[$match['message']];
            $context = null;
            if (preg_match('/,[[:space:]]*context[[:space:]]*=[[:space:]]*(?<context>[a-z0-9_]+)/', $match[0], $contextMatches)) {
                $context = $strings[$contextMatches['context']];
            }
            $translations[] = [
                'message' => $message,
                'context' => $context,
            ];
        }

        return $translations;
    }
}
