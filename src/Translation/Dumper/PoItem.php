<?php

namespace Drupal\itk_translation_extractor\Translation\Dumper;

class PoItem extends \Drupal\Component\Gettext\PoItem
{
    public const string NO_CONTEXT = '__no_context__';

    public static function joinStrings(string ...$strings): string
    {
        return implode(self::DELIMITER, [...$strings]);
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
