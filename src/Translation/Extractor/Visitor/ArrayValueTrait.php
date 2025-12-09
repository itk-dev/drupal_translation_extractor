<?php

namespace Drupal\itk_translation_extractor\Extractor\Visitor;

use PhpParser\Node;

trait ArrayValueTrait
{
    private function getArrayArgument(Node\Expr\CallLike|Node\Attribute|Node\Expr\New_ $node, int|string $index, bool $indexIsRegex = false): ?Node\Expr\Array_
    {
        return null;
    }

    private function getArrayStringValue(Node\Expr\Array_ $array): ?string
    {
        return null;
    }
}
