<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Test\Unit\Extractor;

use Drupal\Core\Template\TwigTransTokenParser;
use Drupal\drupal_translation_extractor\ItkTranslationExtractorTwigExtension;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoFileDumper;
use Drupal\drupal_translation_extractor\Translation\TwigExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;
use Twig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class PoFileDumperTest extends TestCase
{
    public function testFormatCatalogDa(): void
    {
        $resource = __DIR__.'/resources/';
        $locale = 'da';

        $extractor = new TwigExtractor($this->twig());
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $dumper = new PoFileDumper();
        $output = $dumper->formatCatalogue($messages, '', [
            'project_name' => 'testFormatCatalog',
        ]);

        $strings = [
            $this->line('# Danish translation of testFormatCatalog'),
            $this->line('"Plural-Forms: nplurals=2; plural=(n != 1);\n"'),
            $this->line('"Language: da\n"'),
            $this->block([
                'msgctxt "the context"',
                'msgid "t filter with options context"',
                'msgstr "t filter with options context"',
            ]),
            $this->block([
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

    public function testFormatCatalogDaWithPrefix(): void
    {
        $resource = __DIR__.'/resources/';
        $locale = 'da';

        $extractor = new TwigExtractor($this->twig());
        $messages = new MessageCatalogue($locale);
        $extractor->setPrefix('__');
        $extractor->extract($resource, $messages);

        $dumper = new PoFileDumper();
        $output = $dumper->formatCatalogue($messages, '', [
            'project_name' => 'testFormatCatalog',
            'empty_prefix' => '__',
        ]);

        $strings = [
            $this->block([
                'msgctxt "the context"',
                'msgid "t filter with options context"',
                'msgstr "__t filter with options context"',
            ]),
            $this->block([
                'msgid "Hello star."',
                'msgid_plural "Hello @count stars."',
                'msgstr[0] "__Hello star."',
                'msgstr[1] "__Hello @count stars."',
            ]),

            $this->block([
                '#, fuzzy',
                'msgctxt "the context"',
                'msgid "t filter with options context"',
                'msgstr "__t filter with options context"',
            ]),
        ];
        foreach ($strings as $string) {
            $this->assertStringContainsString($string, $output);
        }
    }

    public function testFormatCatalogPl(): void
    {
        $resource = __DIR__.'/resources/';
        $locale = 'pl';

        $extractor = new TwigExtractor($this->twig());
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $dumper = new PoFileDumper();
        $output = $dumper->formatCatalogue($messages, '', [
            'project_name' => 'testFormatCatalog',
        ]);

        $strings = [
            $this->line('# Polish translation of testFormatCatalog'),
            $this->line('"Plural-Forms: nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);\n"'),
            $this->line('"Language: pl\n"'),
            $this->block([
                'msgctxt "the context"',
                'msgid "t filter with options context"',
                'msgstr "t filter with options context"',
            ]),
            $this->block([
                'msgid "Hello star."',
                'msgid_plural "Hello @count stars."',
                'msgstr[0] "Hello star."',
                'msgstr[1] "Hello @count stars."',
                'msgstr[2] ""',
            ]),
        ];
        foreach ($strings as $string) {
            $this->assertStringContainsString($string, $output);
        }
    }

    private function line(string $string): string
    {
        return $string."\n";
    }

    private function block(array $strings): string
    {
        return implode('', array_map($this->line(...), $strings))."\n";
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
