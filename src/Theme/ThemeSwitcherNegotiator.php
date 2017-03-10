<?php
/**
 * @file
 * Contains \Drupal\context\Theme\ThemeSwitcherNegotiator.
 */

namespace Drupal\context\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

class ThemeSwitcherNegotiator implements ThemeNegotiatorInterface {

  /**
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $context_manager = \Drupal::service('context.manager');
    foreach($context_manager->getActiveReactions('theme') as $theme_reaction) {
      $configuration = $theme_reaction->getConfiguration();
      $this->theme = $configuration['theme'];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->theme;
  }
}
