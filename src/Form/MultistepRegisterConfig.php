<?php

namespace Drupal\multistep_register\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MultistepRegisterConfig.
 */
class MultistepRegisterConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'multistep_register.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multistep_register_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('multistep_register.config');

    $vocabularyStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');
    $vocabularyList = $vocabularyStorage->loadMultiple();
    $options[0] = '';
    foreach ($vocabularyList as $key => $value) {
      $options[$key] = $value->get('name');
    }

    $types = [
      'textfield' => 'textfield',
      'date' => 'Date',
      'select' => 'taxonomy Vocabulary',
      'number' => 'Number',
    ];

    $form = [
      '#prefix' => '<div id="container-fields-wrapper">',
      '#suffix' => '</div>',
    ];

    $fields = $config->get('fields');

    for ($i = 1; $i <= 2; $i++) {
      $form['steps'][$i] = [
        '#type' => 'details',
        '#title' => 'Step ' . $i,
        '#open' => TRUE,
      ];

      $form['steps'][$i]['fields'] = [
        '#type' => 'table',
        '#header' => [
          t('Machine name'),
          t('Label'),
          t('Type'),
          t('special'),
          t('required'),
        ],
        '#empty' => t('There are no items.'),
      ];

      $remove = ($form_state->get('remove' . $i) != NULL) ? $form_state->get('remove' . $i) : FALSE;
      $count = ($form_state->get('count' . $i) != NULL) ? $form_state->get('count' . $i) : 0;
      $fields = $config->get('fields');
      $fields = (isset($fields) && !empty($fields)) ? $fields : [];

      if (isset($fields) && is_array($fields) && (count($fields) < $count)) {
        $fields["step_{$i}_data_{$count}"] = [];
        $form_state->set('count' . $i, $count);
      }
      else {
        if ($remove) {
          $count = 0;
          $form_state->set('count' . $i, 0);
        }
        elseif ($count != NULL) {
          $form_state->set('count' . $i, $count);
          $count = $count;
        }
        else {
          $count = $this->countFieldsforSteps($fields, $i);
          $form_state->set('count' . $i, $count);
        }
      }

      for ($j = 0; $j < $count; $j++) {

        $aditionalId = "step_{$i}_data_{$j}";

        $form['steps'][$i]['fields'][$aditionalId]['machine_name'] = [
          '#type' => 'textfield',
          "#required" => TRUE,
          '#default_value' => isset($fields[$aditionalId]["machine_name"]) ? $fields[$aditionalId]["machine_name"] : '',
        ];

        $form['steps'][$i]['fields'][$aditionalId]['label'] = [
          '#type' => 'textfield',
          '#default_value' => isset($fields[$aditionalId]["label"]) ? $fields[$aditionalId]["label"] : '',
        ];

        $form['steps'][$i]['fields'][$aditionalId]['type'] = [
          '#type' => 'select',
          '#options' => $types,
          '#default_value' => isset($fields[$aditionalId]["type"]) ? $fields[$aditionalId]["type"] : '',
        ];

        $form['steps'][$i]['fields'][$aditionalId]['taxonomy_vocabulary'] = [
          '#type' => 'select',
          '#options' => $options,
          '#validated' => TRUE,
          '#default_value' => isset($fields[$aditionalId]["taxonomy_vocabulary"]) ? $fields[$aditionalId]["taxonomy_vocabulary"] : '',
          '#prefix' => '<div id="taxonomy-vocabulary-' . $i . '-' . $j . '">',
          '#suffix' => '</div>',
          '#states' => [
            'visible' => [':input[name="fields[step_' . $i . '_data_' . $j . '][type]"]' => ['value' => 'select']],
          ],
        ];

        $form['steps'][$i]['fields'][$aditionalId]['required'] = [
          '#type' => 'checkbox',
          '#default_value' => isset($fields[$aditionalId]["required"]) ? $fields[$aditionalId]["required"] : '',
        ];

      }

      $form['steps'][$i]['add'] = [
        '#type' => 'submit',
        '#value' => 'Add field step ' . $i,
        '#submit' => [
          [$this, 'addContainerCallback'],
        ],
        '#ajax' => [
          'callback' => [$this, 'addFieldSubmit'],
          'wrapper' => 'container-fields-wrapper',
        ],
      ];

      if ($count > 0) {
        $form['steps'][$i]['remove'] = [
          '#type' => 'submit',
          '#value' => 'Delete field step ' . $i,
          '#limit_validation_errors' => [],
          '#submit' => [
            [$this, 'removeContainerCallback'],
          ],
          '#ajax' => [
            'callback' => [$this, 'addFieldSubmit'],
            'wrapper' => 'container-fields-wrapper',
          ],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function addContainerCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $stepButton = substr($values['op'], -1, 1);
    $count = $form_state->get('count' . $stepButton) + 1;
    $form_state->set('count' . $stepButton, $count);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function removeContainerCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $stepButton = substr($values['op'], -1, 1);
    $count = $form_state->get('count' . $stepButton);
    if ($count > 0) {
      $count = $count - 1;
      $form_state->set('count' . $stepButton, $count);
      if ($count == 0) {
        $form_state->set('remove' . $stepButton, TRUE);
      }
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldSubmit(array &$form, FormStateInterface $form_state) {
    return $form;
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Los cambios se han almacenado correctamente.'));
    $fields = $form_state->getValue('fields');
    $this->config('multistep_register.config')
      ->set('fields', $fields)
      ->save();
    $entity = \Drupal::entityTypeManager()->getDefinition('user');

    $fieldDefinitions = multistep_register_entity_base_field_info($entity);
    foreach ($fieldDefinitions as $key => $field) {
      \Drupal::entityDefinitionUpdateManager()
        ->installFieldStorageDefinition($key, 'user', 'user', $field);
    }
    \Drupal::service('entity.definition_update_manager')->updateEntityType($entity);

  }

}
