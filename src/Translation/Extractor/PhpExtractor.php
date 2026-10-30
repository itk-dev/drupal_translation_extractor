<?php

namespace Drupal\drupal_translation_extractor\Translation\Extractor;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * PhpExtractor is lifted from \Symfony\Component\Translation\Extractor\PhpAstExtractor.
 *
 * @see \Symfony\Component\Translation\Extractor\PhpAstExtractor
 */
class PhpExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    private readonly Parser $parser;

    public function __construct(
        /**
         * @param iterable<AbstractVisitor&NodeVisitor> $visitors
         */
        private readonly iterable $visitors,
        private string $prefix = '',
    ) {
        if (!class_exists(ParserFactory::class)) {
            throw new \LogicException(\sprintf('You cannot use "%s" as the "nikic/php-parser" package is not installed. Try running "composer require nikic/php-parser".', static::class));
        }

        $this->parser = new ParserFactory()->createForHostVersion();
    }

    public function extract(iterable|string $resource, MessageCatalogue $catalogue): void
    {
        /** @var \SplFileInfo $file */
        foreach ($this->extractFiles($resource) as $file) {
            $traverser = new NodeTraverser();

            // This is needed to resolve namespaces in class methods/constants.
            $nameResolver = new NodeVisitor\NameResolver();
            $traverser->addVisitor($nameResolver);

            foreach ($this->visitors as $visitor) {
                $visitor->initialize($catalogue, $file, $this->prefix);
                $traverser->addVisitor($visitor);
            }

            $nodes = $this->parser->parse(file_get_contents($file));
            $traverser->traverse($nodes);
        }
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    protected static array $supportedFileExtensions = [
        'php', 'install', 'module', 'theme',
    ];

    protected function canBeExtracted(string $file): bool
    {
        return parent::canBeExtracted($file)
            && preg_match('/\bt\(|->t(?:rans)?\(|TranslatableMarkup|@Translation\(/i', file_get_contents($file));
    }
}
