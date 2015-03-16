Widgets Avalanche for Ecwid
=======================

Grab your Ecwid products and categories into a variety of WordPress widgets, including a slider, a popup, and an accordion.

###### Contributors
scofennell@gmail.com

###### Tags
Ecwid, Ecommerce, E-Commerce, Online Store, Ecwid Slider, Ecwid Popup, Ecwid Accordion, Ecwid Table.

###### Requires at least
4.1

###### Tested up to
4.1.1

###### Stable tag
1.5.4

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
*   An autosuggest.
*   A sortable table.
*   A link to an RSS feed for your products.

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

###### Does this plugin make my site really slow?
Only for one page load.  It calls Ecwid, gets your store data, and caches it in your WordPress database for future use.  Unless you have WordPress in debug mode and you are logged in to the site.  Then it does not cache.

###### Wait, so there is a slow page load?  For every user?
No, just for one user.  Once one user loads the page, your WordPress site will have cached the data from Ecwid, and this establishes the cache from which all other users will benefit for the lifetime of that cache.

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

![Slider widget](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-2.png)

This is the popup widget.

![popup widget](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-3.png)

This is the accordion widget.

![Accordion widget](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-4.png)

This is the autosuggest widget.

![Autosuggest widget](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-5.png)

This is the sortable widget.

![Sortable widget](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-6.png)

This is the RSS widget.

![RSS widget](https://raw.githubusercontent.com/scofennell/sjf_ecwid_widgets_avalanche/master/assets/screenshot-7.png)

Changelog
---------

### 1.5.4
Improved FAQ.

### 1.5.3
Moved docs sections to their widget files.

### 1.5.2
Moved JS to its own file, fixed JS error in wp-admin.

### 1.5.1
Fixed trailing slash error with feed 404.

### 1.5
Added RSS widget & feed.

### 1.4
Added sortable table widget.
Standardized css classes.

### 1.3
Added debug console in wp-admin.

### 1.2
Added Autosuggest widget.

### 1.1.1
Stop asking Ecwid for permissions we don't need.

### 1.1
Smarter welcome and error messages for new accounts.

### 1.0
Initial release.