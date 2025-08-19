**v1.20.2** (28 Apr 2025)  
[fix] Updated dependencies and added more safety checks to telemetry opt-ins/opt-outs  

**v1.20.1** (24 Apr 2025)  
[fix] Variation price on attribute selection now correctly updates after recent WC core a11y update.  

**v1.20.0** (19 Feb 2025)  
[update] Licensing.  

**v1.19.0** (04 Sep 2024)  
[new] WooCommerce Product Block editor compatibility  
[new] Compatibility with CURCY - WooCommerce Multi Currency plugin  
[new] Developer filter: `iconic_was_attribute_option_value`  
[fix] Accordion on the product page for block-based themes  
[fix] Replace deprecated `FILTER_SANITIZE_STRING`  

**v1.18.0** (15 May 2024)  
[new] Compatibility with Discount Rules and Dynamic Pricing for WooCommerce plugin  
[new] Added developer filter to cache product swatch HTML for performance  
[new] Developer filter `iconic_was_should_clear_static_fees_when_get_fees_by_attribute`  
[update] Fees are now visible in the cart / mini-cart  
[fix] Check for a valid attachment ID before retrieving an image in the attribute terms list table  
[fix] Ensure attribute slugs are formatted in is_was_enabled  

**v1.17.3** (11 Dec 2023)  
[update] Iconic dependencies.  

**v1.17.2** (15 Nov 2023)  
[fix] Prevent multiple variations forms from breaking swatch selections  

**v1.17.1** (31 Aug 2023)  
[fix] Fix license validation issues and type errors  

**v1.17.0** (24 Aug 2023)  
[new] New licensing system  
[fix] Do not show product specific swatch data for simple products in WLV  
[fix] Fees are now added to manually created order items  

**v1.16.3** (21 Jul 2023)  
[update] Updated Iconic dependencies  

**v1.16.2** (5 Jul 2023)  
[new] Developer Filter: iconic_was_image_swatch_attachment_id  
[update] Updated dependencies  
[fix] Fatal error while editing a product template with Elementor  
[fix] Check filters key exists in swatch data to avoid warning  

**v1.16.1** (6 Jun 2023)  
[fix] Fixed an issue affecting visibility of the fees table for custom attributes.

**v1.16.0** (6 Jun 2023)  
[update] Relocated product-specific attribute fee fields from the Attributes tab to the Attribute Swatches (formerly known as Swatches) tab.  

**v1.15.0** (1 Jun 2023)  
[new] Filter: use `iconic_was_swatches_show_unavailable ` to show unavailable variation swatches  
[new] Filter: use `iconic_was_sort_attribute_terms` to sort attribute groups with custom code  
[new] Filter: use `iconic_was_loop_use_product_swatch_data` to enable product-specific swatch settings in the loop  
[new] Declared compatibility with WooCommerce's HPOS feature  

**v1.14.2** (2 Jan 2023)  
[fix] WOOCS: Cart item price currency now changes correctly  

**v1.14.1** (7 Oct 2022)  
[update] Settings framework updated.

**v1.14.0** (23 Sep 2022)  
[fix] Attribute fees are now properly calculated for mini-cart line item prices  

**v1.13.0** (10 Aug 2022)  
[update] Added strike-through styling to OOS text swatches for better accessibility  
[fix] Fixed a bug causing the attribute swatch settings to be hidden  
[fix] Fix to prevent empty fee values when adding products to cart via AJAX  

**v1.12.0** (22 Jul 2022)  
[new] You can now pick swatch colours directly from the list of attribute terms  
[fix] Fix to prevent global fees being returned as zero when saving product attributes  
[fix] Fixed attribute fees in non-default currencies not converting for products using WCML currency switcher  

**v1.11.2** (6 Jul 2022)  
[fix] Prevented a PHP warning caused by invalid attribute taxonomy names  
[fix] Fixed a bug where tax was missing from products with both sale prices and fees  

**v1.11.1** (29 Jun 2022)  
[fix] Fixed a bug that caused the sale price to disappear in the catalog in certain contexts when fees are added  

