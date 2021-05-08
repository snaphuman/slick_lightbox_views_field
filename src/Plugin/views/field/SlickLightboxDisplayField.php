<?php

/**
 * @file
 * Definition of Drupal\slick_lightbox_views_field\Plugin\views\field\SlickLightboxDisplayField.
 */

namespace Drupal\slick_lightbox_views_field\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("slick_lightbox_display_field")
 */
class SlickLightboxDisplayField extends FieldPluginBase {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;
  protected $handlers;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->currentDisplay = $view->current_display;
    $this->handlers = $display->handlers;
    $this->view = $view;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options.
   *
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['lightbox_inline'] = array('default' => '');
    $options['lightbox_inline']['selected_field'] = array('default' => '');
    $options['lightbox_inline']['link_text'] = array('default' => '');
    $options['lightbox_inline']['link_icon'] = array('default' => '');
    $options['lightbox_inline']['link_class'] = array('default' => '');

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $fields = !empty($this->handlers['field']) ? $this->handlers['field'] : [];

    $options = [];
    foreach ($fields as $key => $field) {
      if ($field->options['exclude']) {
        $options[$key] = $field->options['id'];
      }
    }
    $form['lightbox_inline'] = [
      '#type' => 'fieldset',
      '#title' => t('Show inline field'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['lightbox_inline']['selected_field'] = [
      '#title' => $this->t('Which field should be shown as colorbox?'),
      '#type' => 'select',
      '#default_value' => $this->options['lightbox_inline']['selected_field'],
      '#options' => $options,
    ];
    $form['lightbox_inline']['link_text'] = [
      '#title' => $this->t('Link text to display'),
      '#type' => 'textfield',
      '#default_value' => $this->options['lightbox_inline']['link_text'],
    ];
    $form['lightbox_inline']['link_icon'] = [
      '#title' => $this->t('Link icon to display'),
      '#type' => 'textfield',
      '#default_value' => $this->options['lightbox_inline']['link_icon'],
    ];
    $form['lightbox_inline']['link_class'] = [
      '#title' => $this->t('Link custom class'),
      '#type' => 'textfield',
      '#default_value' => $this->options['lightbox_inline']['link_class'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {

    $slick = \Drupal::service('slick.manager');
    $formatter = \Drupal::service('slick.formatter');

    $excluded = $this->options['lightbox_inline']['selected_field'];
    $field_id = $this->handlers['field'][$excluded]->field;
    $entity = $values->_entity;
    $field = $entity->get($field_id);

    $options['nid'] = $entity->id();
    $options['link_text'] = $this->options['lightbox_inline']['link_text'];
    $options['link_class'] = $this->options['lightbox_inline']['link_class'];
    $options['link_icon'] = $this->options['lightbox_inline']['link_icon'];

    if ($this->typeOf($field) === 'EntityReferenceFieldItemList') {

      //kint("entra slick");
      $slickBuild = [];
      $referenced = $field->referencedEntities();
      $options['type'] = "reference";

      foreach ($referenced as $item) {
        if ($this->typeOf($item) === 'Media') {

          // Blazy options for each slide element.
          $element = [
            'item' => $item->get('field_media_image')->get(0),
            'settings' => [
              'media_switch' => 'slick_lightbox',
              'lightbox' => 'slick_lightbox',
            ],
          ];

          $formatted = $formatter->getBlazy($element);

          // Formatted element is passed to slick array.
          $slickBuild['items'][] = [
            'slide' => $formatted,
            'caption' => 'To be implemented'
          ];
        }
      }
      // Slick options
      // $build['settings']['media_switch'] = 'slick_lightbox';
      $slickBbuild['options'] = [
        'arrows' => TRUE,
      ];

      // Variables that are passed to inline template.
      $slick = $slick->build($slickBuild);

      $output = $this->slickOutput($options, $slick);

    } elseif ($this->typeOf($field) === 'FieldItemList') {
      //kint("entra colorbox");

      $view = $field->view();
      $options['type'] = 'field';

      $output = $this->colorboxOutput($options, $view);
    }

    return $output;
  }

  private function slickOutput($options, $content) {

    return [
      '#type' => 'inline_template',
      '#attached' => [
        'library' => [
          'slick_lightbox_views_field/main',
          'slick_lightbox/load',
          'slick_lightbox/slick-carousel',
          'slick/slick.theme',
        ],
      ],
      '#template' => '
            <a class="slick-lightbox-btn {{ link_class }}"
               data-id="{{ id }}" data-type="{{ type }}" href="#">
                {% if link_icon %}
                  <i class="{{ link_icon }}"></i>
                {% endif %}
                {{ link_text }}
            </a>
            <div id="slick-lightbox-{{ type }}-{{ id }}" class="hidden">
                {{ items }}
            </div>
        ',
      '#context' => [
        'items' => $content,
        'id' => $options['nid'],
        'link_text' => $options['link_text'],
        'link_class' => $options['link_class'],
        'link_icon' => $options['link_icon'],
        'type' => $options['type'],
      ],
    ];
  }

  private function colorboxOutput($options, $content) {

    return [
      '#type' => 'inline_template',
      '#attached' => [
        'library' => [
          'slick_lightbox_views_field/main',
        ],
      ],
      '#template' => '
            <a data-colorbox-inline="#colorbox-{{ type }}-{{ id }}" class="{{ link_class }}">
                {% if link_icon %}
                  <i class="{{ link_icon }}"></i>
                {% endif %}
                {{ link_text }}
            </a>
            <div id="colorbox-{{ type }}-{{ id }}" class="hidden">
                {{ items }}
            </div>
        ',
      '#context' => [
        'items' => $content,
        'id' => $options['nid'],
        'link_text' => $options['link_text'],
        'link_class' => $options['link_class'],
        'link_icon' => $options['link_icon'],
        'type' => $options['type'],
      ],
    ];
  }

  private function typeOf($object) {

    $type = gettype($object);
    if ($type !== "object") {
      return NULL;
    }

    $class_path = get_class($object);
    return end(explode('\\', $class_path));
  }

}
