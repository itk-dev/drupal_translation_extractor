<?php

namespace Drupal\drupal_translation_extractor\Test\Unit\Command;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\drupal_translation_extractor\Command\TranslationExtractCommand;
use Drupal\drupal_translation_extractor\Test\Unit\AbstractTestCase;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoFileDumper;
use Drupal\drupal_translation_extractor\Translation\Extractor\PhpExtractor;
use Drupal\drupal_translation_extractor\Translation\Extractor\Visitor\TranslatableMarkupVisitor;
use Drupal\drupal_translation_extractor\Translation\Extractor\Visitor\TransMethodVisitor;
use Drupal\drupal_translation_extractor\Twig\Translation\Extractor\TwigExtractor;
use Drupal\locale\StringStorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Translation\Extractor\ChainExtractor;
use Symfony\Component\Translation\Writer\TranslationWriter;

final class TranslationExtractCommandTest extends AbstractTestCase
{
    public function testNoTranslations(): void
    {
        $command = $this->createCommand();
        $input = new ArrayInput([
            'locale' => 'da',
            'source' => $this->getResourcePath('translations'),
            '--dump-messages' => true,
        ]);
        $output = new BufferedOutput();
        $result = $command->run($input, $output);
        $this->assertEquals(Command::SUCCESS, $result);
        $content = $output->fetch();
        $this->assertStringContainsString('[WARNING] No translation messages were found.', $content);
    }

    public function testNoTranslationsOutput(): void
    {
        $outputPath = tempnam(sys_get_temp_dir(), __METHOD__);

        $command = $this->createCommand();
        $input = new ArrayInput([
            'locale' => 'da',
            'source' => $this->getResourcePath('templates/'),
            '--force' => true,
            '--output' => $outputPath,
        ]);
        $expectedPath = $this->getResourcePath('expected/'.__FUNCTION__.'.da.po');

        $output = new BufferedOutput();
        $result = $command->run($input, $output);
        $this->assertEquals(Command::SUCCESS, $result);
        $content = $output->fetch();
        $this->assertStringNotContainsString('[WARNING] No translation messages were found.', $content);

        $this->assertPoFileEqualsPoFile($expectedPath, $outputPath);
    }

    private function createCommand(): TranslationExtractCommand
    {
        $writer = new TranslationWriter();
        $writer->addDumper('po', new PoFileDumper());

        $reader = new PoStreamReader();

        $extractor = new ChainExtractor();
        $extractor->addExtractor('php',
            new PhpExtractor(visitors: [
                new TransMethodVisitor(),
                new TranslatableMarkupVisitor(),
            ]));
        $extractor->addExtractor('twig', new TwigExtractor($this->createTwig()));

        $extensionPathResolver = $this->createStub(ExtensionPathResolver::class);
        $stringStorage = $this->createStub(StringStorageInterface::class);

        return new TranslationExtractCommand($writer, $reader, $extractor, $extensionPathResolver, $stringStorage);
    }
}
