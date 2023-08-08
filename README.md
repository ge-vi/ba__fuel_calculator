# Fuel Consumption Calculator Module

The Fuel Consumption Calculator module provides a versatile calculator to estimate fuel consumption and fuel cost for different scenarios. The calculator can be utilized in two ways: as a block on the homepage and as a separate page at `/fuel-calculator`.

## Features

1. **Block and Page Usage:** The calculator can be placed as a block on the homepage for quick access, and it is also available as a separate page at `/fuel-calculator` for more detailed calculations.

2. **Configurable Default Values:** Administrators can set default values for the calculator in the configuration form found at `/admin/config/system/fuel-default-settings`. By default, these values are preset during installation using the config/install feature.

3. **AJAX for Live Output:** AJAX is employed to display the calculator's output live, ensuring a seamless user experience without page reloads.

4. **JavaScript Reset Function:** A JavaScript function is included to reset the form to its default values easily.

5. **CSS Styling:** The form is styled using CSS to improve its appearance and user-friendliness.

6. **REST API for Fuel Data Submission:** A REST API is implemented with a POST method to receive fuel consumption, distance, and price values in raw JSON format. Users can interact with this API using tools like cURL or Postman. Example JSON data for submission:
[{
"fuel_consumption": 6.59,
"distance": 250,
"price": 2
}]

7. **Prefilling Form with URL Parameters:** Users can prefill the form by passing values through the URL, e.g., `http://example.com/fuel-calculator?distance=350&price=12&fuel_consumption=70`.

8. **Input Validation:** The form supports validation, ensuring that only numeric values greater than 0 are allowed.

9. **Logging of Calculation Results:** All calculation results are stored in the database, enabling easy access to historical data.

## Installation and Configuration

1. Install the Fuel Consumption Calculator module like any other Drupal module.
2. For the Rest API to be enabled, install the restui module using composer require drupal/restui , enable it and enable the fuel consumption API with POST enabled.
2. On install the default values will be set which can be altered for the form on the configuration page at `/admin/config/system/fuel-default-settings`.
3. Place the block on any page to provide quick access to the calculator.
4. Or access the block as a page from `/fuel-calculator`.

## Usage

1. Access the calculator on the homepage placed as a block or through the URL `/fuel-calculator`.
2. Enter the required values for fuel consumption, distance, and fuel price in numeric format and greater than zero.
3. The live output will be displayed via AJAX as you input the values.
4. To prefill the form, pass values through the URL parameters. eg: http://13.234.217.103/fuel-calculator?distance=350&price=12&fuel_consumption=70
5. Submit the form to calculate fuel consumption and fuel cost.
