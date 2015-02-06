Widgets Avalanche for Ecwid
=======================

Grab your Ecwid products and categories into a variety of WordPress widgets, including a slider, a popup, and an accordion.

###### Contributors
scofennell@gmail.com

###### Tags
Ecwid, Ecommerce, E-Commerce, Online Store, Ecwid Slider, Ecwid Popup, Ecwid Accordion.

###### Requires at least
4.1

###### Tested up to
4.1

###### Stable tag
1.1.1

###### License
GPLv2 or later

###### License URI
http://www.gnu.org/licenses/gpl-2.0.html

Description
-----------

Grab your Ecwid products and categories into a variety of WordPress widgets including:

*   A slider.
*   A popup.
*   An accordion.

Installation
------------

1. Upload the `sjf_ecwid_widgets_avalanche` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make sure you have an Ecwid store with an account that allows API access
4. Visit the plugin settings page and click the button to connect your store

Frequently Asked Questions
--------------------------

###### The plugin won't work.  Why?
Make sure you have an ecwid store with an account that allows API access.  This may mean you need a paid ecwid account.

###### Does this plugin makes my site really slow?
Only for one page load.  It calls Ecwid, gets your store data, and caches it locally for future use.  Unless you have WordPress in debug mode and you are logged in to the site.  Then it does not cache.

###### A cache?  What if I make changes to my products in Ecwid.com?
You'll need to dump your cached data in order to see those changes.  There are three ways to do that:

1. Wait a few hours.  The cache resets each day
2. Click the button to dump your cache in the plugin settings page
3. Save or re-save any widget from this plugin

###### The accordion widget shows some products multiple times.  This is dumb.
It loops through all your categories, and shows all the products in each category.  If a product is in multiple categories, it will appear multiple times.

###### Some of these widgets don't do exactly what I want them to do. Can you change something?
Probably!  Contact me at scofennell@gmail.com to inquire about custom work.

Screenshots
-----------

This is the plugin settings screen.  You must visit this screen after activating this plugin in order to connect your store.  You can return to this screen at any time to disconnect your store, or to dump your cache

![Settings screen](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-1.png)

This is the slider widget.

![Settings screen](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-2.png)

This is the popup widget.

![Settings screen](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-3.png)

This is the accordion widget.

![Settings screen](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-4.png)

This is the slider widget in wp-admin.

![Settings screen](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-5.png)

This is the popup widget in wp-admin.

![Settings screen](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-6.png)

This is the accordion widget in wp-admin.

![Settings screen](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-7.png)

Changelog
---------

### 1.1.1
Stop asking Ecwid for permissions we don't need.

### 1.1
Smarter welcome and error messages for new accounts.

### 1.0
Initial release.