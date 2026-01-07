<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Test\Unit\Translation\Extractor;

use Drupal\drupal_translation_extractor\Test\Unit\AbstractTestCase;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Drupal\drupal_translation_extractor\Translation\Extractor\PhpExtractor;
use Drupal\drupal_translation_extractor\Translation\Extractor\Visitor\TranslatableMarkupVisitor;
use Drupal\drupal_translation_extractor\Translation\Extractor\Visitor\TransMethodVisitor;
use Symfony\Component\Translation\MessageCatalogue;

final class PhpExtractorTest extends AbstractTestCase
{
    public function testTransMethod(): void
    {
        $resource = [
            $this->getResourcePath('src/MyClass.php'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor(visitors: [
            new TransMethodVisitor(),
        ]);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(3, $messages->all(PoItem::NO_CONTEXT));
        $this->assertCount(3, $messages->all('the context'));
        $this->assertContains('the context', $domains);
        $this->assertCount(3, $messages->all('another context'));
        $this->assertContains('another context', $domains);
    }

    public function testTransMethodDrupal(): void
    {
        $resource = [
            $this->getResourcePath('src/MyClassDrupal.php'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor(visitors: [
            new TransMethodVisitor(),
        ]);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(1, $messages->all(PoItem::NO_CONTEXT));
        $this->assertCount(1, $messages->all('the context'));
        $this->assertContains('the context', $domains);
        $this->assertCount(1, $messages->all('another context'));
        $this->assertContains('another context', $domains);
    }

    public function testTransMethodDrupalModule(): void
    {
        $resource = $this->getResourcePath();
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor(visitors: [
            new TransMethodVisitor(),
        ]);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(9, $domains);
    }

    public function testTranslatableMarkup(): void
    {
        $resource = [
            $this->getResourcePath('src/MyClass.php'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor(visitors: [
            new TranslatableMarkupVisitor(),
        ]);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(1, $messages->all(PoItem::NO_CONTEXT));
        $this->assertContains('the context', $domains);
        $this->assertCount(1, $messages->all('the context'));
        $this->assertContains('another context', $domains);
        $this->assertCount(1, $messages->all('another context'));
    }

    public function testTranslatableMarkupDrupal(): void
    {
        $resource = [
            $this->getResourcePath('src/MyClassDrupal.php'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor(
            visitors: [
                new TranslatableMarkupVisitor(),
            ]
        );
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(1, $messages->all(PoItem::NO_CONTEXT));
        $this->assertContains('the context', $domains);
        $this->assertCount(1, $messages->all('the context'));
        $this->assertContains('another context', $domains);
        $this->assertCount(1, $messages->all('another context'));
    }

    private function createExtractor(array $visitors): PhpExtractor
    {
        return new PhpExtractor(visitors: $visitors);
    }
}
