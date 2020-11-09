<?php

namespace Drupal\multistep_register\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\multistep_register\Ajax\RegisterCommand;

/**
 * Class MultistepForm.
 */
class MultistepForm extends FormBase {

  /**
   * Define newUserFields.
   *
   * @var array
   */
  protected $newUserFields;

  /**
   * Define setNewUserFields.
   */
  public function setNewUserFields($values) {
    $this->newUserFields = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multistep_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $configFields = \Drupal::config("multistep_register.config")->get('fields');

    $storage = $form_state->getStorage();
    $values = $form_state->getValues();
    $step = isset($storage['step']) ? $storage['step'] : 1;
    $form_state->set('step', $step);

    $form['#prefix'] = '<div id="ajax_multistep_form">';
    $form['#suffix'] = '</div>';

    $counts = $this->countFieldsforSteps($configFields, $step);
    $form_state->set('counts', $counts);
    $fname_default_value = $lname_default_value = '';
    $form["step_{$step}"] = [
      '#type' => 'fieldset',
      '#title' => "Step {$step}",
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    for ($i = 0; $i < $counts; $i++) {
      $defaults_values[$i] = "";
      $extraField = "step_{$step}_data_{$i}";
      if (isset($values["step_{$step}"])) {
        $defaults_values[$i] = $values[$configFields[$extraField]['machine_name']];
      }
      elseif (isset($storage["step_{$step}"])) {
        $defaults_values[$i] = $storage["step_{$step}"][$configFields[$extraField]['machine_name']];
      }
      $form["step_{$step}"][] = $this->getFormField($configFields[$extraField], $defaults_values[$i]);
    }

    $form['buttons'] = [
      '#type' => 'container',
    ];
    if ($step !== 1) {
      $form['buttons']['back'] = [
        '#type' => 'submit',
        '#value' => t('Back'),
        '#limit_validation_errors' => [],
        '#submit' => [],
        '#ajax' => [
          'wrapper' => 'ajax_multistep_form',
          'callback' => '::multistepFormAjaxCallback',
        ],
      ];
    }

    if ($step !== 2) {
      $form['buttons']['next'] = [
        '#type' => 'submit',
        '#value' => t('Next'),
        '#ajax' => [
          'wrapper' => 'ajax_multistep_form',
          'callback' => '::multistepFormAjaxCallback',
        ],
      ];
    }
    else {
      $form['buttons']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit'),
        '#ajax' => [
          'wrapper' => 'ajax_multistep_form',
          'callback' => '::submitAjaxCallback',
        ],
      ];
    }

    $form['#attached']['library'] = 'multistep_register/multistep_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldsforSteps($fields, $step) {
    $count = 0;
    foreach ($fields as $key => $value) {
      if (strpos($key, 'step_' . $step) !== FALSE) {
        $count++;
      }
    }
    return $count;
  }

  /**
   * Return the field structure.
   */
  public function getFormField($field, $default_value) {
    switch ($field['type']) {
      case 'date':
        $formField[$field['machine_name']] = [
          '#type' => $field['type'],
          '#required' => (bool) $field['required'],
          '#title' => $field['label'],
          '#format' => 'd/m/Y',
          '#default_value' => $default_value,
        ];
        break;

      case 'select':
        $vid = $field['taxonomy_vocabulary'];
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
        foreach ($terms as $term) {
          $options[] = [
            $term->tid => $term->name,
          ];
        }
        $formField[$field['machine_name']] = [
          '#type' => $field['type'],
          '#required' => (bool) $field['required'],
          '#options' => $options,
          '#title' => $field['label'],
          '#default_value' => $default_value,
        ];
        break;

      default:
        $formField[$field['machine_name']] = [
          '#type' => $field['type'],
          '#required' => (bool) $field['required'],
          '#title' => $field['label'],
          '#default_value' => $default_value,
        ];
        break;
    }
    return $formField;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $values = $form_state->getValues();
    $keys = array_keys($values);
    $step = $newStep = isset($storage['step']) ? $storage['step'] : 1;

    for ($i = 0; $i < $storage['counts']; $i++) {
      if (strtolower($values['op']) != 'back') {
        $storage["step_{$step}"][$keys[$i]] = $values[$keys[$i]];
      }
    }
    if (isset($values['next']) && $values['op'] == $values['next']) {
      $newStep++;
    }
    if (isset($values['back']) && $values['op'] == $values['back']) {
      $newStep--;
    }
    $storage['step'] = $newStep;
    $form_state->setStorage($storage);

    if (isset($values['back']) && $values['op'] == $values['submit']) {
      for ($i = 1; $i <= $storage["step"]; $i++) {
        foreach ($storage["step_{$i}"] as $key => $value) {
          $fields[$key] = $value;
        }
      }
      $result = $this->registerUser($fields);
      if ($result) {
        $form_state->cleanValues();
        $storage = [
          'step' => 1,
        ];
        $form_state->setStorage($storage);
      }
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Define registerUser.
   */
  private function registerUser($fields) {

    $username = $this->getUserName($fields);

    $baseFields = [
      'name' => $username,
      'pass' => $username,
      'status' => 1,
    ];

    $registerFields = array_merge($baseFields, $fields);

    $user = User::create($registerFields);
    if ($user->save()) {
      $this->setNewUserFields($registerFields);
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

  /**
   * Define username for create account.
   */
  private function getUserName($fields, $aux = 0) {

    $fname = isset($fields['first_name']) ? $fields['first_name'] : 'dummy';
    $lname = isset($fields['last_name']) ? $fields['last_name'] : 'user';
    $username = strtolower($fname . '.' . $lname);
    $username = ($aux != 0) ? ($username . '_' . $aux) : $username;

    $ids = \Drupal::entityQuery('user')
      ->condition('name', $username)
      ->range(0, 1)
      ->execute();

    if (!empty($ids)) {
      $aux++;
      $username = $this->getUserName($fields, $aux);
    }

    return $username;

  }

  /**
   * Ajax multistep form callback.
   */
  public function multistepFormAjaxCallback($form, &$form_state) {
    return $form;
  }

  /**
   * Ajax multistep form callback.
   */
  public function submitAjaxCallback($form, &$form_state) {
    $fields = $this->newUserFields;
    $response = new AjaxResponse();
    $response->addCommand(new RegisterCommand($fields));
    return $response;
  }

}
