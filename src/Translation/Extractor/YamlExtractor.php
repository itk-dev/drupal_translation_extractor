<?php

namespace Drupal\drupal_translation_extractor\Translation\Extractor;

use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Extractor for YAML files.
 */
class YamlExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    // https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-local-actions
    private const string LINKS_ACTION = 'links.action.yml';
    // https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-contextual-links
    private const string LINKS_CONTEXTUAL = 'links.contextual.yml';
    // https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-menu-links
    private const string LINKS_MENU = 'links.menu.yml';
    // https://www.drupal.org/docs/drupal-apis/menu-api/providing-module-defined-local-tasks
    private const string LINKS_TASK = 'links.task.yml';
    // https://www.drupal.org/docs/drupal-apis/routing-system/structure-of-routes
    private const string ROUTING = 'routing.yml';

    private static array $supportedFileExtensions = [
        self::LINKS_ACTION,
        self::LINKS_CONTEXTUAL,
        self::LINKS_MENU,
        self::LINKS_TASK,
        self::ROUTING,
    ];

    public function __construct(
        private string $prefix = '',
    ) {
    }

    public function extract(iterable|string $resource, MessageCatalogue $catalogue): void
    {
        /** @var \SplFileInfo $file */
        foreach ($this->extractFiles($resource) as $file) {
            $filename = $file->getPathname();
            try {
                $content = Yaml::parseFile($filename);
            } catch (ParseException) {
                // Ignore exception.
                continue;
            }
            if (!is_array($content) || array_is_list($content)) {
                continue;
            }

            switch ($this->getFileExtension($filename)) {
                case self::LINKS_ACTION:
                case self::LINKS_CONTEXTUAL:
                case self::LINKS_MENU:
                case self::LINKS_TASK:
                    $this->extractLinks($content, $catalogue);
                    break;

                case self::ROUTING:
                    $this->extractRouting($content, $catalogue);
                    break;
            }
        }
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    private function getFileExtension(string $file): ?string
    {
        foreach (self::$supportedFileExtensions as $extension) {
            if (str_ends_with($file, $extension)) {
                return $extension;
            }
        }

        return null;
    }

    protected function canBeExtracted(string $file): bool
    {
        return $this->isFile($file)
            && null !== $this->getFileExtension($file);
    }

    protected function extractFromDirectory(array|string $resource): iterable|Finder
    {
        if (!class_exists(Finder::class)) {
            throw new \LogicException(\sprintf('You cannot use "%s" as the "symfony/finder" package is not installed. Try running "composer require symfony/finder".', static::class));
        }

        $finder = new Finder()
            ->ignoreVCSIgnored(true)
            ->followLinks();

        return $finder
            ->files()
            ->name(array_map(static fn (string $ext) => '*.'.$ext, self::$supportedFileExtensions))
            ->in($resource);
    }

    private function extractLinks(array $content, MessageCatalogue $catalogue): void
    {
        $keys = ['title', 'description'];
        foreach ($content as $spec) {
            foreach ($keys as $key) {
                if (isset($spec[$key])) {
                    $id = $spec[$key];
                    $translation = $this->prefix.$id;
                    $domain = $spec[$key.'_context'] ?? PoItem::NO_CONTEXT;
                    $catalogue->set($id, $translation, $domain);
                }
            }
        }
    }

    private function extractRouting(array $content, MessageCatalogue $catalogue): void
    {
        $keys = ['_title'];
        foreach ($content as $spec) {
            foreach ($keys as $key) {
                if (isset($spec['defaults'][$key])) {
                    $id = $spec['defaults'][$key];
                    $translation = $this->prefix.$id;
                    $domain = $spec['defaults'][$key.'_context'] ?? PoItem::NO_CONTEXT;
                    $catalogue->set($id, $translation, $domain);
                }
            }
        }
    }
}
