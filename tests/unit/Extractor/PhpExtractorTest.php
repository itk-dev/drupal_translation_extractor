<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PhpExtractorTest extends TestCase
{
  public function testExtract(): void
    {
        $extractor = new \Drupal\itk_translation_extractor\Extractor\PhpExtractor();
        $extractor->extract();

        $this->assertSame($string, '');
    }
  }
