<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Test\Unit\Twig\Translation\Extractor;

use Drupal\drupal_translation_extractor\Test\Unit\AbstractTestCase;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Drupal\drupal_translation_extractor\Twig\Translation\Extractor\TwigExtractor;
use Symfony\Component\Translation\MessageCatalogue;

final class TwigExtractorTest extends AbstractTestCase
{
    public function testTransMethod(): void
    {
        $resource = [
            $this->getResourcePath('templates/my_template.html.twig'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
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
        $resource = [
            $this->getResourcePath('templates/my_template_drupal.html.twig'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();
        $this->assertCount(1, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
    }

    private function createExtractor(): TwigExtractor
    {
        return new TwigExtractor($this->createTwig());
    }
}