**v1.11.0** (24 Jun 2022)  
[update] Added a span tag around the attribute label seperator  
[fix] Tweaked accordion handle icon CSS to cater for th or td markup in the variations table  

**v1.10.0** (31 May 2022)  
[fix] Fixed issue where webp images using picture tags would not change in the catalog  
[fix] Fix edge case with label fee if the term is called 0  

**v1.9.0** (28 Apr 2022)  
[new] Added a new filter to modify text swatch labels.  
[new] Added a new getting started tab to the plugin settings.  
[fix] Improve compatibility with WPML.  

**v1.8.0** (21 Mar 2022)  
[new] Added new filter to conditionally hide attribute swatches in archives  
[fix] Fix to prevent incorrect CSS width calculation on swatch slider list  

**v1.7.0** (1 Mar 2022)  
[update] Updated Freemius SDK.  
[fix] Prevent aborting fee checks if the request is AJAX.  
[fix] Added compatibility with WCFAD by re-hooking the totals calculation callback.  
[fix] Fixed a fatal error with the WP CLI commands.  

**v1.6.0** (2 Feb 2022)  
[new] Hook and Filter documentation generation dependencies added  
[fix] Fixed bug where the fees field would not appear when WPML is active  
[fix] Fixed bug preventing missing variation swatches when using custom attributes  

**v1.5.0** (21 Dec 2021)  
[new] Added support for displaying the price suffix in variable product price/from price  
[fix] Silence errors appearing in log files caused by minor PHP issues  

**v1.4.2** (3 Dec 2021)  
[update] Add original product object as parameter to swatch HTML loop filter  
[fix] Fixed issue causing attribute ordering to not be reflected in swatches on the front-end  

**v1.4.1** (1 Dec 2021)  
[fix] Silence warning caused by direct access of WC_Product attributes  

**v1.4.0** (1 Dec 2021)  
[new] Added compatibility with the Aelia Currency Switcher plugin  
[new] Added a filter to support the hiding of attribute label fees  
[new] GitHub workflow improvements  
[update] Add selected class to li element 
[update] Improved compatibility with WPML  
[update] Improved compatibility with WOOCS  
[fix] Allow fees value to be negative  
[fix] Fixed group labels still showing when groups were removed  
[fix] Prevented error when `$product` is a string and not a WC_Product  
[fix] Fixed fee field breaking attribute edit screen  
[fix] Fixed error when fetching "any..." terms  
[fix] Prevented Related Products from disappearing when Salient theme is installed  
[fix] Prevented From: text from appearing when all variation prices are identical  
[fix] Ensured price is updated correctly when no thousand separator is set  
[fix] Prevented group labels from disappearing if single product swatch meta is set  
[fix] Fixed missing variations powered by custom variation attributes  

**v1.3.4** (13 Aug 2021)  
[new] Add compatibility with WooCommerce Currency Switcher (WOOCS) by realmag777  
[update] `Linked Variations` plugin can use attribute groups now  
[update] Update dependencies  
[fix] Compatibility with WooCommerce Product Bundles by Somewherewarm  
[fix] Fix attribute fees not updating issue  
[fix] Fix non-numeric value encountered warning  
[fix] Escape quotes in JS when changing selected label  
[fix] Fix tooltip issues on iOS devices  
[fix] Fix invalid characters message when no color assigned to swatch  
[fix] Disable overflow effects on product page for radio buttons  
[fix] Ensure visual attributes aren't given default values (attribute name)  

**v1.3.3** (20 Apr 2021)  
[update] Remove group label in single line view  
[update] Compatibility with OceanWP theme product filters  
[update] Added grouped attributes to filter widgets  
[update] Compatibility with GeneratePress theme  
[update] Loco translate compatibility  
[update] Update dependencies  
[fix] Warning in_array warning in archive page  
[fix] Fix issue with similar attribute names  
[fix] Compatibility with 3rd-party quickview plugins  
[fix] Expand self-closing jQuery elements, e.g. `<div/>`  
[fix] Update tooltips to work if swatch is loaded dynamically  
[fix] Prevent error when default attribute value contains spaces  

