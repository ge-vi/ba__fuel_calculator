<?php

namespace Drupal\fuel_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\MessageCommand;

/**
 * Provides a fuel_calculator form.
 */
class FuelCalculatorForm extends FormBase {

  protected $request;
  protected $loggerFactory;
  protected $currentUser;

  /**
   *
   */
  public function __construct(Request $request, LoggerChannelFactoryInterface $loggerFactory, AccountProxyInterface $currentUser) {
    $this->request = $request;
    $this->loggerFactory = $loggerFactory;
    $this->currentUser = $currentUser;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('logger.factory'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fuel_calculator_fuel_calculator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the query parameters from the URL.
    $query = $this->request->query;
    $form['distance'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#min' => 0,
      '#title' => $this->t('Distance travelled'),
      '#description' => $this->t('km'),
      '#default_value' => $query->get('distance') ?: $this->config('fuel_calculator.settings')->get('distance'),
      '#required' => TRUE,
    ];
    $form['fuel_consumption'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#min' => 0,
      '#title' => $this->t('Fuel consumption'),
      '#description' => $this->t('l/100km'),
      '#default_value' => $query->get('fuel_consumption') ?: $this->config('fuel_calculator.settings')->get('fuel_consumption'),
      '#required' => TRUE,
    ];
    $form['price'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#min' => 0,
      '#title' => $this->t('Price per litre'),
      '#description' => $this->t('EUR'),
      '#default_value' => $query->get('price') ?: $this->config('fuel_calculator.settings')->get('price'),
      '#required' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['reset_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Reset'),
      '#attributes' => [
        'id' => 'reset-button',
      ],
      '#ajax' => [
        'callback' => '::resetFormStateAjaxCallback',
      ],
    ];
    // Submits the form to calculate values.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
      '#ajax' => [
        'callback' => '::calculateFormAjaxCallback',
        'event' => 'click',
        'wrapper' => 'fuel-calculator-form',
        'effect' => 'fade',
      ],
    ];
    // Add a wrapper for the result area.
    $form['result_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'fuel-calculator-result-wrapper'],
    ];

    // Prepare the result markup.
    $result_markup = '<div class="fuel-calculation-result">';
    $result_markup .= '<p>' . $this->t('Fuel spent: @fuel_spent liters', ['@fuel_spent' => '0.0']) . '</p>';
    $result_markup .= '<p>' . $this->t('Fuel cost: @fuel_cost EUR', ['@fuel_cost' => '0.0']) . '</p>';
    $result_markup .= '</div>';

    // Initially set the result markup in the form.
    $form['result_wrapper']['result'] = [
      '#type' => 'markup',
      '#markup' => $result_markup,
    ];

    $form['#prefix'] = '<div id="fuel-calculator-form">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'fuel_calculator/fuel_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $distance = $form_state->getValue('distance');
    $fuel_consumption = $form_state->getValue('fuel_consumption');
    $price_per_liter = $form_state->getValue('price');
    // Calculate fuel spent and fuel cost using FuelCalculate service (as before).
    $fuel_calculator = \Drupal::service('fuel_calculator.calculate');
    [$fuel_spent, $fuel_cost] = $fuel_calculator->calculate($distance, $fuel_consumption, $price_per_liter);

    // Round the results to one decimal place.
    $fuel_spent = number_format($fuel_spent, 1);
    $fuel_cost = number_format($fuel_cost, 1);
    $log_message = json_encode([
      'ip_address' => $this->request->getClientIp(),
      'username' => $this->currentUser->getAccountName(),
      'distance' => $distance,
      'fuel_consumption' => $fuel_consumption,
      'price_per_liter' => $price_per_liter,
      'fuel_spent' => $fuel_spent,
      'fuel_cost' => $fuel_cost,

    ]);

    // Log the message with an appropriate severity level.
    $this->loggerFactory->get('fuel_calculator')->notice($log_message);

    // Prepare the result markup.
    $result_markup = '<div class="fuel-calculation-result">';
    $result_markup .= '<p>' . $this->t('Fuel spent: @fuel_spent liters', ['@fuel_spent' => $fuel_spent]) . '</p>';
    $result_markup .= '<p>' . $this->t('Fuel cost: @fuel_cost EUR', ['@fuel_cost' => $fuel_cost]) . '</p>';
    $result_markup .= '</div>';

    // Update the result markup in the form.
    $form['result_wrapper']['result']['#markup'] = $result_markup;
    $form_state->setRebuild(TRUE);
  }

  /**
   * AJAX callback for the Calculate button.
   */
  public function calculateFormAjaxCallback(array &$form, FormStateInterface $form_state) {
    // Prepare an empty AJAX response object.
    $response = new AjaxResponse();
    // If there are validation errors, update the form with the error messages.
    if ($form_state->hasAnyErrors()) {
      $errors = $form_state->getErrors();
      $form_state->clearErrors();
      $this->messenger()->deleteByType('error');
      foreach ($errors as $error) {
        $response->addCommand(new MessageCommand($error, NULL, ['type' => 'error']));
      }
    }
    else {
      $this->submitForm($form, $form_state);
      $response->addCommand(new ReplaceCommand('#fuel-calculator-result-wrapper', $form['result_wrapper']));
    }
    return $response;
  }

  /**
   * AJAX callback for the resetting form state.
   */
  public function resetFormStateAjaxCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
