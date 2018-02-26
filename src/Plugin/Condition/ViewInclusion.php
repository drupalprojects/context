<?php
namespace Drupal\context\Plugin\Condition;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Provides a 'Views' condition.
 *
 * @Condition(
 *   id = "view_inclusion",
 *   label = @Translation("View inclusion"),
 *   context = {
 *     "view" = @ContextDefinition("entity:view", label = @Translation("View")),
 *   }
 * )
 */
class ViewInclusion extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  private $entityTypeManager;

  private $currentRouteMatch;

  /**
   * View constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   * @param \Drupal\Core\Routing\CurrentRouteMatch
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $views = $this->entityTypeManager->getStorage('view')->loadMultiple();
    $options = [];
    foreach ($views as $key => $view) {
      foreach ($view->get('display') as $display) {
        if ($display['display_plugin'] === 'page') {
          $viewRoute = 'view-' . $key . '-' . $display['id'];
          $options[$viewRoute] = $view->label() . ' - ' . $display['display_title'];
        }
      }
    }

    $configuration = $this->getConfiguration();

    $form['views_pages'] = [
      '#title' => $this->t('Views pages'),
      '#type' => 'select',
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => isset($configuration['view_inclusion']) && !empty($configuration['view_inclusion']) ? array_keys($configuration['view_inclusion']) : [],
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['view_inclusion'] = array_filter($form_state->getValue('views_pages'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function summary() {
    return t('Select views pages');
  }

  /**
   * @return bool
   */
  public function evaluate() {
    $route = str_replace('.', '-', $this->currentRouteMatch->getRouteName());
    $configuration = $this->getConfiguration();

    if (array_key_exists('view_inclusion', $configuration) && !empty($configuration['view_inclusion'])) {
      return in_array($route, $configuration['view_inclusion']);
    }

    return FALSE;
  }
}
