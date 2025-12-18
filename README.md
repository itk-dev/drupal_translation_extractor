# Translation extractor

Extracts translations from PHP files and Twig templates.

This Drupal translation extractor stands on the shoulders of giants:

* <https://www.drupal.org/project/potx>
* <https://www.drupal.org/project/translation_extractor>
* <https://symfony.com/doc/current/translation.html#extracting-translation-contents-and-updating-catalogs-automatically>

## Installation

``` shell
composer require --dev itk-dev/drupal_translation_extractor:^1.0
drush pm:install drupal_translation_extractor
```

## Use

The main entrypoint is the `drupal_translation_extractor:translation:extract` Drush command. This command is basically
Symfony's [`translation:extract` console
command](https://symfony.com/doc/current/translation.html#extracting-translation-contents-and-updating-catalogs-automatically)
with a few changes and additions outlined below.

<details>
<summary>Changes from the Symfony command</summary>

The `bundle` argument doesn't make sense and has been removed.

The `--clean` option is a [NOP](https://en.wikipedia.org/wiki/NOP_(code)) since it doesn't make sense for our use. We
always write only existing translations.

</details>

A new argument has been added:

`source` The source path to extract translations from. For convenience, `module:«name»` and `theme:«name»` can be used
in the value and will be expanded to the full path to the module and/or theme respectively, i.e.
`module:my_custom_module` will be expanded to `web/modules/custom/my_custom_module`, say.

A new option has been added:

`--output` The output path. The value can use these placeholders:

| Name            | Value                                                     |
|-----------------|-----------------------------------------------------------|
| `%locale`       | The locale being extracted                                |
| `%module`       | The module name if specified using `module:…` in `source` |
| `%source_dir`   | The directory of `%source`                                |
| `%source`       | The expanded value of `source`                            |
| `%theme`        | The theme name if specified using `theme:…` in `source`   |
| `%language`[^1] | Alias for `%locale`                                       |
| `%project` [^1] | Alias for either `%module` or `%theme` (whichever is set) |

[^1]: Matching placeholders used the Locale module (cf.
    [locale.api.php](https://git.drupalcode.org/project/drupal/-/blob/11.x/core/modules/locale/locale.api.php)).

### Example

Running

``` shell
drush drupal_translation_extractor:translation:extract da --dump-messages --force module:my_modules --output=%source/translation/%module.%locale.po
```

will find translations in all PHP and Twig files in the `web/modules/custom/my_module` directory and write the result to
`web/modules/custom/my_module/translation/my_module.da.po`.

> [!NOTE]
> Much of the code in this module is ~stolen from~based on Symfony components and therefore we do not use Drupal coding
> standards.
