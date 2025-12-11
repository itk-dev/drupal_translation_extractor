<?php

namespace Drupal\itk_translation_extractor\Translation\Extractor\Visitor;

use Drupal\itk_translation_extractor\Translation\Dumper\PoItem;
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

            if (!$string = $this->getStringArgument($node, 0 < $firstNamedArgumentIndex ? 0 : 'string')) {
                return null;
            }

            $context = null;
            if ($options = $this->getArrayArgument($node, 2 < $firstNamedArgumentIndex ? 2 : 'options')) {
                $context = $this->getArrayStringValue($options, 'context');
            }

            $this->addMessageToCatalogue($string, $context ?? PoItem::NO_CONTEXT, $node->getStartLine());
        }

        if (\in_array('PluralTranslatableMarkup', $className->getParts(), true)) {
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
