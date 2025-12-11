<?php

namespace Drupal\itk_translation_extractor\Translation\Extractor\Visitor;

use Drupal\itk_translation_extractor\Translation\Helper;
use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * Lifted from \Symfony\Component\Translation\Extractor\Visitor\TranslatableMessageVisitor.
 *
 * @see \Symfony\Component\Translation\Extractor\Visitor\TranslatableMessageVisitor
 */
final class TranslatableMarkupVisitor extends AbstractVisitor implements NodeVisitor
{
    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Expr\New_) {
            return null;
        }

        if (!($className = $node->class) instanceof Node\Name) {
            return null;
        }

        if (\in_array('TranslatableMarkup', $className->getParts(), true)) {
            $firstNamedArgumentIndex = $this->nodeFirstNamedArgumentIndex($node);

            if (!$messages = $this->getStringArguments($node, 0 < $firstNamedArgumentIndex ? 0 : 'string')) {
                return null;
            }

            $context = null;
            if ($options = $this->getArrayArgument($node, 2 < $firstNamedArgumentIndex ? 2 : 'options')) {
                $context = $this->getArrayStringValue($options, 'context');
            }

            foreach ($messages as $message) {
                $this->addMessageToCatalogue($message, $context ?? Helper::UNDEFINED_DOMAIN, $node->getStartLine());
            }
        }

        if (\in_array('PluralTranslatableMarkup', $className->getParts(), true)) {
            $firstNamedArgumentIndex = $this->nodeFirstNamedArgumentIndex($node);

            if (!$singular = $this->getStringArguments($node, 1 < $firstNamedArgumentIndex ? 1 : 'singular')) {
                return null;
            }
            if (!$plural = $this->getStringArguments($node, 2 < $firstNamedArgumentIndex ? 2 : 'plural')) {
                return null;
            }

            $context = null;
            if ($options = $this->getArrayArgument($node, 4 < $firstNamedArgumentIndex ? 4 : 'options')) {
                $context = $this->getArrayStringValue($options, 'context');
            }
            $context ??= Helper::UNDEFINED_DOMAIN;

            foreach ($singular as $index => $message) {
                $this->addMessageToCatalogue($message, $context, $node->getStartLine());
                $this->addMetadataToCatalogue($message, [Helper::METADATA_EXTRACTED_PLURALS => [$message, $plural[$index]]], $context);
            }
        }

        return null;
    }
}
