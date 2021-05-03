<?php

/**
 * Implements hook_views_data_alter().
 */
function slick_lightbox_views_field_views_data_alter(array &$data) {

  $data['node']['slick_lightbox_display_field'] = array(
    'title' => t('Slick Lightbox display field'),
    'field' => array(
      'title' => t('Slick lightbox display field'),
      'help' => t('Display field or custom content as slick lightbox'),
      'id' => 'slick_lightbox_display_field',
    ),
  );
}
