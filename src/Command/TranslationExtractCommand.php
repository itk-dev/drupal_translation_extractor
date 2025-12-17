<?php

declare(strict_types=1);

namespace Drupal\drupal_translation_extractor\Command;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\drupal_translation_extractor\Exception\InvalidArgumentException;
use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Drupal\locale\StringStorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

/**
 * Lifted from Symfony's `translation:extract` command.
 *
 * @see https://github.com/symfony/framework-bundle/blob/7.3/Command/TranslationExtractCommand.php
 */
#[AsCommand(
    name: 'drupal_translation_extractor:translation:extract',
    description: 'Extract missing translations keys from code to translation files',
)]
final class TranslationExtractCommand extends Command
{
    private const ASC = 'asc';
    private const DESC = 'desc';
    private const SORT_ORDERS = [self::ASC, self::DESC];
    private const FORMATS = [
        'xlf12' => ['xlf', '1.2'],
        'xlf20' => ['xlf', '2.0'],
    ];
    private const NO_FILL_PREFIX = "\0NoFill\0";

    public function __construct(
        private readonly TranslationWriterInterface $writer,
        private readonly PoStreamReader $reader,
        private readonly ExtractorInterface $extractor,
        private readonly ExtensionPathResolver $extensionPathResolver,
        private readonly StringStorageInterface $stringStorage,
    ) {
        parent::__construct();

        if (!method_exists($writer, 'getFormats')) {
            throw new \InvalidArgumentException(\sprintf('The writer class "%s" does not implement the "getFormats()" method.', $writer::class));
        }
    }

