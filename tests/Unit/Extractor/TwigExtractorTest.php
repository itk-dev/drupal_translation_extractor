<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Test\Unit\Extractor;

use Drupal\Core\Template\TwigTransTokenParser;
use Drupal\drupal_translation_extractor\ItkTranslationExtractorTwigExtension;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Drupal\drupal_translation_extractor\Translation\TwigExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;
use Twig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigExtractorTest extends TestCase
{
    public function testTransMethod(): void
    {
        $extractor = new TwigExtractor($this->twig());
        $resource = [
            __DIR__.'/resources/templates/my_template.html.twig',
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(3, $messages->all(PoItem::NO_CONTEXT));
        $this->assertCount(3, $messages->all('the context'));
        $this->assertContains('the context', $domains);
        $this->assertCount(2, $messages->all('another context'));
        $this->assertContains('another context', $domains);
    }

    public function testDrupalTransMethod(): void
    {
        $extractor = new TwigExtractor($this->twig());
        $resource = [
            __DIR__.'/resources/templates/my_template_drupal.html.twig',
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();
        $this->assertCount(1, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
    }

    private function twig(): Environment
    {
        $twig = new Environment(new FilesystemLoader());
        $trans = fn () => null;
        $twig->addFilter(new Twig\TwigFilter('t', $trans));
        $twig->addFilter(new Twig\TwigFilter('trans', $trans));
        $twig->addExtension(new ItkTranslationExtractorTwigExtension());
        $twig->addTokenParser(new TwigTransTokenParser());

        return $twig;
    }
}
