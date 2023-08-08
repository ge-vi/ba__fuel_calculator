<?php

namespace Drupal\fuel_calculator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure fuel_calculator settings for this site.
 */
final class FuelDefaultSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fuel_calculator_fuel_default_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fuel_calculator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['distance'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Distance travelled in km'),
      '#description' => $this->t('Enter distance travelled in km.'),
      '#default_value' => $this->config('fuel_calculator.settings')->get('distance'),
      '#required' => TRUE,
    ];
    $form['fuel_consumption'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Fuel consumption'),
      '#description' => $this->t('Enter fuel consumption in litres per 100km.'),
      '#default_value' => $this->config('fuel_calculator.settings')->get('fuel_consumption'),
      '#required' => TRUE,
    ];
    $form['price'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Price per litre'),
      '#description' => $this->t('Enter fuel price per litre in EUR.'),
      '#default_value' => $this->config('fuel_calculator.settings')->get('price'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fuel_calculator.settings')
      ->set('distance', $form_state->getValue('distance'))
      ->set('fuel_consumption', $form_state->getValue('fuel_consumption'))
      ->set('price', $form_state->getValue('price'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