    protected function configure(): void
    {
        $this
          ->setDefinition([
              new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
              new InputOption('prefix', null, InputOption::VALUE_REQUIRED, 'Override the default prefix', '__'),
              new InputOption('no-fill', null, InputOption::VALUE_NONE, 'Extract translation keys without filling in values'),
              new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Override the default output format', 'po'),
              new InputOption('dump-messages', null, InputOption::VALUE_NONE, 'Should the messages be dumped in the console'),
              new InputOption('force', null, InputOption::VALUE_NONE, 'Should the extract be done'),
              new InputOption('clean', null, InputOption::VALUE_NONE, 'Should clean not found messages'),
              new InputOption('domain', null, InputOption::VALUE_REQUIRED, 'Specify the domain to extract'),
              new InputOption('sort', null, InputOption::VALUE_REQUIRED, 'Return list of messages sorted alphabetically'),
              new InputOption('as-tree', null, InputOption::VALUE_REQUIRED, 'Dump the messages as a tree-like structure: The given value defines the level where to switch to inline YAML'),

              new InputArgument('source', InputArgument::REQUIRED, 'Source path.'),
              new InputOption('output', null, InputOption::VALUE_REQUIRED, 'Output path. Required if --force is specified.'),
              new InputOption('fill-from-string-storage', null, InputOption::VALUE_NONE, 'Fill translations with values from string storage.'),
          ])
          ->setHelp(<<<'EOF'
The <info>%command.name%</info> command extracts translation strings from templates
of a given module or theme or another path. It can display them or merge
the new ones into the translation files.

When new translation strings are found it can automatically add a prefix to the translation
message. However, if the <comment>--no-fill</comment> option is used, the <comment>--prefix</comment>
option has no effect, since the translation values are left empty.

Example running against a module (my_module)

  <info>php %command.full_name% --dump-messages en module:my_module</info>
  <info>php %command.full_name% --force --prefix="new_" fr module:my_module</info>

Example running against a theme (my_theme)

  <info>php %command.full_name% --dump-messages en theme:my_theme</info>
  <info>php %command.full_name% --force --prefix="new_" fr theme:my_theme</info>

You can sort the output with the <comment>--sort</> flag:

    <info>php %command.full_name% --dump-messages --sort=asc en …</info>
    <info>php %command.full_name% --force --sort=desc fr …</info>

EOF
          )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errorIo = $io->getErrorStyle();

        // check presence of force or dump-message
        if (true !== $input->getOption('force') && true !== $input->getOption('dump-messages')) {
            $errorIo->error('You must choose one of --force or --dump-messages');

            return self::FAILURE;
        }
        $outputPath = $input->getOption('output');
        if (null === $outputPath && true === $input->getOption('force')) {
            $errorIo->error('--output is required when --force is specified');

            return self::FAILURE;
        }

        $format = $input->getOption('format');
        $xliffVersion = '1.2';

        if (\array_key_exists($format, self::FORMATS)) {
            [$format, $xliffVersion] = self::FORMATS[$format];
        }

        // check format
        $supportedFormats = $this->writer->getFormats();
        if (!\in_array($format, $supportedFormats, true)) {
            $errorIo->error(['Wrong output format', 'Supported formats are: '.implode(', ', $supportedFormats).', xlf12 and xlf20.']);

            return self::FAILURE;
        }

        // Define Root Paths
        [$codePaths, $sourceInfo] = $this->getRootCodePaths($input);

        $currentName = 'default directory';

        $io->title('Translation Messages Extractor and Dumper');
        $io->comment(\sprintf('Generating "<info>%s</info>" translation files for "<info>%s</info>"', $input->getArgument('locale'), $currentName));

        $io->comment('Parsing templates...');
        $prefix = $input->getOption('no-fill') ? self::NO_FILL_PREFIX : $input->getOption('prefix');
        $extractedCatalogue = $this->extractMessages($input->getArgument('locale'), $codePaths, $prefix);

        $io->comment('Loading translated messages...');
        $outputPath = $this->getOutputPath($input, $sourceInfo + [
            'locale' => $input->getArgument('locale'),
        ]);

        $currentCatalogue = $this->loadCurrentMessages($input->getArgument('locale'), $outputPath);
        if (true === $input->getOption('fill-from-string-storage')) {
            // Merge in translations from string storage.
            $currentCatalogue = new MergeOperation(
                $this->loadTranslatedMessages($extractedCatalogue),
                $currentCatalogue)->getResult();
        }

        if (null !== $domain = $input->getOption('domain')) {
            if ('' === $domain) {
                $domain = PoItem::NO_CONTEXT;
            }
            $currentCatalogue = $this->filterCatalogue($currentCatalogue, $domain);
            $extractedCatalogue = $this->filterCatalogue($extractedCatalogue, $domain);
        }

        // process catalogues
        $operation = $input->getOption('clean')
          ? new TargetOperation($currentCatalogue, $extractedCatalogue)
          : new MergeOperation($currentCatalogue, $extractedCatalogue);

        // Exit if no messages found.
        if (!\count($operation->getDomains())) {
            $errorIo->warning('No translation messages were found.');

            return self::SUCCESS;
        }

        $resultMessage = 'Translation files were successfully updated';

        // $operation->moveMessagesToIntlDomainsIfPossible('new');

        if ($sort = $input->getOption('sort')) {
            $sort = strtolower($sort);
            if (!\in_array($sort, self::SORT_ORDERS, true)) {
                $errorIo->error(['Wrong sort order', 'Supported formats are: '.implode(', ', self::SORT_ORDERS).'.']);

                return self::FAILURE;
            }
        }

        // show compiled list of messages
        if (true === $input->getOption('dump-messages')) {
            $extractedMessagesCount = 0;
            $io->newLine();
            foreach ($operation->getDomains() as $domain) {
                $newKeys = array_keys($operation->getNewMessages($domain));
                $allKeys = array_keys($operation->getMessages($domain));

                $list = array_merge(
                    array_diff($allKeys, $newKeys),
                    array_map(fn ($id) => \sprintf('<fg=green>%s</>', $id), $newKeys),
                    array_map(fn ($id) => \sprintf('<fg=red>%s</>', $id), array_keys($operation->getObsoleteMessages($domain)))
                );

                $domainMessagesCount = \count($list);

                if (self::DESC === $sort) {
                    rsort($list);
                } else {
                    sort($list);
                }

                $io->section(\sprintf('Messages extracted for context "<info>%s</info>" (%d message%s)', PoItem::formatContext($domain), $domainMessagesCount, $domainMessagesCount > 1 ? 's' : ''));
                $io->listing($list);

                $extractedMessagesCount += $domainMessagesCount;
            }

            if ('xlf' === $format) {
                $io->comment(\sprintf('Xliff output version is <info>%s</info>', $xliffVersion));
            }

            $resultMessage = \sprintf('%d message%s successfully extracted', $extractedMessagesCount, $extractedMessagesCount > 1 ? 's were' : ' was');
        }

        // save the files
        if (true === $input->getOption('force')) {
            $io->comment('Writing files...');

            $operationResult = $operation->getResult();
            assert($operationResult instanceof MessageCatalogue);
            if ($sort) {
                $operationResult = $this->sortCatalogue($operationResult, $sort);
            }

            if (true === $input->getOption('no-fill')) {
                $this->removeNoFillTranslations($operationResult);
            }

            $dumperOptions = [
                'path' => dirname($outputPath),
                'output_name' => basename($outputPath),
                // 'default_locale' => $this->defaultLocale,
                'xliff_version' => $xliffVersion,
                'as_tree' => $input->getOption('as-tree'),
                'inline' => $input->getOption('as-tree') ?? 0,
                'empty_prefix' => $input->getOption('prefix'),
            ];

            $this->writer->write($operationResult, $format, $dumperOptions);

            if (true === $input->getOption('dump-messages')) {
                $resultMessage .= ' and translation files were updated';
            }
        }

        $io->success($resultMessage.'.');

        return self::SUCCESS;
    }

