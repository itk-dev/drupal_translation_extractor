const messages = [
  Drupal.t('ignored Drupal.t'),
  Drupal.t('ignored Drupal.t with context', {}, {context: 'the context'}),

  Drupal.formatPlural(0, 'ignored 1 Drupal.formatPlural call', 'ignored @count Drupal.formatPlural calls'),
  Drupal.formatPlural(0, 'ignored 1 Drupal.formatPlural call with context', 'ignored @count Drupal.formatPlural calls with context', {}, {context: 'the context'}),
]
