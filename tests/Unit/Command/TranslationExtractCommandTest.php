<?php

namespace Drupal\drupal_translation_extractor\Test\Unit\Command;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\drupal_translation_extractor\Command\TranslationExtractCommand;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoFileDumper;
use Drupal\drupal_translation_extractor\Translation\Extractor\PhpExtractor;
use Drupal\locale\StringStorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Translation\Extractor\ChainExtractor;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Twig\Environment;

final class TranslationExtractCommandTest extends TestCase
{
    public function testStuff(): void
    {
        $command = $this->createCommand();
        $input = new ArrayInput([
            'locale' => 'da',
            'source' => 'a/b/c',
            '--dump-messages' => true,
        ]);
        $output = new BufferedOutput();
        $result = $command->run($input, $output);
        $this->assertEquals(Command::SUCCESS, $result);
        $content = $output->fetch();
        $this->assertStringContainsString('[WARNING] No translation messages were found.', $content);
    }

    private function createCommand(): TranslationExtractCommand
    {
        $writer = new TranslationWriter();
        $writer->addDumper('po', new PoFileDumper());

        $reader = new PoStreamReader();

        $extractor = new ChainExtractor();
        $extractor->addExtractor('php',
            new PhpExtractor(visitors: [
                new \Drupal\drupal_translation_extractor\Translation\Extractor\Visitor\TransMethodVisitor(),
                new \Drupal\drupal_translation_extractor\Translation\Extractor\Visitor\TranslatableMarkupVisitor(),
            ]));
        $twig = new Environment(new \Twig\Loader\FilesystemLoader());
        $extractor->addExtractor('twig', new \Drupal\drupal_translation_extractor\Translation\TwigExtractor($twig));

        $extensionPathResolver = $this->createMock(ExtensionPathResolver::class);

        $stringStorage = $this->createStringStorage();

        $command = new TranslationExtractCommand($writer, $reader, $extractor, $extensionPathResolver, $stringStorage);

        return $command;
    }

    private function createStringStorage(): StringStorageInterface
    {
        return new class implements StringStorageInterface {
            public function getStrings(array $conditions = [], array $options = [])
            {
                // TODO: Implement getStrings() method.
            }

            public function getTranslations(array $conditions = [], array $options = [])
            {
                // TODO: Implement getTranslations() method.
            }

            public function getLocations(array $conditions = [])
            {
                // TODO: Implement getLocations() method.
            }

            public function findString(array $conditions)
            {
                // TODO: Implement findString() method.
            }

            public function findTranslation(array $conditions)
            {
                // TODO: Implement findTranslation() method.
            }

            public function save($string)
            {
                // TODO: Implement save() method.
            }

            public function delete($string)
            {
                // TODO: Implement delete() method.
            }

            public function deleteStrings($conditions)
            {
                // TODO: Implement deleteStrings() method.
            }

            public function deleteTranslations($conditions)
            {
                // TODO: Implement deleteTranslations() method.
            }

            public function countStrings()
            {
                // TODO: Implement countStrings() method.
            }

            public function countTranslations()
            {
                // TODO: Implement countTranslations() method.
            }

            public function createString($values = [])
            {
                // TODO: Implement createString() method.
            }

            public function createTranslation($values = [])
            {
                // TODO: Implement createTranslation() method.
            }
        };
    }
}
