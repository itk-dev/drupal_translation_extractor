<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Test\Unit\Translation\Extractor;

use Drupal\drupal_translation_extractor\Test\Unit\AbstractTestCase;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Drupal\drupal_translation_extractor\Translation\Extractor\YamlExtractor;
use Symfony\Component\Translation\MessageCatalogue;

final class YamlExtractorTest extends AbstractTestCase
{
    public function testLinksAction(): void
    {
        $resource = [
            $this->getResourcePath('modules/my_module/my_module.links.action.yml'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(1, $messages->all(PoItem::NO_CONTEXT));
        $this->assertContains('admin', $domains);
        $this->assertCount(1, $messages->all('admin'));
        $this->assertContains('editor', $domains);
        $this->assertCount(1, $messages->all('editor'));
    }

    public function testLinksContextual(): void
    {
        $resource = [
            $this->getResourcePath('modules/my_module/my_module.links.contextual.yml'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(1, $messages->all(PoItem::NO_CONTEXT));
        $this->assertContains('admin', $domains);
        $this->assertCount(1, $messages->all('admin'));
        $this->assertContains('editor', $domains);
        $this->assertCount(1, $messages->all('editor'));
    }

    public function testLinksMenu(): void
    {
        $resource = [
            $this->getResourcePath('modules/my_module/my_module.links.menu.yml'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(1, $messages->all(PoItem::NO_CONTEXT));
        $this->assertContains('admin', $domains);
        $this->assertCount(1, $messages->all('admin'));
        $this->assertContains('editor', $domains);
        $this->assertCount(1, $messages->all('editor'));
    }

    public function testLinksTask(): void
    {
        $resource = [
            $this->getResourcePath('modules/my_module/my_module.links.task.yml'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(1, $messages->all(PoItem::NO_CONTEXT));
        $this->assertContains('admin', $domains);
        $this->assertCount(1, $messages->all('admin'));
        $this->assertContains('editor', $domains);
        $this->assertCount(1, $messages->all('editor'));
    }

    public function testRouting(): void
    {
        $resource = [
            $this->getResourcePath('modules/my_module/my_module.routing.yml'),
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(1, $messages->all(PoItem::NO_CONTEXT));
        $this->assertContains('admin', $domains);
        $this->assertCount(1, $messages->all('admin'));
        $this->assertContains('editor', $domains);
        $this->assertCount(1, $messages->all('editor'));
    }

    public function testAll(): void
    {
        $resource = $this->getResourcePath('.');
        $locale = 'da';
        $messages = new MessageCatalogue($locale);

        $extractor = $this->createExtractor();
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(PoItem::NO_CONTEXT, $domains);
        $this->assertCount(5, $messages->all(PoItem::NO_CONTEXT));
        $this->assertContains('admin', $domains);
        $this->assertCount(5, $messages->all('admin'));
        $this->assertContains('editor', $domains);
        $this->assertCount(5, $messages->all('editor'));
    }

    private function createExtractor(): YamlExtractor
    {
        return new YamlExtractor();
    }
}
