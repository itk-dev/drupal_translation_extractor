<?php

namespace Drupal\itk_translation_extractor\Translation\Extractor\Visitor;

use Drupal\itk_translation_extractor\Translation\Helper;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;

/**
 * Lifted from \Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor.
 *
 * @see \Symfony\Component\Translation\Extractor\Visitor\TransMethodVisitor.
 */
final class TransMethodVisitor extends AbstractVisitor implements NodeVisitor
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
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?Node
    {
        return null;
    }
}
