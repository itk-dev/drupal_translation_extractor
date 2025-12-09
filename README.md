# Translation extractor

``` shell
composer require --dev drupal/itk_translation_extractor:^1.0
drush pm:install itk_translation_extractor
```

``` shell
drush itk_translation_extractor:translation:extract da --dump-messages --force --output-name=modules/custom/my_module/my_module.da.po --module=my_module
```

> [!NOTE]
> This module does not use Drupal coding standards.
