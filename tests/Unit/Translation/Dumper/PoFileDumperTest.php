<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Test\Unit\Translation\Dumper;

use Drupal\drupal_translation_extractor\Test\Unit\AbstractTestCase;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoFileDumper;
use Drupal\drupal_translation_extractor\Twig\Translation\Extractor\TwigExtractor;
use Symfony\Component\Translation\MessageCatalogue;

final class PoFileDumperTest extends AbstractTestCase
{
    public function testFormatCatalogDa(): void
    {
        $resource = $this->getResourcePath();
        $locale = 'da';

        $extractor = new TwigExtractor($this->createTwig());
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
        $resource = $this->getResourcePath();
        $locale = 'da';

        $extractor = new TwigExtractor($this->createTwig());
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
        $resource = $this->getResourcePath();
        $locale = 'pl';

        $extractor = new TwigExtractor($this->createTwig());
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
}
