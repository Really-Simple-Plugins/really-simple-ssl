=== Really Simple SSL ===
Contributors:RogierLankhorst
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZEQHXWTSQVAZJ&lc=en_us&item_name=rogierlankhorst%2ecom&item_number=really%2dsimple%2dssl%2dplugin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: mixed content, insecure content, secure website, website security, ssl, https, tls, security, secure socket layers, hsts
Requires at least: 4.2
License: GPL2
Tested up to: 4.5
Stable tag: 2.3.9

No setup required! You only need an SSL certificate, and this plugin will do the rest.

== Description ==
The really simple ssl plugin automatically detects your settings and configures your website.
To keep it lightweight, the options are kept to a minimum. The entire site will move to SSL.

= Three simple steps for setup: =
* Get an SSL certificate (can't do that for you, sorry).
* Activate this plugin
* Enable SSL with one click

Aways backup before you go! If you do not have a sound backup policy, start having one! For a snapshot, install duplicator.

= Love Really Simple SSL? =
Hopefully this plugin save you some hours of work. If you want to support the continuing development of this plugin, you might consider buying the [premium](https://www.really-simple-ssl.com/pro/), which includes
some cool features like the mixed content scan, the option to enable HTTP Strict Transport Security and more detailed feedback on the configuration page.

= What does the plugin actually do =
* The plugin handles most issues that Wordpress has with ssl, like the much discussed loadbalancer issue, or when there are no server variables set at all.
* All incoming requests are redirected to https. If possible with .htaccess, or else with javascript.
* The site url and home url are changed to https.
* Your insecure content is fixed by replacing all http:// urls with https://, except hyperlinks to other domains. Dynamically, so no database changes are made (except for the siteurl and homeurl).

[contact](https://www.really-simple-ssl.com/contact/) me if you have any questions, issues, or suggestions. More information about me or my work can be found on my [website](https://www.rogierlankhorst.com).

= Like to have this plugin in your language? =
Translations can be added very easily [here](https://translate.wordpress.org/projects/wp-plugins/really-simple-ssl). If you do, I can get you added as translation editor to approve the translations.

== Installation ==
To install this plugin:

1. Make a backup!
2. Install your ssl certificate
3. Download the plugin
4. Upload the plugin to the wp-content/plugins directory,
5. Go to “plugins” in your wordpress admin, then click activate.
6. You will get redirected to the login screen. If not, go to the login screen and log on.

= Uninstalling =
In some cases it happens that you cannot access your admin anymore, which would prevent your from uninstalling. The
plugin is shipped with a simple method to uninstall:

1. In the wp-content/plugins/really-simple-ssl folder, rename the file "force-deactivate.txt" to "force-deactivate.php".
2. In your browser, go to http://www.yourdomain.com/wp-content/plugins/really-simple-ssl/force-deactivate.php (replace yourdomain.com with your own domain).
Please remember to use http://, and not https://!

The plugin is now deactivated and all changes were removed.

For more information: go to the [website](https://www.really-simple-ssl.com/), or
[contact](https://www.really-simple-ssl.com/contact/) me if you have any questions or suggestions.

== Frequently Asked Questions ==

= Knowledge base =
For more detailed explanations and documentation on redirect loops, deactivating, mixed content, errors, and so on, please search the [documentation](https://www.really-simple-ssl.com/knowledge-base/)

= Does the mixed content fixer make my site slower? =
On a site where the source consists of about 60.000 characters, the delay caused by the mixed content fixer is about 0.00188 seconds. If this is too much for you, fix the mixed content manually and deactivate it in the settings.

= Mixed content issues =
Most mixed content issues are caused by urls in css or js files.
For detailed instructions on how to find mixed content read this [article](https://really-simple-ssl.com/knowledge-base/how-to-track-down-mixed-content-or-insecure-content/).

= Redirect loop issues =
* If you are experiencing redirect loops on your site, you might want to try disabling the .htaccess:

1. Remove this plugins's rules from your .htaccess.
2. Add to your wp-config.php:
define( 'RLRSSSL_DO_NOT_EDIT_HTACCESS', TRUE);

= How to uninstall when website/backend is not accessible =
Though this plugin is extensively tested, this can still happen. However, this is very easy to fix (you'll need ftp access):

1. In the wp-content/plugins/really-simple-ssl folder, rename the file "force-deactivate.txt" to "force-deactivate.php".
2. In your browser, go to www.yourdomain.com/wp-content/plugins/really-simple-ssl/force-deactivate.php (replace yourdomain.com with your own domain).
Please take care to use http://, and not https://. On a domain without certificate, the deactivate won't load on https.

The plugin is now deactivated and all changes were removed.

= Is the plugin suitable for wordpress multisite? =
Yes, the plugin is wpmu ready.
You can activate ssl per site on subdomain and domain mapping installs. On subfolder installs networkwide activation is encouraged (domain.com/site1).

== Changelog ==
= 2.3.9 =
* Fix: removed internal Wordpress redirect as it causes issues for some users.
* Tweak: improved url request method

= 2.3.8 =
* Tweak: Fallback redirect changed into internal wp redirect, which is faster
* Tweak: When no .htaccess rules are detected, redirect option is enabled automatically
* Tweak: Url request falls back to file_get_contents when curl does not give a result

= 2.3.7 =
* Updated screenshots

= 2.3.6 =
* Fixed: missing priority in template_include hook caused not activating mixed content fixer in some themes

= 2.3.5 =
* Fixed: javascript redirect insertion

= 2.3.4 =
* Tweak: load css stylesheet only on options page and before enabling ssl
* Tweak: mixed content fixer triggered by is_ssl(), which prevents fixing content on http.
* Start detection and configuration only for users with "manage_options" capability

= 2.3.3 =
* Fixed bug in force-deactivate script

= 2.3.2 =
* Changed SSL detection so test page is only needed when not currently on SSL.
* Some minor bug fixes.

= 2.3.1 =
* Removed "activate ssl" option when no ssl is detected.
* Optimized emptying of cache
* Fixed some bugs in deactivation and activation of multisite

= 2.3.0 =
* Given more control over activation process by explicity asking to enable SSL.
* Added a notice if .htaccess is not writable

= 2.2.20 =
Fixed a bug in SSL detection

= 2.2.19 =
Changed followlocation in curl to an alternative method, as this gives issues when safemode or open_basedir is enabled.
Added dismissable message when redirects cannot be inserted in the .htaccess

= 2.2.18 =
Fixed bug in logging of curl detection

= 2.2.17 =
Security fixes in ssl-test-page.php

= 2.2.16 =
Bugfix with of insecure content fixer.

= 2.2.13 =
Added a check if the mixed content fixer is functioning on the front end
Fixed a bug where multisite per_site_activation variable wasn't stored networkwide
Added clearing of wp_rocket cache thans to Greg for suggesting this
Added filter so you can remove the really simple ssl comment
Fixed a bug in the output buffer usage, which resolves several issues.
Added code so JetPack will run smoothly on SSL as well, thanks to Konstantin for suggesting this

= 2.2.12 =
* To prevent lockouts, it is no longer possible to activate plugin when wp-config.php is not writable. In case of loadbalancers, activating ssl without adding the necessary fix in the wp-config would cause a redirect loop which would lock you out of the admin.
* Moved redirect above the wordpress rewrite rules in the htaccess.
* Added an option to disable the fallback javascript redirection to https.

= 2.2.11 =
Brand new content fixer, which fixes all links on in the source of your website.

= 2.2.10 =
* Roll back of mixed content fixer.

= 2.2.9 =
Improved the mixed content fixer. Faster and more effective.

= 2.2.8 =
Edited the wpconfig define check to prevent warnings when none are needed.

= 2.2.7 =
* Extended detection of homeurl and siteurl constants in wp-config.php with regex to allow  for spaces in code.
* Changed text domain to make this plugin language packs ready
* Added 404 detection to ssl detection function, so subdomains can get checked properly on subdomain multisite installs

= 2.2.6 =
Added slash in redirect rule
small bugfixes

= 2.2.3 =
documentation update

= 2.2.2 =
* Added multisite support for the missing https server variable issue
* Improved curl connection script
* Added French translation thanks to Cedric

= 2.2.1 =
* Small bug fixes

= 2.2.0 =
* Added per site activation for multisite, but excluded this option for subfolder installs.
* Added script to easily deactivate the plugin when you are locked out of the wordpress admin.
* Added support for a situation where no server variables are given which can indicate ssl, which can cause Wordpress to generate errors and redirect loops.
* Removed warning on Woocommerce force ssl after checkout, as only unforce ssl seems to be causing problems
* Added Russian translation, thanks to xsascha
* Improved redirect rules in the .htaccess
* Added option te disable the plugin from editing the .htaccess in the settings
* Fixed a bug where multisite would not deactivate correctly
* Fixed a bug where insecure content scan would not scan custom post types

= 2.1.18 =
* Made woocommerce warning dismissable, as it does not seem to cause issues
* Fixed a bug caused by WP native plugin_dir_url() returning relative path, resulting in no ssl messages

= 2.1.17 =
* Fixed a bug where example .htaccess rewrite rules weren't generated correctly
* Added woocommerce to the plugin conflicts handler, as some settings conflict with this plugin, and are superfluous when you force your site to ssl anyway.
* Excluded transients from mixed content scan results

= 2.1.16 =
* Fixed a bug where script would fail because curl function was not installed.
* Added debug messages
* Improved FAQ, removed typos
* Replaced screenshots

= 2.1.15 =
* Improved user interface with tabs
* Changed function to test ssl test page from file_get_contents to curl, as this improves response time, which might prevent "no ssl messages"
* Extended the mixed content fixer to replace src="http:// links, as these should always be https on an ssl site.
* Added an errormessage in case of force rewrite titles in Yoast SEO plugin is used, as this prevents the plugin from fixing mixed content

= 2.1.14 =
* Added support for loadbalancer and is_ssl() returning false: in that case a wp-config fix is needed.
* Improved performance
* Added debuggin option, so a trace log can be viewed
* Fixed a bug where the rlrsssl_replace_url_args filter was not applied correctly.

= 2.1.13 =
* Fixed an issue where in some configurations the replace url filter did not fire

= 2.1.12 =
* Added the force SSL option, in cases where SSL could not be detected for some reason.
* Added a test to check if the proposed .htaccess rules will work in the current environment.
* Readded HSTS to the htaccess rules, but now as an option. Adding this should be done only when you are sure you do not want to revert back to http.

= 2.1.11 =
* Improved instructions regarding deinstalling when locked out of back-end

= 2.1.10 =
* Removed HSTS headers, because it is difficult to roll back.

= 2.1.9 =
* Added the possibility to prevent htaccess from being edited, in case of redirect loop.
= 2.1.7 =
* Refined SSL detection
* Bugfix on deactivation of plugin

= 2.1.6 =
* Fixed an SSL detection issue which could lead to redirect loop

= 2.1.4 =
* Improved redirect rules for .htaccess

= 2.1.3 =
* Now plugin only changes .htaccess when one of three preprogrammed ssl types was recognized.
* Simplified filter use to add your own urls to replace, see f.a.q.
* Default javascript redirect when .htaccess redirect does not succeed

= 2.1.2 =
* Fixed bug where number of options with mixed content was not displayed correctly

= 2.1.1 =
* limited the number of files, posts and options that can be show at once in the mixed content scan.

= 2.1.0 =
* Added version control to the .htaccess rules, so the .htaccess gets updated as well.
* Added detection of loadbalancer and cdn so .htaccess rules can be adapted accordingly. Fixes some redirect loop issues.
* Added the possibility to disable the auto replace of insecure links
* Added a scan to scan the website for insecure links
* Added detection of in wp-config.php defined siteurl and homeurl, which could prevent from successfull url change.
* Dropped the force ssl option (used when not ssl detected)
* Thanks to Peter Tak, [PTA security](http://www.pta-security.nl/) for mentioning the owasp security best practice https://www.owasp.org/index.php/HTTP_Strict_Transport_Security in .htaccess,

= 2.0.7 =
* Added 301 redirect to .htaccess for seo purposes

= 2.0.3 =
* Fixed some typos in readme
* added screenshots
* fixed a bug where on deactivation the https wasn't removed from siturl and homeurl

= 2.0.0 =
* Added SSL detection by opening a page in the plugin directory over https
* Added https redirection in .htaccess, when possible
* Added warnings and messages to improve user experience
* Added automatic change of siteurl and homeurl to https, to make backend ssl proof.
* Added caching flush support for WP fastest cache, Zen Cache and W3TC
* Fixed bug where siteurl was used as url to fix instead of homeurl
* Fixed issue where url was not replaced on front end, when used url in content is different from home url (e.g. http://www.domain.com as homeurl and http://domain.com in content)
* Added filter so you can add cdn urls to the replacement script
* Added googleapis.com/ajax cdn to standard replacement script, as it is often used without https.

= 1.0.3 =
* Improved installation instructions

== Upgrade notice ==
Always back up before any upgrade. Especially .htaccess, wp-config.php and the plugin folder. This way you can easily roll back.

== Screenshots ==
1. After activation, if SSL was detected, you can enable SSL.
2. View your configuration on the settings page
