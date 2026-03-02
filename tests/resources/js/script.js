const messages = [
  Drupal.t('Drupal.t'),
  Drupal.t('Drupal.t with context', {}, {context: 'the context'}),

  Drupal.formatPlural(0, '1 Drupal.formatPlural call', '@count Drupal.formatPlural calls'),
  Drupal.formatPlural(0, '1 Drupal.formatPlural call with context', '@count Drupal.formatPlural calls with context', {}, {context: 'the context'}),
]
