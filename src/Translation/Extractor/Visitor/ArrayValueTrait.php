<?php

namespace Drupal\itk_translation_extractor\Translation\Extractor\Visitor;

use PhpParser\Node;

trait ArrayValueTrait
{
    private function getArrayArgument(
        Node\Expr\CallLike|Node\Attribute|Node\Expr\New_ $node,
        int|string $index,
        bool $indexIsRegex = false,
    ): ?Node\Expr\Array_ {
        if (\is_string($index)) {
            return $this->getArrayNamedArgument($node, $index, $indexIsRegex);
        }

        $args = $node instanceof Node\Expr\CallLike ? $node->getRawArgs() : $node->args;

        if (($arg = $args[$index] ?? null) instanceof Node\Arg) {
            return $arg?->value instanceof Node\Expr\Array_ ? $arg->value : null;
        }

        return null;
    }

    private function getArrayNamedArgument(
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

    private function getArrayStringValue(Node\Expr\Array_ $array, string $key): ?string
    {
        foreach ($array->items as $item) {
            if ($item->key instanceof Node\Scalar\String_ && $item->key->value === $key) {
                return $item->value instanceof Node\Scalar\String_ ? $item->value->value : null;
            }
        }

        return null;
    }
}
