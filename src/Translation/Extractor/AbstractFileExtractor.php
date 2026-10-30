<?php

namespace Drupal\drupal_translation_extractor\Translation\Extractor;

use Drupal\drupal_translation_extractor\Options\FinderOptions;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor as BaseAbstractFileExtractor;

abstract class AbstractFileExtractor extends BaseAbstractFileExtractor
{
    protected static array $supportedFileExtensions = [];

    protected function canBeExtracted(string $file): bool
    {
        return $this->isFile($file)
            && null !== $this->getFileExtension($file);
    }

    protected function getFileExtension(string $file): ?string
    {
        foreach (static::$supportedFileExtensions as $extension) {
            if (str_ends_with($file, '.'.$extension)) {
                return $extension;
            }
        }

        return null;
    }

    protected function extractFromDirectory(array|string $resource): iterable|Finder
    {
        if (!class_exists(Finder::class)) {
            throw new \LogicException(\sprintf('You cannot use "%s" as the "symfony/finder" package is not installed. Try running "composer require symfony/finder".', static::class));
        }

        $finder = new Finder()
            ->ignoreVCSIgnored(FinderOptions::getIgnoreVCSIgnored())
            ->followLinks();

        return $finder
            ->files()
            ->name(array_map(static fn (string $ext) => '*.'.$ext, static::$supportedFileExtensions))
            ->in($resource);
    }
}