    private function filterCatalogue(MessageCatalogue $catalogue, string $domain): MessageCatalogue
    {
        $filteredCatalogue = new MessageCatalogue($catalogue->getLocale());

        // extract intl-icu messages only
        $intlDomain = $domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;
        if ($intlMessages = $catalogue->all($intlDomain)) {
            $filteredCatalogue->add($intlMessages, $intlDomain);
        }

        // extract all messages and subtract intl-icu messages
        if ($messages = array_diff($catalogue->all($domain), $intlMessages)) {
            $filteredCatalogue->add($messages, $domain);
        }
        foreach ($catalogue->getResources() as $resource) {
            $filteredCatalogue->addResource($resource);
        }

        if ($metadata = $catalogue->getMetadata('', $intlDomain)) {
            foreach ($metadata as $k => $v) {
                $filteredCatalogue->setMetadata($k, $v, $intlDomain);
            }
        }

        if ($metadata = $catalogue->getMetadata('', $domain)) {
            foreach ($metadata as $k => $v) {
                $filteredCatalogue->setMetadata($k, $v, $domain);
            }
        }

        return $filteredCatalogue;
    }

    private function sortCatalogue(MessageCatalogue $catalogue, string $sort): MessageCatalogue
    {
        $sortedCatalogue = new MessageCatalogue($catalogue->getLocale());

        $domains = $catalogue->getDomains();
        if (self::DESC === $sort) {
            rsort($domains);
        } elseif (self::ASC === $sort) {
            sort($domains);
        }

        foreach ($domains as $domain) {
            // extract intl-icu messages only
            $intlDomain = $domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;
            if ($intlMessages = $catalogue->all($intlDomain)) {
                if (self::DESC === $sort) {
                    krsort($intlMessages);
                } elseif (self::ASC === $sort) {
                    ksort($intlMessages);
                }

                $sortedCatalogue->add($intlMessages, $intlDomain);
            }

            // extract all messages and subtract intl-icu messages
            if ($messages = array_diff($catalogue->all($domain), $intlMessages)) {
                if (self::DESC === $sort) {
                    krsort($messages);
                } elseif (self::ASC === $sort) {
                    ksort($messages);
                }

                $sortedCatalogue->add($messages, $domain);
            }

            if ($metadata = $catalogue->getMetadata('', $intlDomain)) {
                foreach ($metadata as $k => $v) {
                    $sortedCatalogue->setMetadata($k, $v, $intlDomain);
                }
            }

            if ($metadata = $catalogue->getMetadata('', $domain)) {
                foreach ($metadata as $k => $v) {
                    $sortedCatalogue->setMetadata($k, $v, $domain);
                }
            }
        }

        foreach ($catalogue->getResources() as $resource) {
            $sortedCatalogue->addResource($resource);
        }

        return $sortedCatalogue;
    }

