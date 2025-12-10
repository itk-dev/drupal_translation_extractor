<?php

declare(strict_types=1);

namespace Drupal\itk_translation_extractor\Test\unit\Extractor;

use Drupal\itk_translation_extractor\Translation\Extractor\PhpExtractor;
use Drupal\itk_translation_extractor\Translation\Extractor\Visitor\TranslatableMarkupVisitor;
use Drupal\itk_translation_extractor\Translation\Extractor\Visitor\TransMethodVisitor;
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
            __DIR__.'/resources/MyClass.php',
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains('', $domains);
        $this->assertContains('the context', $domains);
        $this->assertContains('another context', $domains);
        $this->assertCount(3, $messages->all(''));
        $this->assertCount(3, $messages->all('the context'));
        $this->assertCount(3, $messages->all('another context'));
    }

    public function testTranslatableMarkup(): void
    {
        $visitors = [
            new TranslatableMarkupVisitor(),
        ];
        $resource = [
            __DIR__.'/resources/MyClass.php',
        ];
        $locale = 'da';

        $extractor = new PhpExtractor($visitors);
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains('', $domains);
        $this->assertContains('the context', $domains);
        $this->assertContains('another context', $domains);
        $this->assertCount(1, $messages->all(''));
        $this->assertCount(1, $messages->all('the context'));
        $this->assertCount(1, $messages->all('another context'));
    }
}
