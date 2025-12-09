<?php

namespace Drupal\itk_translation_extractor\Translation\Extractor\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;

/**
 * Lifted from \Symfony\Component\Translation\Extractor\Visitor\TranslatableMessageVisitor.
 *
 * @see \Symfony\Component\Translation\Extractor\Visitor\TranslatableMessageVisitor
 */
final class TranslatableMarkupVisitor extends AbstractVisitor implements NodeVisitor
{
    use ArrayValueTrait;

    public function beforeTraverse(array $nodes): ?Node
    {
        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Expr\New_) {
            return null;
        }

        if (!($className = $node->class) instanceof Node\Name) {
            return null;
        }

        if (!\in_array('TranslatableMarkup', $className->getParts(), true)) {
            return null;
        }

        $firstNamedArgumentIndex = $this->nodeFirstNamedArgumentIndex($node);

        if (!$messages = $this->getStringArguments($node, 0 < $firstNamedArgumentIndex ? 0 : 'string')) {
            return null;
        }

        $context = '';
        if ($options = $this->getArrayArgument($node, 2 < $firstNamedArgumentIndex ? 2 : 'options')) {
            $context = $this->getArrayStringValue($options, 'context') ?? '';
        }

        foreach ($messages as $message) {
            $this->addMessageToCatalogue($message, $context, $node->getStartLine());
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?Node
    {
        return null;
    }
}
