<?php

namespace Drupal\itk_translation_extractor\NodeVisitor;

use Drupal\Core\Template\TwigNodeTrans;
use Drupal\Core\Template\TwigNodeTrans as TransNode;
use Drupal\itk_translation_extractor\Translation\Helper;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Node\CheckToStringNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Expression\TempNameExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Lifted from \Symfony\Bridge\Twig\NodeVisitor\TranslationNodeVisitor.
 *
 * @see \Symfony\Bridge\Twig\NodeVisitor\TranslationNodeVisitor
 */
final class TranslationNodeVisitor implements NodeVisitorInterface
{
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
        // @todo Is {{ t('…') }} supported in Drupal?
        //        } elseif (
        //            $node instanceof FunctionExpression
        //            && 't' === $node->getAttribute('name')
        //        ) {
        //            $nodeArguments = $node->getNode('arguments');
        //
        //            if ($nodeArguments->getIterator()->current() instanceof ConstantExpression) {
        //                $this->messages[] = [
        //                    $this->getReadMessageFromArguments($nodeArguments, 0),
        //                    $this->getReadDomainFromArguments($nodeArguments, 2),
        //                ];
        //            }
        } elseif ($node instanceof TransNode) {
            // extract trans nodes
            $body = $node->getNode('body');
            if ($body instanceof ConstantExpression) {
                // {% trans '…' %}
                $this->messages[] = [
                    $this->getConcatValueFromNode($body, ''),
                    $node->hasNode('options') ? $this->getReadDomainFromNode($node->getNode('options')) : Helper::UNDEFINED_DOMAIN,
                ];
            } else {
                // {% trans %}…{% endtrans %}
                if ($node->hasNode('plural')) {
                    $singular = $this->getStringValue($body);
                    $plural = $this->getStringValue($node->getNode('plural'));
                    $this->messages[] = [
                        $body->getAttribute('data'),
                        $node->hasNode('options') ? $this->getReadDomainFromNode($node->getNode('options')) : Helper::UNDEFINED_DOMAIN,
                        ['plurals' => [$singular, $plural]],
                    ];
                } else {
                    $this->messages[] = [
                        $body->getAttribute('data'),
                        $node->hasNode('options') ? $this->getReadDomainFromNode($node->getNode('options')) : Helper::UNDEFINED_DOMAIN,
                    ];
                }
            }
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

    private function getReadDomainFromArguments(Node $arguments, int $index): string
    {
        if ($arguments->hasNode('options')) {
            $argument = $arguments->getNode('options');
        } elseif ($arguments->hasNode($index)) {
            $argument = $arguments->getNode($index);
        } else {
            return Helper::UNDEFINED_DOMAIN;
        }

        return $this->getReadDomainFromNode($argument);
    }

    private function getReadDomainFromNode(Node $node): string
    {
        if ($node instanceof ArrayExpression) {
            foreach ($node->getKeyValuePairs() as $pair) {
                $key = $this->getConcatValueFromNode($pair['key'], '');
                if ('context' === $key) {
                    return $this->getConcatValueFromNode($pair['value'], '') ?: Helper::UNDEFINED_DOMAIN;
                }
            }
        }

        return Helper::UNDEFINED_DOMAIN;
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

    private function getStringValue(Node $node): string
    {
        $compiled = $this->compileString($node);

        return $compiled[0]->getNode(0)->getAttribute('value');
    }

    /**
     * Verbatim copy of TwigNodeTrans::compileString().
     *
     * @see TwigNodeTrans::compileString()
     */
    private function compileString(Node $body)
    {
        if ($body instanceof NameExpression || $body instanceof ConstantExpression || $body instanceof TempNameExpression) {
            return [$body, []];
        }

        $tokens = [];
        if (count($body)) {
            $text = '';

            foreach ($body as $node) {
                if ($node instanceof PrintNode) {
                    $n = $node->getNode('expr');
                    while ($n instanceof FilterExpression) {
                        $n = $n->getNode('node');
                    }

                    if ($n instanceof CheckToStringNode) {
                        $n = $n->getNode('expr');
                    }
                    $args = $n;

                    // Support TwigExtension->renderVar() function in chain.
                    if ($args instanceof FunctionExpression) {
                        $args = $n->getNode('arguments')->getNode(0);
                    }

                    // Detect if a token implements one of the filters reserved for
                    // modifying the prefix of a token. The default prefix used for
                    // translations is "@". This escapes the printed token and makes them
                    // safe for templates.
                    // @see TwigExtension::getFilters()
                    $argPrefix = '@';
                    while ($args instanceof FilterExpression) {
                        switch ($args->getAttribute('twig_callable')->getName()) {
                            case 'placeholder':
                                $argPrefix = '%';
                                break;
                        }
                        $args = $args->getNode('node');
                    }
                    if ($args instanceof CheckToStringNode) {
                        $args = $args->getNode('expr');
                    }
                    if ($args instanceof GetAttrExpression) {
                        $argName = [];
                        // Reuse the incoming expression.
                        $expr = $args;
                        // Assemble a valid argument name by walking through the expression.
                        $argName[] = $args->getNode('attribute')->getAttribute('value');
                        while ($args->hasNode('node')) {
                            $args = $args->getNode('node');
                            if ($args instanceof NameExpression) {
                                $argName[] = $args->getAttribute('name');
                            } else {
                                $argName[] = $args->getNode('attribute')->getAttribute('value');
                            }
                        }
                        $argName = array_reverse($argName);
                        $argName = implode('.', $argName);
                    } else {
                        $argName = $n->getAttribute('name');
                        if (!is_null($args)) {
                            $argName = $args->getAttribute('name');
                        }
                        $expr = new ContextVariable($argName, $n->getTemplateLine());
                    }
                    $placeholder = sprintf('%s%s', $argPrefix, $argName);
                    $text .= $placeholder;
                    $expr->setAttribute('placeholder', $placeholder);
                    $tokens[] = $expr;
                } else {
                    $text .= $node->getAttribute('data');
                }
            }
        } elseif (!$body->hasAttribute('data')) {
            throw new SyntaxError('{% trans %} tag cannot be empty');
        } else {
            $text = $body->getAttribute('data');
        }

        return [
            new Nodes([new ConstantExpression(trim($text), $body->getTemplateLine())]),
            $tokens,
        ];
    }
}
