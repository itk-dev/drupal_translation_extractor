<?php

namespace Drupal\itk_translation_extractor\Translation\Extractor\Visitor;

use Drupal\itk_translation_extractor\Translation\Dumper\PoItem;
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

            if (!$string = $this->getStringArgument($node, 0 < $firstNamedArgumentIndex ? 0 : 'string')) {
                return null;
            }

            $context = null;
            if ($options = $this->getArrayArgument($node, 2 < $firstNamedArgumentIndex ? 2 : 'options')) {
                $context = $this->getArrayStringValue($options, 'context');
            }

            $this->addMessageToCatalogue($string, $context ?? PoItem::NO_CONTEXT, $node->getStartLine());
        } elseif ('formatPlural' === $name) {
            // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21StringTranslation%21TranslationInterface.php/function/TranslationInterface%3A%3AformatPlural/11.x
            $firstNamedArgumentIndex = $this->nodeFirstNamedArgumentIndex($node);

            if (!$singular = $this->getStringArgument($node, 1 < $firstNamedArgumentIndex ? 1 : 'singular')) {
                return null;
            }
            if (!$plural = $this->getStringArgument($node, 2 < $firstNamedArgumentIndex ? 2 : 'plural')) {
                return null;
            }

            $context = null;
            if ($options = $this->getArrayArgument($node, 4 < $firstNamedArgumentIndex ? 4 : 'options')) {
                $context = $this->getArrayStringValue($options, 'context');
            }
            $context ??= PoItem::NO_CONTEXT;

            $this->addMessageToCatalogue(PoItem::joinStrings($singular, $plural), $context, $node->getStartLine());
        }

        return null;
    }
}
