=== Really Simple SSL ===
Contributors:RogierLankhorst
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZEQHXWTSQVAZJ&lc=NL&item_name=rogierlankhorst%2ecom&item_number=really%2dsimple%2dssl%2dplugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: secure website, website security, ssl, https, tls, security, secure socket layers, hsts
Requires at least: 4.2
License: GPL2
Tested up to: 4.4
Stable tag: 2.2.7

No setup required! You only need an SSL certificate, and this plugin will do the rest.

== Description ==
The really simple ssl plugin detects ssl by trying to open a page through https.
If ssl is detected it will configure your site to support ssl.

= Two simple steps for setup: =
* Get an SSL certificate from your hosting provider (can't do that for you, sorry).
* Activate this plugin.

= What does the plugin actually do =
* The plugin handles most issues that Wordpress has with ssl, like the much discussed loadbalancer issue, or when there are no server variables set at all.
* All incoming requests are redirected to https. If possible with .htaccess, or else with javascript.
* The site url and home url are changed to https.
* Your insecure content is fixed by replacing all included resources with https. Dynamically, so no database changes are made (except for the siteurl and homeurl).

= Feedback is welcome! =
Though the plugin is extensively tested and currently successfully active on over 5000 websites, it is impossible to test or even conceive of every different server configuration. So it might be possible you have issues with a non-standard server configuration.
Rather than being mad about it, I would appreciate it if you contact me with the issue, so I can help you fix it and improve the plugin at the same time.
I will need the following information:

* Debug report: activate debug and copy the results
* Domain
* Plugin list

[contact](https://www.really-simple-ssl.com/contact/) me if you have any questions, issues, or suggestions. More information about me or my work can be found on my [website](https://www.rogierlankhorst.com).

= Betatesting =
If you like to betatest, that would be great! Please enter my betatest mailinglist [here](https://www.really-simple-ssl.com/betatesting/).

= I need help translating =
I'd like to include more translations, translations can be added [here](https://translate.wordpress.org/projects/wp-plugins/really-simple-ssl).

= Translation credits =
Thanks for the French translation to [Cédric](http://www.blig.fr/)
Thanks for the Russian translation to [xsacha](https://news36.org/)

= Under development, expected early 2016 =
* A check for possible external resources that are not available over ssl.
* Option to disable javascript redirect in settings.

== Installation ==
To install this plugin:

1. Install your ssl certificate
2. Download the plugin
3. Upload the plugin to the wp-content/plugins directory,
4. Go to “plugins” in your wordpress admin, then click activate.
5. You will get redirected to the login screen. If not, go to the login screen and log on.

= Testing =
This plugin is tested in standard apache environments, but is built with several fallbacks for situations where non-standard configurations are used.
If you have issues, please contact me. Maybe we can fix your configuration as well.

= Uninstalling =
In some cases it happens that you cannot access your admin anymore, which would prevent your from uninstalling. The
plugin is shipped with a simple method to uninstall:

1. In the wp-content/plugins/really-simple-ssl folder, rename the file "force-deactivate.txt" to "force-deactivate.php".
2. In your browser, go to www.yourdomain.com/wp-content/plugins/really-simple-ssl/force-deactivate.php

The plugin is now deactivated and all changes were removed.

For more information: go to the [website](http://www.really-simple-ssl.com/), or
[contact](http://www.really-simple-ssl.com/contact/) me if you have any questions or suggestions.

== Frequently Asked Questions ==
= Troubleshooting shortlist =
You can find the cause of most issues when looking in the Chrome console:
In Chrome, right click on your webpage, then select "inspect element" to see what links are causing this (you can ignore hyperlinks).

* If you are experiencing redirects on your site, you might want to try disabling the .htaccess:

1. Remove this plugins's rules from your .htaccess.
2. Add to your wp-config.php:
define( 'RLRSSSL_DO_NOT_EDIT_HTACCESS', TRUE);

* If parts of your site aren't loading, you might have external resources that are not able to load on ssl. Check the Google console.
* If your browser still gives mixed content warnings:

1. Clear the cache of your browser and of your Wordpress site, if you use a caching plugin.
2. Check the Google console for errors. If you have non https links to your own site, or "src='http://" in the source of your website,
the insecure content fixer is probably blocked by another plugin. You can check this by deactivating your plugins one by one, and see if really simple ssl starts working.
Let me know if you find a plugin conflict, so I can put it in my conflict list.

= How to uninstall when website/backend is not accessible =
Though this plugin is extensively tested, this can still happen. However, this is very easy to fix (you'll need ftp access):

1. In the wp-content/plugins/really-simple-ssl folder, rename the file "force-deactivate.txt" to "force-deactivate.php".
2. In your browser, go to www.yourdomain.com/wp-content/plugins/really-simple-ssl/force-deactivate.php

The plugin is now deactivated and all changes were removed.

= What does the option "HSTS" mean? =
HSTS means HTTP Strict Transport Security, and makes browsers force your visitors over https.
As this setting will be remembered by your visitor's browsers for at least a year, you should only enable this when your setup is up and running, and you do not plan
to revert back to http.

= My hits in Google Analytics have dropped. What happened? =
After you move to SSL, you should change your domain in Google Analytics as well. In Google Search Console, you should add the https variant.
If the redirect wasn't set in your .htaccess for some reason, try to add it manually. I've had feedback that GA does not register hits when the redirect was not in place.

= The settings page says redirect could not be set in the .htaccess. Is that a problem? =
Not really. The plugin also adds some javascript to redirect any non https pages, so your site should load over https without any problems.
Furthermore, you can enable the HSTS setting to improve security.

Common causes:
1. The .htaccess is not writable, or not available. To fix it, make writable, or enter the rewriterules manually
2. Testing of the .htaccess rewrite rules failed, due to missing server variables or not being able to reach the testing page. These issues are caused by your server configuration, contact your hosting provider

= Is it possible to exclude certain urls from the ssl redirect? =
That is not possible. This plugin simply forces your complete site over https, which keeps it lightweight.

= Is the plugin suitable for wordpress multisite? =
Yes, the plugin is wpmu ready.
You can activate ssl per site on subdomain installs. On subfolder installs networkwide activation is strongly advised (domain.com/site1).

= Does the plugin do a seo friendly 301 redirect in the .htaccess? =
Yes, default the plugin redirects permanently with [R=301].

== Changelog ==
= 2.2.7 =
Changed text domain to comply with Wordpress translations
Added 404 detection to ssl detection function, so subdomains can get checked properly on subdomain multisite installs

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
In general I would always recommend a solid backup policy, as that will prevent a lot of stress, yelling, and hitting yourself.

== Screenshots ==
1. After activation, your ssl will be detected if present
2. View your configuration on the settings page
3. Check if your site has mixed content. If you want you can just leave it that way, because of the built in mixed content fixer.
