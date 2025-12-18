<?php

namespace Drupal\drupal_translation_extractor\Translation\Extractor\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor as BaseAbstractVisitor;

abstract class AbstractVisitor extends BaseAbstractVisitor implements NodeVisitor
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

    public function afterTraverse(array $nodes): ?Node
    {
        return null;
    }

    protected function getArrayArgument(
        Node\Expr\CallLike|Node\Attribute|Node\Expr\New_ $node,
        int|string $index,
        bool $indexIsRegex = false,
    ): ?Node\Expr\Array_ {
        if (\is_string($index)) {
            return $this->getArrayNamedArgument($node, $index, $indexIsRegex);
        }

        $args = $node instanceof Node\Expr\CallLike ? $node->getRawArgs() : $node->args;

        if (($arg = $args[$index] ?? null) instanceof Node\Arg) {
            return $arg->value instanceof Node\Expr\Array_ ? $arg->value : null;
        }

        return null;
    }

    protected function getArrayNamedArgument(
        Node\Expr\CallLike|Node\Attribute $node,
        ?string $argumentName = null,
        bool $isArgumentNamePattern = false,
    ): ?Node\Expr\Array_ {
        $args = $node instanceof Node\Expr\CallLike ? $node->getArgs() : $node->args;

        foreach ($args as $arg) {
            if (!$isArgumentNamePattern && $arg->name?->toString() === $argumentName) {
                return $arg->value instanceof Node\Expr\Array_ ? $arg->value : null;
            } elseif ($isArgumentNamePattern && preg_match($argumentName,
                $arg->name?->toString() ?? '') > 0) {
                return $arg->value instanceof Node\Expr\Array_ ? $arg->value : null;
            }
        }

        return null;
    }

    protected function getArrayStringValue(Node\Expr\Array_ $array, string $key): ?string
    {
        foreach ($array->items as $item) {
            if ($item->key instanceof Node\Scalar\String_ && $item->key->value === $key) {
                return $item->value instanceof Node\Scalar\String_ ? $item->value->value : null;
            }
        }

        return null;
    }

    protected function getStringArgument(Node\Expr\CallLike|Node\Attribute|Node\Expr\New_ $node, int|string $index, bool $indexIsRegex = false): ?string
    {
        return $this->getStringArguments($node, $index, $indexIsRegex)[0] ?? null;
    }
}
