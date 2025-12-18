<?php

namespace Drupal\drupal_translation_extractor\Test\Unit;

use Drupal\Core\Template\TwigTransTokenParser;
use Drupal\drupal_translation_extractor\Twig\Extension\ItkTranslationExtractorTwigExtension;
use PHPUnit\Framework\TestCase;
use Twig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class AbstractTestCase extends TestCase
{
    protected function getResourcePath(string $path = ''): string
    {
        return dirname(__DIR__).'/resources/'.$path;
    }

    protected function createTwig(): Environment
    {
        $twig = new Environment(new FilesystemLoader());
        $trans = fn () => null;
        $twig->addFilter(new Twig\TwigFilter('t', $trans));
        $twig->addFilter(new Twig\TwigFilter('trans', $trans));
        $twig->addExtension(new ItkTranslationExtractorTwigExtension());
        $twig->addTokenParser(new TwigTransTokenParser());

        return $twig;
    }

    protected function assertPoFileEqualsPoFile(string $expectedFile, string $actualFile, bool $ignoreHeader = true, string $message = ''): void
    {
        $this->assertFileExists($expectedFile, $message);
        $this->assertFileExists($actualFile, $message);
        $expected = file_get_contents($expectedFile);
        $actual = file_get_contents($actualFile);

        $expected = trim((string) $expected);
        $actual = trim((string) $actual);

        if ($ignoreHeader) {
            if (false !== $index = strpos($expected, "\n\n")) {
                $expected = substr($expected, $index);
            }
            if (false !== $index = strpos($actual, "\n\n")) {
                $actual = substr($actual, $index);
            }
        }

        $this->assertEquals($expected, $actual, $message);
    }
}
