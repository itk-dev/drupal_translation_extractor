<?php

declare(strict_types=1);

namespace Drupal\itk_translation_extractor\Test\unit\Extractor;

use Drupal\Core\Template\TwigTransTokenParser;
use Drupal\itk_translation_extractor\ItkTranslationExtractorTwigExtension;
use Drupal\itk_translation_extractor\Translation\Dumper\PoFileDumper;
use Drupal\itk_translation_extractor\Translation\TwigExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;
use Twig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class PoFileDumperTest extends TestCase
{
    public function testFormatCatalog(): void
    {
        $resource = __DIR__.'/resources/';
        $locale = 'da';

        $extractor = new TwigExtractor($this->twig());
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $outputPath = tempnam(sys_get_temp_dir(), 'po_');
        $dumper = new PoFileDumper();
        $output = $dumper->formatCatalogue($messages, '', [
            'path' => dirname($outputPath),
            'output_name' => basename($outputPath),
            'project_name' => 'testFormatCatalog',
        ]);

        $strings = [
            '"Plural-Forms: nplurals=2; plural=(n != 1);\n"',
            '"Language: da\n"',
            join("\n", [
                'msgctxt "the context"',
                'msgid "t filter with options context"',
                'msgstr "t filter with options context"',
            ]),
            join("\n", [
                'msgid "Hello star."',
                'msgid_plural "Hello @count stars."',
                'msgstr[0] "Hello star."',
                'msgstr[1] "Hello @count stars."',
            ]),
        ];
        foreach ($strings as $string) {
            $this->assertStringContainsString($string, $output);
        }
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
