<?php

/**
 * @PluginBase(
 *   label = @Translation("Plugin label", context="my_plugin"),
 *   description = @Translation("Plugin description"),
 *   text = @Translation("Plugin text", arguments={}, context="my_plugin"),
 *   text1 = @Translation("Plugin text (1)", context="my_plugin", arguments={}),
 *   text2 = @Translation("Plugin text (2)", context =  "my_plugin_text", arguments={}),
 * )
 *
 * @see https://git.drupalcode.org/project/drupal/-/blob/main/core/lib/Drupal/Core/Annotation/Translation.php
 */
class MyPlugin
{
}
