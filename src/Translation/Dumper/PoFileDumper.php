<?php

namespace Drupal\itk_translation_extractor\Translation\Dumper;

use Drupal\Component\Gettext\PoHeader;
use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Translation\Dumper\PoFileDumper as BasePoFileDumper;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @see \Drupal\locale\Form\ExportForm
 */
class PoFileDumper extends BasePoFileDumper
{
    public function __construct(
        private readonly FileSystemInterface $fileSystem,
    ) {
    }

    public function dump(MessageCatalogue $messages, array $options = []): void
    {
        if (!\array_key_exists('path', $options)) {
            throw new InvalidArgumentException('The file dumper needs a path option.');
        }
        if (!\array_key_exists('output_name', $options)) {
            throw new InvalidArgumentException('The file dumper needs an output path option.');
        }

        $output = $this->formatCatalogue($messages, '', $options);
        $outputPath = $options['path'].'/'.$options['output_name'];
        file_put_contents($outputPath, $output);
    }

    public function formatCatalogue(
        MessageCatalogue $messages,
        string $domain,
        array $options = [],
    ): string {
        $header = new PoHeader();
        // Set Plural-Forms
        $spec = '';
        switch ($messages->getLocale()) {
            case 'da':
                $spec = 'Plural-Forms: nplurals=2; plural=(n != 1);';
        }
        $header->setFromString($spec);
        if ($languageName = ($options['language_name'] ?? null)) {
            $header->setLanguageName($languageName);
        }
        if ($projectName = ($options['project_name'] ?? null)) {
            $header->setProjectName($projectName);
        }
        $uri = $this->fileSystem->tempnam('temporary://', 'po_');

        $writer = new PoStreamWriter();
        $writer->setURI($uri);
        $writer->setHeader($header);
        $writer->open();
        foreach ($messages->getDomains() as $domain) {
            foreach ($messages->all($domain) as $source => $translation) {
                $metadata = $messages->getMetadata($domain, $source);
                $item = new PoItem();
                $item->setSource($source);
                $item->setTranslation($translation);
                $writer->writeItem($item);
            }
        }
        $writer->close();

        $output = file_get_contents($writer->getURI());
        unlink($writer->getURI());

        return $output;
    }
}
