<?php

namespace Drupal\drupal_translation_extractor\Translation\Extractor;

use Drupal\drupal_translation_extractor\Translation\Dumper\PoItem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * JsExtractor.
 *
 * Most of this code is lifted from https://git.drupalcode.org/project/drupal/-/blob/11.3.3/core/modules/locale/locale.module.
 *
 * @see https://git.drupalcode.org/project/drupal/-/blob/11.3.3/core/modules/locale/locale.module
 */
class JsExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    public function __construct(
        private string $prefix = '',
    ) {
    }

    public function extract(iterable|string $resource, MessageCatalogue $catalogue): void
    {
        /** @var \SplFileInfo $file */
        foreach ($this->extractFiles($resource) as $file) {
            $filename = $file->getPathname();
            $messages = $this->extractMessages($filename);
            $this->canBeExtracted($filename);

            foreach ($messages as $message) {
                $id = $message['source'];
                $translation = PoItem::joinStrings(PoItem::splitStrings($id), $this->prefix);
                $domain = $message['context'] ?: PoItem::NO_CONTEXT;
                $catalogue->set($id, $translation, $domain);
            }
        }
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    private static array $supportedFileExtensions = [
        'js',
    ];

    protected function canBeExtracted(string $file): bool
    {
        return in_array(pathinfo($file, \PATHINFO_EXTENSION), self::$supportedFileExtensions, true)
            && $this->isFile($file)
            && preg_match('/\bDrupal\.(t|formatPlural)\(/', file_get_contents($file));
    }

    protected function extractFromDirectory(array|string $resource): iterable|Finder
    {
        if (!class_exists(Finder::class)) {
            throw new \LogicException(\sprintf('You cannot use "%s" as the "symfony/finder" package is not installed. Try running "composer require symfony/finder".', static::class));
        }

        return (new Finder())
            ->ignoreVCSIgnored(true)
            ->files()->name(array_map(static fn (string $ext) => '*.'.$ext,
                self::$supportedFileExtensions))->in($resource);
    }

    /**
     * Lifted from locale.module.
     */
    private const string LOCALE_JS_STRING = '(?:(?:\'(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+';
    private const string LOCALE_JS_OBJECT = '\{.*?\}';
    private const string LOCALE_JS_OBJECT_CONTEXT = '
  \{              # match object literal start
  .*?             # match anything, non-greedy
  (?:             # match a form of "context"
    \'context\'
    |
    "context"
    |
    context
  )
  \s*:\s*         # match key-value separator ":"
  ('.self::LOCALE_JS_STRING.')  # match context string
  .*?             # match anything, non-greedy
  \}              # match end of object literal
';

    /**
     * Lifted from _locale_parse_js_file().
     */
    private function extractMessages(string $filepath): array
    {
        // Load the JavaScript file.
        $file = file_get_contents($filepath);

        // Match all calls to Drupal.t() in an array.
        // Note: \s also matches newlines with the 's' modifier.
        preg_match_all('~
    [^\w]Drupal\s*\.\s*t\s*                       # match "Drupal.t" with whitespace
    \(\s*                                         # match "(" argument list start
    ('.self::LOCALE_JS_STRING.')\s*                 # capture string argument
    (?:,\s*'.self::LOCALE_JS_OBJECT.'\s*            # optionally capture str args
      (?:,\s*'.self::LOCALE_JS_OBJECT_CONTEXT.'\s*) # optionally capture context
    ?)?                                           # close optional args
    [,\)]                                         # match ")" or "," to finish
    ~sx', $file, $t_matches);

        // Match all Drupal.formatPlural() calls in another array.
        preg_match_all('~
    [^\w]Drupal\s*\.\s*formatPlural\s*  # match "Drupal.formatPlural" with whitespace
    \(                                  # match "(" argument list start
    \s*.+?\s*,\s*                       # match count argument
    ('.self::LOCALE_JS_STRING.')\s*,\s*   # match singular string argument
    (                             # capture plural string argument
      (?:                         # non-capturing group to repeat string pieces
        (?:
          \'(?:\\\\\'|[^\'])*\'   # match single-quoted string with any character except unescaped single-quote
          |
          "(?:\\\\"|[^"])*"       # match double-quoted string with any character except unescaped double-quote
        )
        (?:\s*\+\s*)?             # match "+" with possible whitespace, for str concat
      )+                          # match multiple because we supports concatenating strs
    )\s*                          # end capturing of plural string argument
    (?:,\s*'.self::LOCALE_JS_OBJECT.'\s*          # optionally capture string args
      (?:,\s*'.self::LOCALE_JS_OBJECT_CONTEXT.'\s*)?  # optionally capture context
    )?
    [,\)]
    ~sx', $file, $plural_matches);

        $matches = [];

        // Add strings from Drupal.t().
        foreach ($t_matches[1] as $key => $string) {
            $matches[] = [
                'source' => $this->stripQuotes($string),
                'context' => $this->stripQuotes($t_matches[2][$key]),
            ];
        }

        // Add string from Drupal.formatPlural().
        foreach ($plural_matches[1] as $key => $string) {
            $matches[] = [
                'source' => $this->stripQuotes($string).PoItem::DELIMITER.$this->stripQuotes($plural_matches[2][$key]),
                'context' => $this->stripQuotes($plural_matches[3][$key]),
            ];
        }

        return $matches;
    }

    /**
     * Lifted from _locale_strip_quotes().
     */
    private function stripQuotes(string $string): string
    {
        return implode('', preg_split('~(?<!\\\\)[\'"]\s*\+\s*[\'"]~s', substr($string, 1, -1)));
    }
}
