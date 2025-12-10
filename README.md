# Translation extractor

This Drupal translation extractor stands on the shoulders of giants:

* <https://www.drupal.org/project/potx>
* <https://www.drupal.org/project/translation_extractor>
* <https://symfony.com/doc/current/translation.html#extracting-translation-contents-and-updating-catalogs-automatically>

``` shell
composer require --dev drupal/itk_translation_extractor:^1.0
drush pm:install itk_translation_extractor
```

``` shell
drush itk_translation_extractor:translation:extract da --dump-messages --force --output-name=modules/custom/my_module/my_module.da.po --module=my_module
```

> [!NOTE]
> Much of the code in this module is ~stolen from~based on Symfony components and therefore we do not use Drupal coding
> standards.
