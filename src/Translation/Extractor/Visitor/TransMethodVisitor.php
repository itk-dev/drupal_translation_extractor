<?php

namespace Drupal\itk_translation_extractor\Translation\Extractor\Visitor;

use Drupal\itk_translation_extractor\Translation\Helper;
use PhpParser\Node;

/**
 * Lifted from \Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor.
 *
 * @see \Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor.
 */
final class TransMethodVisitor extends AbstractVisitor
{
    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Expr\MethodCall && !$node instanceof Node\Expr\FuncCall) {
            return null;
        }

        if (!\is_string($node->name) && !$node->name instanceof Node\Identifier && !$node->name instanceof Node\Name) {
            return null;
        }

        $name = $node->name instanceof Node\Name ? $node->name->getLast() : (string) $node->name;

        if ('trans' === $name || 't' === $name) {
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
        } elseif ('formatPlural' === $name) {
            // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21StringTranslation%21TranslationInterface.php/function/TranslationInterface%3A%3AformatPlural/11.x
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
