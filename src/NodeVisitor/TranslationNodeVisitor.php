<?php

namespace Drupal\itk_translation_extractor\NodeVisitor;

use Drupal\Core\Template\TwigNodeTrans as TransNode;
use Twig\Environment;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Lifted from \Symfony\Bridge\Twig\NodeVisitor\TranslationNodeVisitor.
 *
 * @see \Symfony\Bridge\Twig\NodeVisitor\TranslationNodeVisitor
 */
final class TranslationNodeVisitor implements NodeVisitorInterface
{
    public const UNDEFINED_DOMAIN = '';

    private bool $enabled = false;
    private array $messages = [];

    public function enable(): void
    {
        $this->enabled = true;
        $this->messages = [];
    }

    public function disable(): void
    {
        $this->enabled = false;
        $this->messages = [];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if (!$this->enabled) {
            return $node;
        }

        if (
            $node instanceof FilterExpression
            && in_array($node->hasAttribute('twig_callable') ? $node->getAttribute('twig_callable')->getName() : $node->getNode('filter')->getAttribute('value'), ['t', 'trans'], true)
            && $node->getNode('node') instanceof ConstantExpression
        ) {
            // extract constant nodes with a t(rans) filter
            $this->messages[] = [
                $node->getNode('node')->getAttribute('value'),
                $this->getReadDomainFromArguments($node->getNode('arguments'), 1),
            ];
        } elseif (
            $node instanceof FunctionExpression
            && 't' === $node->getAttribute('name')
        ) {
            $nodeArguments = $node->getNode('arguments');

            if ($nodeArguments->getIterator()->current() instanceof ConstantExpression) {
                $this->messages[] = [
                    $this->getReadMessageFromArguments($nodeArguments, 0),
                    $this->getReadDomainFromArguments($nodeArguments, 2),
                ];
            }
        } elseif ($node instanceof TransNode) {
            // extract trans nodes
            $this->messages[] = [
                $node->getNode('body')->getAttribute('data'),
                $node->hasNode('options') ? $this->getReadDomainFromNode($node->getNode('options')) : null,
            ];
        } elseif (
            $node instanceof FilterExpression
            && in_array($node->hasAttribute('twig_callable') ? $node->getAttribute('twig_callable')->getName() : $node->getNode('filter')->getAttribute('value'), ['t', 'trans'], true)
            && $node->getNode('node') instanceof ConcatBinary
            && $message = $this->getConcatValueFromNode($node->getNode('node'), null)
        ) {
            $this->messages[] = [
                $message,
                $this->getReadDomainFromArguments($node->getNode('arguments'), 1),
            ];
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }

    private function getReadMessageFromArguments(Node $arguments, int $index): ?string
    {
        if ($arguments->hasNode('message')) {
            $argument = $arguments->getNode('message');
        } elseif ($arguments->hasNode($index)) {
            $argument = $arguments->getNode($index);
        } else {
            return null;
        }

        return $this->getReadMessageFromNode($argument);
    }

    private function getReadMessageFromNode(Node $node): ?string
    {
        if ($node instanceof ConstantExpression) {
            return $node->getAttribute('value');
        }

        return null;
    }

    private function getReadDomainFromArguments(Node $arguments, int $index): ?string
    {
        if ($arguments->hasNode('options')) {
            $argument = $arguments->getNode('options');
        } elseif ($arguments->hasNode($index)) {
            $argument = $arguments->getNode($index);
        } else {
            return null;
        }

        return $this->getReadDomainFromNode($argument);
    }

    private function getReadDomainFromNode(Node $node): ?string
    {
        if ($node instanceof ArrayExpression) {
            foreach ($node->getKeyValuePairs() as $pair) {
                $key = $this->getConcatValueFromNode($pair['key'], '');
                if ('context' === $key) {
                    return $this->getConcatValueFromNode($pair['value'], '');
                }
            }
        }

        return self::UNDEFINED_DOMAIN;
    }

    private function getConcatValueFromNode(Node $node, ?string $value): ?string
    {
        if ($node instanceof ConcatBinary) {
            foreach ($node as $nextNode) {
                if ($nextNode instanceof ConcatBinary) {
                    $nextValue = $this->getConcatValueFromNode($nextNode, $value);
                    if (null === $nextValue) {
                        return null;
                    }
                    $value .= $nextValue;
                } elseif ($nextNode instanceof ConstantExpression) {
                    $value .= $nextNode->getAttribute('value');
                } else {
                    // this is a node we cannot process (variable, or translation in translation)
                    return null;
                }
            }
        } elseif ($node instanceof ConstantExpression) {
            $value .= $node->getAttribute('value');
        }

        return $value;
    }
}
