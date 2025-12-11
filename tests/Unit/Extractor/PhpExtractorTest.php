<?php

declare(strict_types=1);

namespace Drupal\itk_translation_extractor\Test\Unit\Extractor;

use Drupal\itk_translation_extractor\Translation\Extractor\PhpExtractor;
use Drupal\itk_translation_extractor\Translation\Extractor\Visitor\TranslatableMarkupVisitor;
use Drupal\itk_translation_extractor\Translation\Extractor\Visitor\TransMethodVisitor;
use Drupal\itk_translation_extractor\Translation\Helper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;

final class PhpExtractorTest extends TestCase
{
    public function testTransMethod(): void
    {
        $visitors = [
            new TransMethodVisitor(),
        ];
        $extractor = new PhpExtractor($visitors);
        $resource = [
            __DIR__.'/resources/src/MyClass.php',
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(Helper::UNDEFINED_DOMAIN, $domains);
        $this->assertCount(3, $messages->all(Helper::UNDEFINED_DOMAIN));
        $this->assertCount(3, $messages->all('the context'));
        $this->assertContains('the context', $domains);
        $this->assertCount(3, $messages->all('another context'));
        $this->assertContains('another context', $domains);
    }

    public function testTransMethodDrupal(): void
    {
        $visitors = [
            new TransMethodVisitor(),
        ];
        $extractor = new PhpExtractor($visitors);
        $resource = [
            __DIR__.'/resources/src/MyClassDrupal.php',
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(Helper::UNDEFINED_DOMAIN, $domains);
        $this->assertCount(1, $messages->all(Helper::UNDEFINED_DOMAIN));
        $this->assertCount(1, $messages->all('the context'));
        $this->assertContains('the context', $domains);
        $this->assertCount(1, $messages->all('another context'));
        $this->assertContains('another context', $domains);
    }

    public function testTranslatableMarkup(): void
    {
        $visitors = [
            new TranslatableMarkupVisitor(),
        ];
        $resource = [
            __DIR__.'/resources/src/MyClass.php',
        ];
        $locale = 'da';

        $extractor = new PhpExtractor($visitors);
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(Helper::UNDEFINED_DOMAIN, $domains);
        $this->assertCount(1, $messages->all(Helper::UNDEFINED_DOMAIN));
        $this->assertContains('the context', $domains);
        $this->assertCount(1, $messages->all('the context'));
        $this->assertContains('another context', $domains);
        $this->assertCount(1, $messages->all('another context'));
    }

    public function testTranslatableMarkupDrupal(): void
    {
        $visitors = [
            new TranslatableMarkupVisitor(),
        ];
        $resource = [
            __DIR__.'/resources/src/MyClassDrupal.php',
        ];
        $locale = 'da';

        $extractor = new PhpExtractor($visitors);
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains(Helper::UNDEFINED_DOMAIN, $domains);
        $this->assertCount(1, $messages->all(Helper::UNDEFINED_DOMAIN));
        $this->assertContains('the context', $domains);
        $this->assertCount(1, $messages->all('the context'));
        $this->assertContains('another context', $domains);
        $this->assertCount(1, $messages->all('another context'));
    }
}
