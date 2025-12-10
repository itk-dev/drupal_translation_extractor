<?php

declare(strict_types=1);

namespace Drupal\itk_translation_extractor\Test\unit\Extractor;

use Drupal\Core\Template\TwigTransTokenParser;
use Drupal\itk_translation_extractor\ItkTranslationExtractorTwigExtension;
use Drupal\itk_translation_extractor\Translation\Dumper\PoFileDumper;
use Drupal\itk_translation_extractor\Translation\TwigExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Twig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class PoFileDumperTest extends TestCase
{
    public function testTransMethod(): void
    {
        $extractor = new TwigExtractor($this->twig());
        $resource = __DIR__.'/resources/';
        $locale = 'da';
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $outputPath = tempnam(sys_get_temp_dir(), 'po_');
        $dumper = new PoFileDumper();
        $dumper->dump($messages, [
            'path' => dirname($outputPath),
            'output_name' => basename($outputPath),
        ]);

        //    $content = file_get_contents($outputPath);
        $loader = new PoFileLoader();
        $messages = $loader->load($outputPath, $locale, '');
        // @todo
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
