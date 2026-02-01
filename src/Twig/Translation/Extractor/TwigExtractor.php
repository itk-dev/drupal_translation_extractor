<?php

namespace Drupal\drupal_translation_extractor\Twig\Translation\Extractor;

use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Drupal\drupal_translation_extractor\Twig\Extension\ItkTranslationExtractorTwigExtension;
use Symfony\Bridge\Twig\Translation\TwigExtractor as BaseTwigExtractor;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Twig\Environment;
use Twig\Source;

class TwigExtractor extends BaseTwigExtractor
{
    /**
     * Prefix for found message.
     */
    private string $prefix = '';

    public function __construct(
        #[Autowire(service: 'twig')]
        private Environment $twig,
    ) {
        parent::__construct($this->twig);
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    protected function extractTemplate(string $template, MessageCatalogue $catalogue): void
    {
        $visitor = $this->twig->getExtension(ItkTranslationExtractorTwigExtension::class)->getTranslationNodeVisitor();

        $visitor->enable();
        $this->twig->parse($this->twig->tokenize(new Source($template, '')));
        foreach ($visitor->getMessages() as $message) {
            $id = trim($message[0]);
            // $translation = Helper::joinStrings(...array_map(static fn (string $string) => '', [...Helper::splitStrings($id)]));
            $translation = PoItem::joinStrings(PoItem::splitStrings($id), $this->prefix);
            $domain = $message[1] ?: PoItem::NO_CONTEXT;
            $catalogue->set($id, $translation, $domain);
        }

        $visitor->disable();
    }

    /**
     * The finder created in parent::extractFromDirectory() does not ignore VCS ignored files.
     *
     * @param string|iterable $directory
     */
    #[\Override]
    protected function extractFromDirectory($directory): iterable
    {
        $finder = new Finder();
        $finder->ignoreVCSIgnored(true);

        return $finder->files()->name('*.twig')->in($directory);
    }
}
