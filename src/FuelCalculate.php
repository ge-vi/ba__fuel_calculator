<?php

namespace Drupal\fuel_calculator;

/**
 * Provides a FuelCalculate service.
 */
class FuelCalculate {

  /**
   * Calculate the fuel cost based on distance, fuel consumption, and price per liter.
   *
   * @param float $distance
   *   The distance travelled in kilometers.
   * @param float $fuel
   *   The fuel consumption in liters per 100 kilometers.
   * @param float $price
   *   The fuel price per liter in EUR.
   *
   * @return array
   *   Array of calculated fuel spent and fuel cost.
   */
  public function calculate($distance, $fuel_consumption, $price_per_liter){
    $fuel_spent = ($fuel_consumption / 100) * $distance;

    // Calculate fuel cost (in EUR).
    $fuel_cost = $fuel_spent * $price_per_liter;
    return array($fuel_spent, $fuel_cost);
  }
}
