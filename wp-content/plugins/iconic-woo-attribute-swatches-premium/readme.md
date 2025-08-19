## Get the Plugin

If you haven't already, you'll need to purchase a license for [WooCommerce Attribute Swatches](https://iconicwp.com/products/woocommerce-attribute-swatches).

## Installation

To install the plugin:

1. Open wp-admin and navigate to `Plugins > Add New > Upload`.
2. Click Choose File, and choose the file `iconic-woo-attribute-swacthes.zip` that you downloaded earlier.
3. Once uploaded, click activate plugin.
4. The plugin is now installed and activated.

## Settings Page

The plugin does not have a settings page. All options are configured per-attribute.

## Swatch Types

Swatches are assigned per-attribute. Currently, you can choose from any of the following swatch types:

* **Image Swatch**  
  Upload an image to your media library to use it as a swatch. It will use the "thumbnail" size from your media settings.
* **Colour Swatch**  
  Select a colour to use as a swatch.
* **Text Swatch**  
  Use the attribute label as a "tag" style swatch.
* **Radio Buttons**  
  Use the attribute label as a "radio" style list.

## Global Swatches

If your attribute will use the same swatch style over all, or the majority of, your products, then you will want to set the swatch style globally. Luckily, it is very easy to do!

1. Navigate to `Products > Attributes`. If you already have an attribute to use, click `Edit` on the relevant attribute and skip to step 4.
2. Enter your attribute name into the `Name` field.
3. Choose `Select` as your `Type`. This will tell WooCommerce that this attribute is for variable product options.
4. Now you can select your `Swatch Type`. If you select what I call a "visual swatch", i.e. an image or a colour swatch, then you will see two additional fields:  
   * **Swatch Shape**  
     Choose whether you want these swatches to be displayed round or square.
   * **Enable Tooltips?**  
     Select `yes` if you'd like the attribute label to be displayed on hover.
5. Some other additional options are now available:  
   * **Show Swatch in Catalog?**  
     Select yes if you'd like available swatches to be displayed in the catalog listing for each product. They will also click through to the product page to be auto-selected. There is a filter you can use to adjust the position of the swatches: `iconic_was_loop_position`. By default it is set to `woocommerce_before_shop_loop_item_title`.
6. Click `Add Attribute` or `Update`.

If you chose a visual swatch type for your attribute (colour or image), then you can now choose the colour or image for your attribute terms.

1. From the attribute list, click on the attribute name, or the `Configure Terms` icon. If you already have attribute terms, skip to step 3.
2. Enter your attribute term name into the `Name` field.
3. Now you can either select your `Colour Swatch` using the colour picker, or add/upload an `Image Swatch` using the "+" icon.
4. Click `Add New [attribute]` or `Update`.

## Product Specific Swatches

If you want to use swatches only for a specific product, or override the global swatch styling for an attribute on the product, then you can do the following:

1. Once your variable product has been configured and saved, you will see the attributes use for variations under the `Swatches` tab.
2. Click on the attribute you'd like to override, for example "Colour".
3. Choose the new `Swatch Type` you'd like to use.
4. If you chose a visual swatch type (colour or image), then the colour or image fields will appear below.
5. Once you're ready, `Update` or `Publish` your product.