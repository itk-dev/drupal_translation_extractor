# Translation extractor

This Drupal translation extractor stands on the shoulders of giants:

* <https://www.drupal.org/project/potx>
* <https://www.drupal.org/project/translation_extractor>
* <https://symfony.com/doc/current/translation.html#extracting-translation-contents-and-updating-catalogs-automatically>

## Installation

``` shell
composer require --dev drupal/itk_translation_extractor:^1.0
drush pm:install itk_translation_extractor
```

## Use

The main entrypoint is the `itk_translation_extractor:translation:extract` Drush command. This command is basically
Symfony's [`translation:extract` console
command](https://symfony.com/doc/current/translation.html#extracting-translation-contents-and-updating-catalogs-automatically)
with a few changes and additions outlined below.

<details>
<summary>Changes from the Symfony command</summary>

The `bundle` argument doesn't make sense and has been removed.

The `--clean` option is a [NOP](https://en.wikipedia.org/wiki/NOP_(code)) since it doesn't make sense for our use. We
always write only existing translations.

</details>

Two new options have been added:

`--source` The source path to extract translations from. For convenience, `module:«name»` and `theme:«name»` can be used
in the value and will be expanded to the full path to the module and/or theme respectively, i.e. `--source
module:my_custom_module` will be converted to `--source modules/custom/my_custom_module`, say.

`--output` The output path. The value can use these placeholders:

| Name          | Value                                                       |
|---------------|-------------------------------------------------------------|
| `%locale`     | The locale being extracted                                  |
| `%module`     | The module name if specified using `module:…` in `--source` |
| `%source_dir` | The directory of `%source`                                  |
| `%source`     | The expanded value of `--source`                            |
| `%theme`      | The theme name if specified using `theme:…` in `--source`   |

### Example

Running

``` shell
drush itk_translation_extractor:translation:extract da --dump-messages --force --source module:my_modules --output=%source/translation/%module.%locale.po
```

will find translations in all PHP and Twig files in the `web/modules/custom/my_module` directory and write the result to
`web/modules/custom/my_module/translation/my_module.da.po`.

> [!NOTE]
> Much of the code in this module is ~stolen from~based on Symfony components and therefore we do not use Drupal coding
> standards.
