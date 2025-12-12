<?php

namespace Drupal\itk_translation_extractor\Translation\Extractor;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor;
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
    private Parser $parser;

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

        $this->parser = (new ParserFactory())->createForHostVersion();
    }

    public function extract(iterable|string $resource, MessageCatalogue $catalogue): void
    {
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

    private static array $supportedFileExtensions = [
        'php', 'install', 'module', 'theme',
    ];

    protected function canBeExtracted(string $file): bool
    {
        return in_array(pathinfo($file, \PATHINFO_EXTENSION), self::$supportedFileExtensions, true)
            && $this->isFile($file)
            && preg_match('/\bt\(|->t(?:rans)?\(|TranslatableMarkup/i', file_get_contents($file));
    }

    protected function extractFromDirectory(array|string $resource): iterable|Finder
    {
        if (!class_exists(Finder::class)) {
            throw new \LogicException(\sprintf('You cannot use "%s" as the "symfony/finder" package is not installed. Try running "composer require symfony/finder".', static::class));
        }

        return (new Finder())->files()->name(array_map(static fn (string $ext) => '*.'.$ext, self::$supportedFileExtensions))->in($resource);
    }
}
