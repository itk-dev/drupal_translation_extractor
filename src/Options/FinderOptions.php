<?php

namespace Drupal\drupal_translation_extractor\Options;

final class FinderOptions
{
    private static bool $ignoreVCSIgnored = true;

    public static function getIgnoreVCSIgnored(): bool
    {
        return self::$ignoreVCSIgnored;
    }

    public static function setIgnoreVCSIgnored(bool $ignoreVCSIgnored): void
    {
        self::$ignoreVCSIgnored = $ignoreVCSIgnored;
    }

    public static function reset(): void
    {
        self::$ignoreVCSIgnored = true;
    }
}