**v1.3.2** (23 Nov 2020)  
[fix] Fixed fees not working bug  

**v1.3.1** (18 Nov 2020)  
[update] Update dependencies  
[fix] Ensure iconic_was_disable_fees runs at the right time  
[fix] Ensure attribute labels are only modified in the variations table  
[fix] Fix tooltip image alignment issue  
[fix] Only add "From:" price label if product has fees  

**v1.3.0** (28 Oct 2020)  
[new] `iconic_was_catalog_swatches` shortcode for displaying the shop/catalog swatches  
[new] Accordion Feature  
[new] Overflow feature - slider, "more", and stacked  
[new] Add global fee field for attributes  
[update] Offset tooltips when on the screen edge  
[update] Add `iconic_was_disable_fees` filter to disable fee hooks  
[update] Allow fees to be set to 0 for product attribute  
[update] Improved swatch tooltips - no layering issues  
[update] Allow float values in fee field (i.e. 2.50)  
[update] Compatibility with WooCommerce Currency Switcher (WOOCS)  
[update] Added a new class on single product page suggesting swatches status  
[update] Compatibility with Product Bundles by SomewhereWarm  
[update] Hide group label when no terms are available for it  
[update] Update POT file  
[update] Update dependencies  
[fix] Don't show backorder products as out of stock  
[fix] Add fees to dropdowns for product attributes  
[fix] Firefox page reload swatch state issue  
[fix] Swatch fees showing "0" when no fee set  
[fix] Fix price with fees in mini-cart  

**v1.2.7** (23 Apr 2020)  
[update] Compatibility with "Smart Variation Images"  
[update] Compatibility with Iconic Sales Booster  
[update] Update dependencies  
[update] Change default swatch style to "border"  
[fix] Handle custom attributes (fixes array_diff() error)  
[fix] Swatches showing square rather than round  
[fix] Rounding precision issue when using attribute fees  
[fix] Persistent Object Cache issue  

**v1.2.6** (18 Mar 2020)  
[update] Version compatibility  

**v1.2.5** (28 Oct 2019)  
[update] Add out of stock class to swatch list item (so it can be hidden or styled)  
[fix] Ensure attributes set to "any" are counted as available  

