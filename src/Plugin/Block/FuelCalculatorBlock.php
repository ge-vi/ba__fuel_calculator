<?php

namespace Drupal\fuel_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'fuel_calculator' block.
 *
 * @Block(
 *   id = "fuel_calculator_block",
 *   admin_label = @Translation("Fuel Calculator block"),
 *   category = @Translation("Use this block to place the fuel calculator form as a block.")
 * )
 */
class FuelCalculatorBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\fuel_calculator\Form\FuelCalculatorForm');
    return $form;
  }

}
