<?php

declare(strict_types=1);

use Drupal\itk_translation_extractor\Translation\Extractor\PhpExtractor;
use Drupal\itk_translation_extractor\Translation\Extractor\Visitor\TransMethodVisitor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;

final class PhpExtractorTest extends TestCase
{
    public function testExtract(): void
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
}
