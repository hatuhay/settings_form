<?php

namespace Drupal\settings_form\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sbp_embed_form.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_form.settings');
    $basic = $config->get('basic');
    $javascript = $config->get('javascript');
    $field_names = $config->get('field_names') ? $config->get('field_names') : [];
    $count = count($field_names);
    $form['#tree'] = true;

    $form['basic']['#type'] = 'details';
    $form['basic']['#title'] = $this->t('Basic settings');
    $form['basic']['#open'] = FALSE;

    $form['basic']['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form id code'),
      '#size' => 60,
      '#maxlength' => 128,
      '#default_value' => $basic['code'],
    ];
    $form['basic']['library'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Library URL. Include https.'),
      '#size' => 60,
      '#maxlength' => 128,
      '#default_value' => $basic['library'],
    ];
    $form['basic']['embed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Embed URL. Include https.'),
      '#size' => 60,
      '#maxlength' => 128,
      '#default_value' => $basic['embed'],
    ];

    $form['javascript']['#type'] = 'details';
    $form['javascript']['#title'] = $this->t('Javascript');
    $form['javascript']['#open'] = FALSE;

    $form['javascript']['previous'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Javascript to execute before presets'),
      '#description' => $this->t('Include script tags'),
      '#default_value' => $javascript['previous'],
    ];
    $form['javascript']['post'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Javascript to execute after presets'),
      '#description' => $this->t('Include script tags'),
      '#default_value' => $javascript['post'],
    ];

    $form['field_names']['#prefix'] = '<div id="fields-wrapper">';
    $form['field_names']['#suffix'] = '</div>';
    $form['field_names']['#type'] = 'details';
    $form['field_names']['#title'] = $this->t('Preset form fields');
    $form['field_names']['#open'] = TRUE;

    $num_fields = $form_state->get('num_fields');
    if (empty($num_fields)) {
      $num_fields = $count;
      $form_state->set('num_fields', $count);
    }
 
    for ($i = 0; $i < $num_fields; $i++) {
      $form['field_names'][$i] = [
        '#type' => 'details',
        '#title' => isset($field_names[$i]['drupal_reference_field']) ? $field_names[$i]['drupal_reference_field'] : 'New field ',
        '#tree' => TRUE,
      ];
      $form['field_names'][$i]['field_number'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Field name'),
        '#description' => $this->t('The name of the field in the form'),
        '#maxlength' => 64,
        '#size' => 64,
        '#default_value' => $field_names[$i]['field_number'],
      ];
      $form['field_names'][$i]['drupal_reference_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Drupal reference field'),
        '#description' => $this->t('The drupal referenced field'),
        '#options' => self::getUserFields(),
        '#size' => 1,
        '#default_value' => $field_names[$i]['drupal_reference_field'],
      ];
      $form['field_names'][$i]['actions']['remove_name'][$i] = [
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#submit' => array('::removeCallback'),
          '#limit_validation_errors' => array(),
          '#ajax' => [
              'callback' => '::addmoreCallback',
              'wrapper' => 'fields-wrapper',
          ],
      ];
    }
    $form['field_names']['add_item'] = [
      '#type' => 'submit',
      '#value' => t('Add Another Item'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'fields-wrapper',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax Callback for the form.
   *
   * @param array $form
   *   The form being passed in
   * @param array $form_state
   *   The form state
   * 
   * @return array
   *   The form element we are changing via ajax
   */
  public function addmoreCallback(&$form, FormStateInterface $form_state) {
    return $form['field_names'];
  }

  /**
   * Functionality for our ajax callback.
   *
   * @param array $form
   *   The form being passed in
   * @param array $form_state
   *   The form state, passed by reference so we can modify
   */
  public function addOne(&$form, FormStateInterface $form_state) {
    $num_fields = $form_state->get('num_fields');
    $form_state->set('num_fields', $num_fields+1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $num_fields = $form_state->get('num_fields');
    if ($num_fields > 1) {
      $remove_button = $num_fields - 1;
      $form_state->set('num_fields', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $field_names = $form_state->getValue('field_names');
    unset($field_names['add_item']);
    $this->config('sbp_embed_form.settings')
      ->set('basic', $form_state->getValue('basic'))
      ->set('field_names', $field_names)
      ->set('javascript', $form_state->getValue('javascript'))
      ->save();
  }

}
