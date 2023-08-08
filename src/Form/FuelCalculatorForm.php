<?php

namespace Drupal\fuel_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Url;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;



/**
 * Provides a fuel_calculator form.
 */
class FuelCalculatorForm extends FormBase
{

  protected $request;
  protected $loggerFactory;
  protected $currentUser;

  public function __construct(Request $request, LoggerChannelFactoryInterface $loggerFactory, AccountProxyInterface $currentUser)
  {
    $this->request = $request;
    $this->loggerFactory = $loggerFactory;
    $this->currentUser = $currentUser;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('logger.factory'),
      $container->get('current_user'),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'fuel_calculator_fuel_calculator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // Get the query parameters from the URL.
    $query = $this->request->query;
    $form['distance'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Distance travelled in km'),
      '#description' => $this->t('Enter distance travelled in km.'),
      '#default_value' => $query->get('distance') ?: $this->config('fuel_calculator.settings')->get('distance'),
      '#required' => TRUE,
    ];
    $form['fuel_consumption'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Fuel consumption'),
      '#description' => $this->t('Enter fuel consumption in litres per 100km.'),
      '#default_value' => $query->get('fuel_consumption') ?: $this->config('fuel_calculator.settings')->get('fuel_consumption'),
      '#required' => TRUE,
    ];
    $form['price'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Price per litre'),
      '#description' => $this->t('Enter fuel price per litre in EUR.'),
      '#default_value' => $query->get('price') ?: $this->config('fuel_calculator.settings')->get('price'),
      '#required' => TRUE,
    ];

    // Submits the form to calculate values.
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Calculate'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $distance = $form_state->getValue('distance');
    $fuel_consumption = $form_state->getValue('fuel_consumption');
    $price = $form_state->getValue('price');

    // Validate distance field.
    if ($distance <= 0) {
      $form_state->setErrorByName('distance', $this->t('Distance should be a positive number.'));
    }

    // Validate fuel consumption field.
    if ($fuel_consumption <= 0) {
      $form_state->setErrorByName('fuel_consumption', $this->t('Fuel consumption should be a positive number.'));
    }

    // Validate price field.
    if ($price <= 0) {
      $form_state->setErrorByName('price', $this->t('Price per litre should be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $distance = $form_state->getValue('distance');
    $fuel_consumption = $form_state->getValue('fuel_consumption');
    $price_per_liter = $form_state->getValue('price');
    // Calculate fuel spent and fuel cost using FuelCalculate service (as before).
    $fuel_calculator = \Drupal::service('fuel_calculator.calculate');
    list($fuel_spent, $fuel_cost) = $fuel_calculator->calculate($distance, $fuel_consumption, $price_per_liter);

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
  }
}