    private function extractMessages(string $locale, array $transPaths, string $prefix): MessageCatalogue
    {
        $extractedCatalogue = new MessageCatalogue($locale);
        $this->extractor->setPrefix($prefix);
        $transPaths = $this->filterDuplicateTransPaths($transPaths);
        foreach ($transPaths as $path) {
            if (is_dir($path) || is_file($path)) {
                $this->extractor->extract($path, $extractedCatalogue);
            }
        }

        return $extractedCatalogue;
    }

    private function filterDuplicateTransPaths(array $transPaths): array
    {
        $transPaths = array_filter(array_map('realpath', $transPaths));

        sort($transPaths);

        $filteredPaths = [];

        foreach ($transPaths as $path) {
            foreach ($filteredPaths as $filteredPath) {
                if (str_starts_with($path, $filteredPath.\DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            $filteredPaths[] = $path;
        }

        return $filteredPaths;
    }

    /**
     * Load current messages from po file.
     */
    private function loadCurrentMessages(string $locale, string $path): MessageCatalogue
    {
        $currentCatalogue = new MessageCatalogue($locale);

        if (!is_file($path)) {
            return $currentCatalogue;
        }

        $this->reader->setURI($path);
        $this->reader->open();
        while ($item = $this->reader->readItem()) {
            if (!empty($item->getTranslation())) {
                $currentCatalogue->set(
                    PoItem::joinStrings((array) $item->getSource()),
                    PoItem::joinStrings((array) $item->getTranslation()),
                    PoItem::fromContext($item->getContext()),
                );
            }
        }
        $this->reader->close();

        return $currentCatalogue;
    }

    private function loadTranslatedMessages(MessageCatalogue $extractedCatalogue): MessageCatalogue
    {
        $translatedMessages = new MessageCatalogue($extractedCatalogue->getLocale());

        foreach ($extractedCatalogue->getDomains() as $domain) {
            $translations = $this->stringStorage->getTranslations([
                'language' => $translatedMessages->getLocale(),
                'context' => PoItem::formatContext($domain),
            ]);
            // Index by source
            $translations = array_column($translations, null, 'source');
            foreach ($extractedCatalogue->all($domain) as $source => $_) {
                if ($translation = ($translations[$source] ?? null)) {
                    if ($string = $translation->getString()) {
                        $translatedMessages->set($source, $string, $domain);
                    }
                }
            }
        }

        return $translatedMessages;
    }

    private function removeNoFillTranslations(MessageCatalogueInterface $operation): void
    {
        foreach ($operation->getDomains() as $domain) {
            foreach ($operation->all($domain) as $key => $message) {
                if (str_starts_with($message, self::NO_FILL_PREFIX)) {
                    $operation->set($key, '', $domain);
                }
            }
        }
    }

    /**
     * @return array
     *               - array of source paths (only one)
     *               - array info
     */
    private function getRootCodePaths(InputInterface $input): array
    {
        $info = [];
        $source = $input->getArgument('source');

        if (empty($source)) {
            throw new InvalidArgumentException('Argument "source" is required.');
        }

        // Expand module and theme paths.
        $source = preg_replace_callback(
            '/(module|theme):([a-z0-9_]+)/i',
            function (array $matches) use (&$info): string {
                $info[$matches[1]] = $matches[2];

                $path = $this->extensionPathResolver->getPath($matches[1], $matches[2]);

                if (empty($path)) {
                    throw new InvalidArgumentException(sprintf('Invalid %s: %s', $matches[1], $matches[2]));
                }

                return $path;
            },
            $source,
        );

        $info['source'] = $source;

        return [[$source], $info];
    }

    private function getOutputPath(InputInterface $input, array $sourceInfo): string
    {
        $output = $input->getOption('output');

        if ($source = ($sourceInfo['source'] ?? null)) {
            $sourceInfo['source_dir'] = is_dir($source) ? $source : dirname($source);
        }
        $sourceInfo['project'] = $sourceInfo['module'] ?? $sourceInfo['theme'] ?? null;
        $sourceInfo['language'] = $sourceInfo['locale'] ?? null;

        // Remove empty values.
        $sourceInfo = array_filter($sourceInfo);

        return preg_replace_callback(
            '/%([a-z0-9_]+)/i',
            fn (array $matches) => $sourceInfo[$matches[1]] ?? $matches[0],
            (string) $output,
        );
    }
}
