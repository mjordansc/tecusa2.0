=== Additional Order Filters for WooCommerce ===
Tags: woocommerce, woocommerce filters, woocommerce order, filters, order
Tested up to: 6.8.2
Requires at least: 4.6
Requires PHP: 7.0
Stable tag: 1.23
Requires WooCommerce at least: 3.0
Contributors: antonbond
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Do you have a large WooCommerce store with hunderd or thousands orders? Then this plugin created for you.

== Description ==
Additional Order Filters for WooCommerce adds additional filters, with which you can easily and quickly find the orders you need among hundreds of others.

Plugin allows you to search by next filters:

<ul>
	<li>Order Statuses</li>
	<li>Payment Method</li>
	<li>Customer Group</li>
	<li>Shipping Method</li>
	<li>Customer details (email, name, phone, etc)</li>
	<li>Customer Billing Country</li>
	<li>Track Number</li>
	<li>SKU number</li>
	<li>Date Range</li>
	<li>Order price total</li>
</ul>

You also can set your own custom order filters based on order meta fields. This can be any order field, including other plugins.

Fully support High-Performance Order Storage of WooCommerce. Support for the previous version of orders (WordPress posts storage (legacy)) also remains.

Absolutely free. Let's try it!

== Installation ==

1. Install and activate the plugin through the 'Plugins' screen in WordPress
2. You can set up your filters in admin dashboard area using Filters of Orders tab

== Frequently Asked Questions ==

= Does it support High-Performance Order Storage? =

Yes, fully supports High-Performance Order Storage of WooCommerce.

= How does search work? =

Just open tabs with additional filters on Woocommerce orders grid page. Enter a value in the field you need and click 'Apply filters' button.

= Can I search for multiple values in one field at once? =

Yes! You can separate values by comma ','. For example, in SKU field: 'MM123, AS321'; Email: 'post@site.com, anna@site.com', etc.

= Can I search for a partial value? =

Yes! For example, order user phone is 1 (111) 682-5352. You can try search any part of this number: '682', '111', '53', '52', etc.

= Plugin still finds too many orders, but I need a specific one order =

Try to refine your search. Fill in other fields, don't use part of the value.

= Plugin doesn't has the filter which I need =

Sorry for that. The author has collected the most frequently used filters. You can try to contact the author and ask to add a filter.

= How I can contact the author of plugin? =

To contact the author by email antonbondarevych.fruitit@gmail.com

== Changelog ==

= 1.23 =

- Fixed XSS vulnerability in the settings function

= 1.22 =

- Fixed vulnerable to Reflected Cross-Site Scripting

= 1.21 =

- Fixed a bug with getting the current admin page

= 1.20 =

- Added High-Performance Order Storage support
- Code optimization
- Fixed a bug with getting the current page

= 1.12 =

- Code optimization
- XSS vulnerable fixed

= 1.11 =

- Plugin architecture changed
- Code optimization
- Added user custom order filters

= 1.10 =

- Fixed bugs in code
- Code optimization

= 1.09 =

- Added "Customer Billing Country" filter
- Added French translation
- Code optimization

= 1.08 =

- Improved search functions

= 1.07 =

- Fixed bugs in code

= 1.06 =

- Fixed bug with button display
- Tested with WordPress 5.4.1 and WooCommerce 4.1.0

= 1.05 =

- Fixed bug with order status filter field

= 1.04 =

- Test with WordPress 5.1.1 and WooCommerce 3.6.0

= 1.03 =

- Changed search by order status: multi-status search
- Сode optimization

= 1.02 =

- Added languages: Russian, Hebrew
- Сode optimization

= 1.01 =

- Added number of filters in the column
- Сode optimization

= 1.0 =

- First version
