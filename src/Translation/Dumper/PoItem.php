<?php

namespace Drupal\drupal_translation_extractor\Translation\Dumper;

class PoItem extends \Drupal\Component\Gettext\PoItem
{
    public const string NO_CONTEXT = '__no_context__';

    private bool $fuzzy = false;

    public function setFuzzy(bool $fuzzy = true): self
    {
        $this->fuzzy = $fuzzy;

        return $this;
    }

    public function __toString()
    {
        $string = parent::__toString();

        if ($this->fuzzy) {
            // https://www.gnu.org/savannah-checkouts/gnu/gettext/manual/gettext.html#Fuzzy-Entries
            $string = '#, fuzzy'."\n".$string;
        }

        return $string;
    }

    public static function joinStrings(array $strings, string $prefix = ''): string
    {
        return implode(self::DELIMITER, array_map(fn (string $string) => $prefix.$string, $strings));
    }

    /**
     * @return string[]
     */
    public static function splitStrings(string $string, int $numberOfStrings = 1): array
    {
        $strings = explode(self::DELIMITER, $string);
        for ($i = 0; $i < $numberOfStrings; ++$i) {
            $strings[$i] ??= '';
        }

        return $strings;
    }

    public static function formatContext(?string $domain): string
    {
        return (null === $domain || self::NO_CONTEXT === $domain) ? '' : $domain;
    }
}