**v1.2.4** (23 Oct 2019)  
[update] Improve performance when loading swatches in the catalog  
[update] Changed tick icon to embedded fontawesome svg (https://fontawesome.com/license)  
[update] Compatibility with OceanWP Theme  
[update] Update dependencies  
[fix] Ensure unavailable swatches are not shown in the catalog  
[fix] Mark out of stock variations as disabled in the catalog  
[fix] Check for product before adding fee to attribute label (fixes issue with Jilt)  
[fix] Deselect unavailable attribute automatically  

**v1.2.3** (30 July 2019)  
[update] Change fee calculation method for better compatibility  
[update] Ensure variation sale price is formatted correctly  
[fix] Prevent double tap on ios  
[fix] Slow query when fetching first variation for an attribute value (archives)  
[fix] When adding fee to swatch label, check product is not a string (issue with Jilt compatibility)  

**v1.2.2** (1 July 2019)  
[fix] Freemius fix  

**v1.2.1** (23 Apr 2019)  
[update] Don't show attribute fees for admin labels  
[update] Allow swatches to be positioned in ajax based results  
[update] Deselect unavailable attributes on page load  
[fix] Attributes not showing in Swatches tab  
[fix] Issue where new product page was not loading  

**v1.2.0** (18 Apr 2019)  
[new] Ability to add fees to each attribute option  
[update] Compatibility with Woo 3.6.1  
[update] Update dependencies  
[update] Use CRUD for product meta  
[fix] Stop layered nav swatches being replaced by single product settings  

**v1.1.4** (2 Mar 2019)  
[fix] Security Fix  

**v1.1.3** (10 Jan 2019)  
[new] Show swatches in layered nav filters  
[update] Update dependencies  
[update] Allow swatches to be hidden in loop per product via `iconic_was_hide_loop_swatches` filter  
[fix] Sometimes the product image is not updated when clicking on swatches in the loop  
[fix] SSV compatibility. The context switch wasn't restored at the right place on the product loop when product had multiple attributes  
[fix] Sometimes attributes didn't appear in the loop  
[fix] The swatches in the loop didn't follow the custom terms order set in the backend  

**v1.1.2** (10 Sep 2018)  
[update] implement Iconic core classes  
[update] Allow swatches to be displayed under variation products on the catalog  
[fix] Issue with product specific visual swatches saving  
[fix] Use `jQuery` instead of `$` when editing attributes  
[fix] Change method of selecting value in select field  

**v1.1.1** (15 Jun 2018)  
[fix] Attributes not selected when over AJAX variation threshold  

**v1.1.0** (14 Jun 2018)  
[update] Add WPML config settings  
[update] Flatsome compatibility helpers  
[update] Add POT file  
[update] Better conditional fields in admin  
[update] Add Woocommerce Variations Table - Grid compatibility  
[update] Freemius  
[update] Optimize catalog swatches  
[update] Hide unavailable catalog swatches  
[update] New style options for visual swatches  
[update] Add ability to group swatches by label  
[update] Add filters to modify default swatch sizes `iconic_was_default_swatch_size`  
[fix] Product specific attribute swatches not showing in catalog  
[fix] Double tap selection issue on touch devices  
[fix] Attribute order for composite products  
[fix] Image switch size  
[fix] Some styling issues in the product edit panel  
[fix] Don't add swatches in catalog if $product is false  
[fix] Output of swatch options when creating a global attribute  
[fix] Don't remove current values when changing swatch type  
[fix] Ensure "disabled" options are greyed out  

**v1.0.10** (07/10/2017)  
[update] Freemius

**v1.0.9** (13/09/2017)  
[update] Re-enable product-level swatch catalog settings  
[update] Allow custom swatch sizes  
[fix] Escape double quotes in swatch data attributes  
[fix] Some performance updates

**v1.0.8** (06/08/2017)  
[fix] wp_mail issue

**v1.0.7** (02/08/2017)  
[update] Code tidy  
[update] Add filter for loop position priority  
[update] Add new licensing system  
[fix] Make sure chosen attribute span is more specific  
[fix] Product tab formatting  
[fix] Deprecated action  
[fix] Make sure swatches are positioned on 'init'


**v1.0.6** (02/04/2017)  
[update] WooCommerce 3.0.0 compatibility  
[update] Made "disabled" swatch styling more apparent  
[update] Add attribute data to swatch data for filters  
[update] Improve load time in archive  
[update] Allow large preview without enabling tooltips  
[fix] Undefined index error

**v1.0.5** (21/12/2016)  
[update] Show available options on hover  
[update] Remove slug_alt variable  
[update] Atelier compatibility  
[update] Added filters to the modify_attribute_html function  
[update] Remove dashboard  
[update] Add option to change image when swatch is clicked in catalog view  
[fix] Remove max-width on preview image

**v1.0.4** (18/07/2016)  
[update] Add large preview option to tooltip  
[fix] Compatibility with Shop the Look plugin

**v1.0.2** (21/06/2016)  
[fix] Fix admin product tab when attributes do not exist  
[fix] Fix label in admin variations tab  
[update] Ability to show swatches in the catalog listing, they also click through to select that option.

**v1.0.1** (19/06/2016)  
[update] Add swatch options title to term edit page
[fix] Remove license key page
[update] Add check for WooCommerce plugin
[update] Allow disabled swatches to be clicked - refresh selection
[fix] Stop tooltip text wrapping
[fix] Correct attribute term ordering
[update] Compatibility with WooCommerce Quickview by Iconic
[update] modify labels for normal select fields

**v1.0.0** (16/05/2016)  
Initial release
