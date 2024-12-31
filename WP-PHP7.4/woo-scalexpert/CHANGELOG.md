[add 1.6.0] - 2024-12-17
Version 1.6.0 of plugin woocommerce

### Added

- Delivery confirmation
- Variable product support
- Change some translation
- Fix rounding issue with taxes
- Update simulation on product configuration
- Fix losing account connected after payment redirect

[add 1.5.5] - 2024-11-29
Version 1.5.5 of plugin woocommerce

### Added

- Add option to disable simulation in cart
- Fix display of simulation when plugin is disabled
- Fix display of simulation in checkout
- Fix how simulation is calculated in checkout
- Add missing translations

[add 1.5.4] - 2024-10-23
Version 1.5.4 of plugin woocommerce

### Added

- Use native action from Woocommerce to display simulation interface in cart and product pages instead of overriding templates  

[add 1.5.3] - 2024-10-03
Version 1.5.3 of plugin woocommerce

### Added

- Display simulation interface on cart page
- Fixing code quality issues

[add 1.4.0] - 2024-09-04
Version 1.4.0 of plugin woocommerce

### Added

- Display simulation interface on product page and payment page
- Add new lib and process to validate and reformat phone number

[add 1.3.0] - 2024-07-18
Version 1.3.0 of plugin woocommerce

### Added

- Retrieve and display financing sub-status for back-office order tracking
- Administration and personalization of the long credit product free of charge (SCFRLT-TXTS)

[add 1.2.0] - 2024-06-07
Version 1.2.0 of plugin woocommerce

### Added

- compartmentalisation of API calls to reduce API load and maintain availability
- added maximum and minimum limit to prevent API calls outside the scope of products or basket
- added the option of cancelling all or part of financing in woocommerce back office

[add 1.1.0] - 2024-04-23
Version 1.1.0 of plugin woocommerce

### Added

- Format check on the telephone number entered by the customer before API call
- New payment method labels for the Woocommerce BO (default value when the plugin is installed)
- connection prompt added to API return if session lost (woo-scalexpert/woocommerce/checkout/oder-received.php)
- ability to recreate a basket from an order lost due to an API processing error

[add 1.0.7] - 2024-04-10
Version 1.0.7 of plugin woocommerce

### Added

- add verify / check number format in php, does not submit to the api with an inaccurate formatted phone number

[fix 1.0.6] - 2024-04-03
Version 1.0.6 of plugin woocommerce

### Added

- add redirection to public_view_order_url after checkout API error

### Fixed

- Fix verify / check number format new pattern regex

[add 1.0.5] - 2024-03-27
Version 1.0.5 of plugin woocommerce

### Added

- add verify / check number format

[add 1.0.4] - 2024-03-25

### Added

- add comments around custom code in /woocommerce/checkout/form-pay.php
- add comments around custom code in /woocommerce/checkout/thankyou.php
- add comments around custom code in /woocommerce/order/order-details.php
- add comments around custom code in /woocommerce/single-product/meta.php
- add comments around custom code in /woocommerce/single-product/short-description.php

[fix 1.0.3] - 2024-03-20

### Fixed

- Fix DOM in buttons if API does not return images

[fix 1.0.2] - 2024-03-12
Version 1.0.2 of plugin woocommerce
Content : e-financing solutions split payment & long term credit.

### Fixed

- Add tests for showlogo_cart in views/payment-buttons
- Add tests for Product attributes during Checkout
- Add debug explanation in Readme Template Section

[fix 1.0.1] - 2024-02-26
Version 1.0.1 of plugin woocommerce
Content : e-financing solutions split payment & long term credit.

### Fixed

- Fix url api

[1.0.0] - 2024-02-12
Version 1.0.0 of plugin woocommerce
Content : e-financing solutions split payment & long term credit.
