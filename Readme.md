# SOCIETE GENERALE SCALEXPERT

This version of the Scalexpert plugin is compatible with PHP7.4, Woocommerce 6.3.1 and WordPress 6.3. A PHP8.2 version
is available

Date: 12/02/2024

Version: 1.0.0
content: Scalexpert E-financing solutions supported : Split payment, Long credit

Documentation

# 1. How to install the project

Download the version compatible with your Wordpress / Woocommerce installation. For security reasons, we advise you to
update your PHP / Wordpress / Woocommerce version.

Upload the ZIP file via Plugins/Add New/Upload Plugin ( homeurl/wp-admin/plugin-install.php )

You can also plug the GIT directly into your install and pull the appropriate branch into your project. How GIT works is
not covered in this readme. Visit https://git-scm.com/ for information on how git works.

Make sure that the Scalexpert plugin is installed in /wp-content/plugins/woo-scalexpert/ .

# 2. Activate the plugin

Activate the plugin by clicking on "Activate" dans WP Admin/ Plugins

# 3. Configuring the plugin

The plugin has 3 main configuration tabs. The API keys (Setting the keys), activation of debug mode and activation of
the various finance options (Activate/deactivate).

## 3.1 "Setting the Keys" Tab

Entre votre ID commerçant dans la texte-box correspondante à votre projet. Test ou Production. Si vous n'en avez pas
encore ou vous l'avez perdu, vous trouverez ici lien pour retrouver votre ID. Entrez votre clé API dans le champ idoine,
et testez votre clé en cliquant sur "Vérifier la clé". Le plugin testera la paire de clés correspondante à la sélection
de la listbox "Choose your environnement"

If the key configuration is correct, click on "Save changes".

Only activate the plugin with the switch at the top of this tab once all the configuration is complete.

## 3.2 "Debug mode" Tab

To enable or disable debug mode, click the appropriate button on this tab. The log files will be located in
/wp-content/plugins/woo-scalexpert/logs. You will need an sFtp client to access these log files. For security reasons,
direct access to these files is prohibited.

## 3.3 "Financing Controller (Activate/deactivate)" Tab

In this tab you will find the financing options available in your Scalexpert contract. Simply click on the button of the
option you wish to activate on your site. Then click on "Save changes". If you have not yet registered your Scalexpert
key pair, you will not find eligible financing in this tab.

# 4. AdminPage "Customise"

On this page, you can to some extent customise the display of Scalexpert in your Woocommerce Store.
The possible options are :

- Display on the product page or not
- Customise the title of the financing option on the product page
- Decide on the position above or below the add to basket button
- add the Société Générale logo to the product page for financing options
- customise the title of the financing option on the basket page
- whether or not to display the Société Générale logo on the basket page
- exclude categories for the display of financing options

# 5. AdminPage "Woocommerce"

On this page you configure Woocommerce display options and how Scalexpert can access the Brand and Model attributes that
are sent to the Scalexpert API.

Scalexpert title Basket

- This text is used in the basket to display all the available financing options.

Scalexpert description

- This description is internal to Woocommerce

Name Attribute Brand

- The name of the Brand attribute that you can configure in your Woocommerce product and that will be sent to
  Scalexpert.

Name Attribute Model

- The name of the Model attribute that you can configure in your Woocommerce product and that will be sent to
  Scalexpert.

# 6. AdminPage "Cron Settings"

On this page you can enable or disable the cron job for updating commands via Scalexpert. Click on "Activate" and select
an update interval. The default interval is the one we recommend by the way.

This cron job will only be launched if there are visits to your Wordpress installation. If you require independent
operation of these updates, it is possible to launch the updates via a url and using a server crontab. This url is made
up of the url of your shop's homepage and the "?updateOrder" parameter. For example, this url would
be: https://www.mon-site.site/ma-boutique/?updateOrder. This is the URL you need to give to your web host so that it can
integrate it into a crontab.
In the following example, the cron will call the update url every ten minutes

*/10 * * * * root curl -k " https://www.mon-site.site/ma-boutique/?updateOrder"

# 7. Templating

The Scalexpert plugin uses a number of Woocommerce templates to display the financing options available in

- product pages,
- the shopping cart
- the payment return page,
- order summary

These templates are located in the /wp-content/plugins/woo-scalexpert/woocommerce folder. You can override their display
to adapt them to your site's design. To do so, copy them, keeping the same tree structure, into the "woocommerce" folder
of the template you're using. The display order is

- 1 Woocommerce template
- 2 Woo-Scalexpert template
- 3 Template of the Wordpress theme used

You'll need to use all the code embedded in these templates to display the features developed for Scalexpert.

Have a lot of fun
