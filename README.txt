=== Valor Pay ===
Contributors: abubacker 
Tags: payment, payment gateway, credit card, valor pay, valor paytech
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 7.7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Valor Pay Payment Gateway

== Description ==

Valor Pay is payment gateway for WooCommerce.

Valor PayTech an Omnichannel Fintech Solution. Valor PayTech provides Merchants and ISO's with every tool they need to succeed in an ever-changing business landscape.

Software Systems and Payment Devices from Valor are the best around for smart hassle-free transactions. Cost-efficient and super smart. With value-added features and reliable service support to match, you’d want Valor to be your partner for growth, always.

**WARNING:** The issue of tax price being calculated twice has been resolved in versions 7.3 and above. Please ensure that you are using version 7.3 or later to avoid encountering this problem.

== Installation ==
Follow below steps to install the plugin.

1. Download the plugin
2. Enter the administrator of your WordPress.
3. Go to Plugins -> Add New -> Upload Plugin
4. Go to Plugin section and find the **Valor Pay**
5. Activate the Valor Pay plugin.
6. To config payment method Go to settings from plugin link or From Woocommerce > Settings > Payments.
7. Please enter the given APP ID, APP key, and EPI ID provided in the Valor portal.

**NOTE**

If you have old valorpos plugin installed remove it and install the new plugin.

== Screenshots ==

1. Valor Pay Settings Page.
2. Payment Gateway in Checkout Page.

== Changelog ==
= 7.7.2 =
* Fix icon length in payment gateway.

= 7.7.1 =
* Updated new valor logo, fix credit card image and failed tracker logic change.

= 7.7.0 =
* Added card type configuration option at the admin level to allow selection of card types: debit cards, credit cards, or both.
* Added L2 and L3 support, Level 3 (L3) benefits applicable only for Visa and Mastercard commercial cards.

= 7.6.1 =
* Added a new hook update the order description and invoice number in the sale payload, introduced token card functionality, updated the "Street No" label in the AVS section and revised the support email.

= 7.6.0 =
* Added support for High Performance Order Storage, Ecommerce device identifier, and allowing a subscription product with a $0 initial payment has been added.

= 7.5.0 =
* Added support for subscription.

= 7.4.0 =
* Added a new configuration to toggle surcharge for debit transactions. This configuration determines whether a surcharge should be applied to debit transactions or not.

= 7.3.1 =
* Update plugin title.

= 7.3.0 =
* Added validation to card fields & Tax amount separated from sub total.

= 7.2.1 =
* Change event listener selector for payment method.

= 7.2.0 =
* Bug Fix remove surcharge fee taxable.

= 7.1.0 =
* Added checkbox to acknowledgment terms.

= 7.0.1 =
* Bug Fix disable refund for auth sale.

= 7.0.0 =
* Major plugin coding standard update.

= 6.0.0 =
* Address Verification Service added.
* Added Payment Failed Tracker.

= 5.0.0 =
* Implemented 2FA for refund.


== Upgrade Notice ==

= 7.0.1 =

If you have old valorpos plugin installed remove it and install the new plugin.
