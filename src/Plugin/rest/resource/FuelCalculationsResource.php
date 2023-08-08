<?php

namespace Drupal\fuel_calculator\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fuel_calculator\FuelCalculate;

/**
 * Represents Fuel calculations records as resources.
 *
 * @RestResource (
 *   id = "fuel_calculator_fuel_calculations",
 *   label = @Translation("Fuel calculations"),
 *   uri_paths = {
 *     "create" = "/api/fuel-calculator-fuel-calculations"
 *   }
 * )
 */
class FuelCalculationsResource extends ResourceBase {

  /**
   * The key-value storage.
   */
  private readonly KeyValueStoreInterface $storage;

  /**
   * The fuel calculator service.
   *
   * @var \Drupal\fuel_calculator\FuelCalculatorService
   */
  private $fuelCalculatorService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    KeyValueFactoryInterface $keyValueFactory,
    FuelCalculate $fuelCalculatorService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $keyValueFactory, $fuelCalculatorService);
    $this->storage = $keyValueFactory->get('fuel_calculator_fuel_calculations');
    $this->fuelCalculatorService = $fuelCalculatorService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('keyvalue'),
      $container->get('fuel_calculator.calculate')
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   */
  public function post($data) {
    try {
      $distance = $data[0]['distance'];
      $fuel_consumption = $data[0]['fuel_consumption'];
      $price_per_liter = $data[0]['price'];
      if (!is_numeric($distance) || (float) $distance <= 0) {
        // Return the error as a JSON response.
        $response = [
          'error' => 'Invalid input',
          'message' => 'The distance input provided is incorrect or missing.',
        ];
        return new ResourceResponse($response, 400);
      }
      elseif (!is_numeric($fuel_consumption) || (float) $fuel_consumption <= 0) {
        // Return the error as a JSON response.
        $response = [
          'error' => 'Invalid input',
          'message' => 'The fuel consumption input provided is incorrect or missing.',
        ];
        return new ResourceResponse($response, 400);
      }
      elseif (!is_numeric($price_per_liter) || (float) $price_per_liter <= 0) {
        // Return the error as a JSON response.
        $response = [
          'error' => 'Invalid input',
          'message' => 'The price input provided is incorrect or missing.',
        ];
        return new ResourceResponse($response, 400);
      }
      else {
        // Calculate fuel spent and fuel cost using FuelCalculate service (as before).
        [$fuel_spent, $fuel_cost] = $this->fuelCalculatorService->calculate($distance, $fuel_consumption, $price_per_liter);

        // Round the results to one decimal place.
        $fuel_spent = round($fuel_spent, 1);
        $fuel_cost = round($fuel_cost, 1);
        $response = [
          'fuel_spent' => $fuel_spent,
          'fuel_cost' => $fuel_cost,
        ];

        // Return the results as a JSON response.
        return new ResourceResponse($response);
      }
    }
    catch (\Exception $e) {
      return new ResourceResponse($e->getMessage(), 401);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function availableMethods() {
    return [
      'POST',
    ];
  }

}
