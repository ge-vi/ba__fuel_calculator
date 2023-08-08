<?php

namespace Drupal\fuel_calculator\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\fuel_calculator\FuelCalculate;

/**
 * Represents Fuel calculations records as resources.
 *
 * @RestResource (
 *   id = "fuel_calculator_fuel_calculations",
 *   label = @Translation("Fuel calculations"),
 *   uri_paths = {
 *     "canonical" = "/api/fuel-calculator-fuel-calculations/{id}",
 *     "create" = "/api/fuel-calculator-fuel-calculations"
 *   }
 * )
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively, you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class FuelCalculationsResource extends ResourceBase
{

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
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $keyValueFactory);
    $this->storage = $keyValueFactory->get('fuel_calculator_fuel_calculations');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self
  {
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
  public function post($data)
  {
    $distance = $data->get('distance');
    $fuel_consumption = $data->get('fuel_consumption');
    $price_per_liter = $data->get('price');
    if (!is_numeric($distance) || $distance <= 0 || !is_numeric($fuel_consumption) || $fuel_consumption <= 0 || !is_numeric($price_per_liter) || $price_per_liter <= 0) {
      throw new BadRequestHttpException('Invalid input data.');
    } else {
      // Calculate fuel spent and fuel cost using FuelCalculate service (as before).
      list($fuel_spent, $fuel_cost) = $this->fuelCalculatorService->calculate($distance, $fuel_consumption, $price_per_liter);

      // Round the results to one decimal place.
      $fuel_spent = round($fuel_spent, 1);
      $fuel_cost = round($fuel_cost, 1);

      // Return the results as a JSON response.
      return new ResourceResponse([
        'fuel_spent' => $fuel_spent,
        'fuel_cost' => $fuel_cost,
      ]);
    }
  }
}
