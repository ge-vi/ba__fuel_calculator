(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.fuelForm = {
    attach: function (context, settings) {
      $("#reset-button").on("click", function (event) {
        event.preventDefault(); // Prevent the form from submitting.
        const form = $(this).closest(".fuel-calculator-fuel-calculator"); // Find the closest parent form element.

        // Loop through all input fields (excluding submit and reset buttons) and clear their values.
        form.find('input:not([type="submit"])').each(function () {
          $(this).val(""); // Clear the value of each field.
        });
        var result = form.find(
          "#fuel-calculator-result-wrapper .fuel-calculation-result"
        );
        result[0].innerHTML =
          "<p>Fuel spent: 0.0 liters</p><p>Fuel cost: 0.0 EUR</p></div>";
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
