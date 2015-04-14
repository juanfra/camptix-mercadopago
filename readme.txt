=== CampTix MercadoPago ===
Contributors: juanfra, andrezrv
Tags: camptix, mercadopago, tickets, wordcamp, event ticketing
Requires at least: 4.1
Tested up to: 4.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Stable Tag: 1.0.6

MercadoPago payment gateway for CampTix. The simple way to sell tickets for your event in Latin american countries.

== Description ==

### Add MercadoPago gateway to CampTix ###

This plugin adds the [MercadoPago](http://mercadopago.com) payment gateway for [CampTix](http://wordpress.org/plugins/camptix/).

With this plugin, users from different countries in Latin America (Argentina, Brazil, Colombia, Venezuela and Mexico) will be able to process their event ticket sales through one of the main payment processors, [MercadoPago](http://mercadopago.com).

= Configuration =

You will need to get your MercadoPago credentials. You can get your "Client_id" and "Client_secret" in these different sites(according to your country of residence):

* [MercadoPago Argentina](https://www.mercadopago.com/mla/herramientas/aplicaciones)
* [MercadoPago Brazil](https://www.mercadopago.com/mlb/ferramentas/aplicacoes)
* [MercadoPago Colombia](https://www.mercadopago.com/mco/herramientas/aplicaciones)
* [MercadoPago Mexico](https://www.mercadopago.com/mlm/herramientas/aplicaciones)
* [MercadoPago Venezuela](https://www.mercadopago.com/mlv/herramientas/aplicaciones)

= Currencies =

With this extension, you can take the payments in the following currencies:

* Argentine Peso
* Brazilian Real
* Colombian Peso
* Mexican Peso
* Venezuelan Bolivar

= Requirements =

Please notice that [CampTix](http://wordpress.org/plugins/camptix/) must be installed and active.

= Contribute =

You can contribute to the code in the [GitHub](https://github.com/juanfraa/camptix-mercadopago) repository.

== Installation ==

* Upload the plugin files to your plugins folder, or install the plugin using the WordPress built-in "Add New" Plugin installer;
* Activate the plugin;
* Navigate to CampTix > Setup > Payment - Activate MercadoPago, and fill in your MercadoPago account details.

== Frequently Asked Questions ==

= What is the plugin license? =

* This plugin is released under the GPL license.

= What is needed to use this plugin? =

* [CampTix](wordpress.org/extend/plugins/camptix/) installed and active;
* An account on [MercadoPago](http://mercadopago.com/).
* Your "Client_id" and "Client_secret" from [MercadoPago](http://mercadopago.com/).


== Changelog ==

= 1.0.6: April 14, 2015 =

* Accept both collection_id and id as data keys to check if IPN is valid.

= 1.0.5: April 14, 2015 =

* Always use $_REQUEST instead of $_GET to obtain values from IPN results.
* Log all the things.

= 1.0.4: April 13, 2015 =

* Check IPN via $_REQUEST
* Log IPN data on success

= 1.0.3: April 6, 2015 =

* Re-added security checks to fix tracking error.
* Quck code changes requested by the meta team.

= 1.0.2: April 3, 2015 =

* Removed unnecessary locale/load_textdomain functionalities.
* Removed option to log (events will be automatically logged from now on)
* Improved code. Added some brace styling to fulfil the WP coding standards.
* Replaced die() with wp_die()
* Replaced json_encode() with wp_json_encode() - (WP4.1 is now a requirement)
* Replaced json_decode() with wp_json_decode() - (WP4.1 is now a requirement)
* Fixed data logging.
* Set sslverify to true everywhere.
* Updated strings and translation files.
* Updated plugin description (Fixed camptix link and added some more information).


= 1.0.1: April 2, 2015 =

* Added images for the public directory.
* Added security checks so the site won't break if Camptix is not active. (props andrezrv)
* Removed cURL SSL version, let it auto-negotiate the protocol.

= 1.0.0 =

* Initial release.

== License ==

CampTix MercadoPago is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

CampTix MercadoPago is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with CampTix MercadoPago. If not, see <http://www.gnu.org/licenses/>.
