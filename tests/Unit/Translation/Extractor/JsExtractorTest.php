<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Test\Unit\Translation\Extractor;

use Drupal\drupal_translation_extractor\Test\Unit\AbstractTestCase;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Drupal\drupal_translation_extractor\Translation\Extractor\JsExtractor;
use Symfony\Component\Translation\MessageCatalogue;

final class JsExtractorTest extends AbstractTestCase
{
    public function testDrupalT(): void
    {
        $resource = [
            $this->getResourcePath('js/script.js'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(2, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(2, $messages->all(PoItem::NO_CONTEXT));
        $this->assertCount(2, $messages->all('the context'));
        $this->assertContains('the context', $domains);
    }

    private function createExtractor(): JsExtractor
    {
        return new JsExtractor();
    }
}
