=== Really Simple SSL ===
Contributors: RogierLankhorst, markwolters, hesseldejong, vicocotea, marcelsanting, janwoostendorp
Donate link: https://www.paypal.me/reallysimplessl
Tags: Security, SSL, https, HSTS, mixed content
Requires at least: 5.9
License: GPL2
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 8.1.3

Easily improve site security with WordPress hardening, vulnerability detection and SSL certificate generation.

== Description ==

=== Really simple, effective and lightweight WordPress Security ===
Really Simple SSL is the most lightweight and easy-to-use security plugin for WordPress. It lays the foundation of your WordPress website's security by leveraging your SSL certificate, scanning for possible vulnerabilities and implementing essential WordPress hardening features.

We believe that security should have the absolute minimum effect on website performance, user experience and maintainability. Therefore, Really Simple SSL is:

* **Lightweight:** Every security feature is developed with a modular approach and with performance in mind. Disabled features won't load any redundant code.
* **Easy-to-use:** 1-minute configuration with short onboarding setup.

=== Security Features ===

= Easy SSL Migration =
Migrates your website to HTTPS and enforces SSL in just one click.

* 301 redirect via PHP or .htaccess
* Secure cookies
* Let's Encrypt: Install an SSL Certificate if your hosting provider supports manual installation.
* Server Health Check: Your server configuration is every bit as important for your website security.

= WordPress Hardening =
Tweak your configuration and keep WordPress fortified and safe by tackling potential weaknesses.

* Prevent code execution in the uploads folder
* Prevent login feedback and disable user enumeration
* Disable XML-RPC
* Disable directory browsing
* Username restrictions (block 'admin' and public names)
* and much more..

= Vulnerability Detection =
Get notified when plugins, themes or WP core contain vulnerabilities and need appropriate action.

=== Improve Security with Really Simple SSL Pro ===
[Protect your site with all essential security features by upgrading to Really Simple SSL Pro.](https://really-simple-ssl.com/)

= Advanced SSL enforcement =
* Mixed Content Scan & Fixer. Detect files that are requested over HTTP and fix it, both Front- and Back-end.
* Enable HTTP Strict Transport Security and configure your site for the HSTS Preload list.

= Security Headers =
Security headers protect your site visitors against the risk of clickjacking, cross-site-forgery attacks, stealing login credentials and malware.

* Independent of your Server Configuration, works on Apache, LiteSpeed, NGINX, etc.
* Protect your website visitors with X-XSS Protection, X-Content-Type-Options, X-Frame-Options, a Referrer Policy and CORS headers.
* Automatically generate your WordPress-tailored Content Security Policy.

= Vulnerability Measures =
When a vulnerability is detected in a plugin, theme or WordPress core you will get notified accordingly. With Vulnerability Measures, you can configure simple but effective measures to make sure that a critical vulnerability won't remain unattended.

* Force update: An update process will be tried multiple times until it can be assumed development of a theme or plugin is abandoned. You will be notified during these steps.
* Quarantine: When a plugin or theme can't be updated to solve a vulnerability, Really Simple SSL can quarantine the plugin.

= Advanced Site Hardening =
* Choose a custom login URL
* Automated File Permissions check and fixer
* Rename and randomize your database prefix
* Change the debug.log file location to a non-public folder
* Disable application passwords
* Control admin creation
* Disable HTTP methods, reducing HTTP requests

= Login Protection =
Secure your website's login process and user accounts with powerful security measures.

* Two-Step verification (Email login)
* Enforce strong passwords and frequent password change
* Limit Login Attempts

With Limit Login Attempts you can configure a threshold to temporarily or permanently block IP addresses or (non-existing) usernames. You can also throw a CAPTCHA after a failed login (hCaptcha or Google reCaptcha)

= Access Control =
* Restrict access to your site for specific regions.
* Add specific IP addresses or IP ranges to the Blocklist or Allowlist.

== Useful Links ==
* [Documentation](https://really-simple-ssl.com/knowledge-base-overview/)
* [Security Definitions](https://really-simple-ssl.com/definitions/)
* [Translate Really Simple SSL](https://translate.wordpress.org/projects/wp-plugins/really-simple-ssl)
* [Issues & pull requests](https://github.com/Really-Simple-Plugins/really-simple-ssl/issues)
* [Feature requests](https://really-simple-ssl.com/feature-requests/)

== Love Really Simple SSL? ==
If you want to support the continuing development of this plugin, please consider buying [Really Simple SSL Pro](https://www.really-simple-ssl.com/pro/), which includes some excellent security features and premium support.

== About Really Simple Plugins ==
Our mission is to make complex WordPress requirements really easy. Really Simple SSL is developed by [Really Simple Plugins](https://www.really-simple-plugins.com).

For generating SSL certificates, Really Simple SSL uses the [le acme2 PHP](https://github.com/fbett/le-acme2-php/) Let's Encrypt client library, thanks to 'fbett' for providing it. Vulnerability Detection uses WP Vulnerability, an open-source initiative by Javier Casares. Want to join as a collaborator? We're on [GitHub](https://github.com/really-simple-plugins/really-simple-ssl) as well!

== Installation ==
To install this plugin:

1. Make a backup! See [our recommendations](https://really-simple-ssl.com/knowledge-base/backing-up-your-site/).
2. Download the plugin.
3. Upload the plugin to the /wp-content/plugins/ directory.
4. Go to "Plugins" in your WordPress admin, then click "Activate".
5. You will now see the Really Simple SSL onboarding process, to quickly help you through the configuration process.

== Frequently Asked Questions ==
= Knowledge Base =
For more detailed explanations and documentation on all Really Simple SSL features, please search the [Knowledge Base](https://www.really-simple-ssl.com/knowledge-base/)

= Mixed Content issues =
Most mixed content issues are caused by URLs in CSS or JS files. For detailed instructions on how to find mixed content read this [article](https://really-simple-ssl.com/knowledge-base/how-to-track-down-mixed-content-or-insecure-content/).

= Generating a Let's Encrypt SSL Certificate =
We added the possibility to generate a Free SSL Certificate with Let's Encrypt in our Really Simple SSL Wizard. We have an updated list available for all possible integrations [here](https://really-simple-ssl.com/install-ssl-certificate/). Please leave feedback about another integration, incorrect information, or you need help.

= Redirect loop issues =
If you are experiencing redirect loops on your site, try these [instructions](https://really-simple-ssl.com/knowledge-base/my-website-is-in-a-redirect-loop/).

= Is the plugin multisite compatible? =
Yes. There is a dedicated network settings page where you can control settings for your entire network, at once.

= Uninstalling Really Simple SSL =
The plugin checks your certificate before enabling, but if, for example, you migrated the site to a non-SSL environment, you might get locked out of the back-end.

If you can't deactivate, do not just remove the plugin folder to uninstall! Follow these [instructions](https://really-simple-ssl.com/knowledge-base/uninstall-websitebackend-not-accessible/) instead.

== Changelog ==
= 8.1.3 =
* May 16th, 2024
* Fix: WP Rocket compatibility causing an issue when advanced-headers.php does not exist

= 8.1.2 =
* May 16th, 2024
* Fix: upgrade advanced-headers.php file to allow early inclusion of the file. The ABSPATH defined check causes in issue for early inclusion, so must be removed.

= 8.1.1 =
* May 14th, 2024
* New: detection of non-recommended permissions on files
* New: Configure region restrictions for your site
* Improvement: Textual change on premium overlay
* Improvement: Upgraded minimum required PHP version to 7.4
* Improvement: compatibility with Bitnami
* Improvement: compatibility of Limit Login Attempts with Woocommerce
* Improvement: remove duplicate X-Really-Simple-SSL-Test from advanced-headers-test.php
* Improvement: clear notice about .htaccess writable if do_not_edit_htaccess is enabled
* Fix: upgrade from <6.0 version to >8.0 causing a fatal error
* Fix: URL to details of detected vulnerabilities was incorrect

= 8.1.0 =
* Improvement: some string corrections
* Fix: show 'self' as default in Frame Ancestors
* Improvement: catch not existing rsssl_version_compare
* Improvement: check for openSSL module existence
* Improvement: set default empty array for options, for legacy upgrades
* Improvement: disable custom login URL when plain permalinks are enabled
* New: Limit Login Attempts Captcha integration
* Improvement: drop renamed folder notice, not needed anymore
* Improvement: enable advanced headers in onboarding
* Improvement: is_object check in updater

= 8.0.1 =
* Fix: enable 2FA during onboarding when not selected by user
* Improvement: better CSP defaults
* Fix: on upgrade to pro, free settings were cleared if "clear settings on deactivation" was enabled
* Fix: catch several array key not existing errors

= 8.0.0 =
* New: hide remember me checkbox
* New: extend blocking of malicious admin creation to multisite
* Improvement: drop prefetch-src from Content Security Policy
* Improvement: disable two-fa when login protection is disabled

= 7.2.8 =
* Fix: clear cron schedules on deactivation
* Improvement: translations update
* Notice: inform users about upcoming merge of free and pro plugin, not action needed, everything will be handled automatically

= 7.2.7 =
* Improvement: added integration with FlyingPress and Fastest Cache
* Improvement: fix exiting a filter, causing a compatibility issue with BuddyPress

= 7.2.6 =
* Improvement: text changes
* Improvement: css on login error message
* Improvement: header detection improved by always checking the last url in the redirect chain
* New: Added option to limit login cookie expiration time
* Fix: custom 404 pages i.c.w. custom login url

= 7.2.5 =
* Fix: IP detection header order
* Fix: table creation on activation of LLA module

= 7.2.4 =
* Fix: PHP warning in Password Security module
* Fix: change login url feature not working with password protected pages
* Improvement: move database table creation to Limit Login Attempts module
* Improvement: prevent php error caused by debug.log file hardening feature

= 7.2.3 =
* Fix: CSP data not showing in datatable

= 7.2.2 =
* Improvement: improved check for PharData class

= 7.2.1 =
* Fix: Config for CSP preventing Learning mode from completing
* Fix: datatable styling
* Fix: using deactivate_https with wp-cli did not remove htaccess rules
* Improvement: add query parameter to enforce email verification &rsssl_force_verification
* Improvement: css for check certificate manually button

= 7.2.0 =
* Fix: changed link to article
* Fix: remove flags .js file which was added twice, props @adamainsworth
* Fix: typo in missing advanced-headers.php notice
* Improvement: catch php warning when script src is empty when using hide wp version, props @chris-yau
* Improvement: new save & continue feedback
* Improvement: datatable styling
* Improvement: new react based modal
* Improvement: menu re-structured
* Improvement: re-check vulnerability status after core update
* Improvement: link in the email security notification to the vulnerability page instead of to a general explanation

= 7.1.3 =
* October 11th 2023
* Fix: React ErrorBoundary preventing Let's Encrypt generation to complete.

= 7.1.2 =
* October 6th 2023
* Fix: hook change in integrations loader causing modules not to load. props @rami5342

= 7.1.1 =
* October 5th 2023
* Fix: incorrect function usage, props @heutger

= 7.1.0 =
* October 4th 2023
* Improvement: detection if advanced-headers.php file is running

= 7.0.9 =
* September 5th 2023
* Improvement: typo update word
* Improvement: translatability in several strings.

= 7.0.8 =
* August 8th 2023
* Improvement: WordPress tested up to 6.3
* Improvement: improve file existence check json
* Fix: handling of legacy options in php 8.1
* Fix: count remaining tasks

= 7.0.7 =
* July 25th 2023
* Improvement: modal icon placement in wizard on smaller screens
* Improvement: expire cached detected headers five minutes after saving the settings
* Fix: handling of legacy options in php 8.1
* Fix: prevent issues with CloudFlare when submitting support form from within the plugin
* Fix: translations singular/plural for japanese translations @maboroshin

= 7.0.6 =
* July 4th 2023
* Improvement: support custom wp-content directory in advanced-headers.php
* Improvement: prevent usage of subdirectories in custom login url
* Fix: translations not loading for chunked react components
* Improvement: add option to manually re-check vulnerabilities '&rsssl_check_vulnerabilities', props @fawp

= 7.0.5 =
* Fix: some users with a non www site reporting issues on the login page over http://www, due to the changes in the wp redirect. Reverting to the old method. props @pedalnorth, @mossifer.

= 7.0.4 =
* June 14th 2023
* Improvement: notice informing about the new free vulnerability detection feature
* Improvement: improved the php redirect method
* Improvement: make the wp-config.php not writable notice dismissable
* Fix: feedback on hardening features enable action not showing as enabled, props @rtpHarry

= 7.0.3 =
* Fix: fix false positives on some plugins
* Improvement: vulnerability notifications in site health, if notifications are enabled.

= 7.0.2 =
* Improvement: improve matching precision on plugins with vulnerabilities.

= 7.0.1 =
* Fix: When the Rest API is not available, the ajax fallback should kick in, which didn't work correctly in 7.0. props @justaniceguy

= 7.0.0 =
* New: Vulnerability Detection is in Beta - [Read more](https://really-simple-ssl.com/vulnerability-detection/) or [Get Started](https://really-simple-ssl.com/instructions/about-vulnerabilities/)
* Improvement: move onboarding rest api to do_action rest_route
* Improvement: catch several edge situations in SSL Labs api
* Improvement: SSL Labs block responsiveness
* Improvement: more robust handling of wp-config.php detection

= 6.3.0 =
* Improvement: added support for the new Let's Encrypt staging environment

= 6.2.5 =
* Improvement: add warning alert option
* Fix: capability mismatch in multisite. props @verkkovaraani

= 6.2.4 =
* Improvement: optionally enable notification emails in onboarding wizard
* Improvement: onboarding styling
* Fix: catch non array value from notices array, props @kenrichman
* Fix: typo in documenation link, props @bookman53

= 6.2.3 =
* Improvement: Changed Back-end react to functional components
* Improvement: multisite notice should link to network admin page
* Improvement: detect existing CAA records to check Let's Encrypt compatibility
* Improvement: tested up to wp 6.2
* Improvement: UX improvement learning mode

= 6.2.2 =
* Fix: capability mismatch for a non administrator in multisite admin, props @jg-visual

= 6.2.1 =
* Fix: race condition when activating SSL through wp-cli, because of upgrade script
* Fix: missing disabled state in textarea and checkboxes
* Fix: some strings not translatable
* Fix: Let's Encrypt renewal with add on
* Improvement: permissions check re-structuring
* Improvement: notice on subsite within multisite environment about wildcard updated

= 6.2.0 =
* New: optional email notifications on advanced settings
* Improvement: added tooltips
* Improvement: added warnings for .htaccess redirect
* Improvement: don't send user email change on renaming admin user, as the email doesn't actually change
* Improvement: Use BASEPATH only for wp-load.php, so symlinked folders will load based on ABSPATH
* Improvement: Improved support for environments where Rest API is blocked

= 6.1.1 =
* Fix: WP CLI not completing SSL when because site_has_ssl option is not set if website has not been visited before, props @oolongm
* Improvement: prevent 'undefined' status showing up in api calls on settings page
* Improvement: show notice if users are using an <2.0 Let's Encrypt shell add-on which is not compatible with 6.0

= 6.1.0 =
* Improvement: some UX changes
* Improvement: Limit number of notices in the dashboard
* Improvement: load rest api request url over https if website is loaded over https
* Fix: empty menu item visible in Let's Encrypt menu

= 6.0.14 =
* Fix: settings page when using plain permalinks, props @mvsitecreator, props @doug2son

= 6.0.13 =
* Improvement: improve method of dropping empty menu items in settings dashboard
* Improvement: dynamic links in auto installer
* Improvement: Let's Encrypt Auto installer not working correctly, props @mirkolofio
* Improvement: change rest_api method to core wp apiFetch()
* Improvement: scroll highlighted setting into view after clicking "fix" on a task
* Improvement: run http method test in batches, and set a default, to prevent possibility of curl timeouts on systems with CURL issues
* Improvement: clean up code-execution.php file after test, props @spinhead
* Improvement: give notification if 'DISABLE_FILE_EDITING' is set to false in the wp-config.php props @joeri1977
* Improvement: drop some unnecessary translations
* Improvement: set better default, and change transients to option for more persistent behavior in wp version test, props @photomaldives
* Fix: Burst Statistics not activating after installation
* Fix: CSS for blue labels in progress dashboard below 1080px
* Fix: WPCLI SSL activation not working due to capability checks, props @oolongm
* Fix: catch invalid account error in Let's Encrypt generation, props @bugsjr
* Fix: do not block user enumeration for gutenberg

= 6.0.12 =
* Fix: on multisite, the test for users with admin username did not use the correct prefix, $wpdb->base_prefix, props @jg-visual
* Improvement: allow submenu in back-end react application
* Improvement: Skip value update when no change has been made
* Improvement: no redirect on dismiss of admin notice, props @gangesh, @rtpHarry, @dumel
* Improvement: remove obsolete warning
* Improvement: qtranslate support on settings page

= 6.0.11 =
* Fix: on some environments, the HTTP_X_WP_NONCE is not available in the code, changed logged in check to accomodate such environments
* Fix: dismiss on admin notices not immediately dismissing, requiring dismiss through dashboard, props @dumel

= 6.0.10 =
* Fix: Apache 2.4 support for the block code execution in the uploads directory hardening feature, props @overlake
* Fix: When used with Varnish cache, Rest API get requests were cached, causing the settings page not to update.
* Fix: Ensure manage_security capability for users upgraded from versions before introduction of this capability
* Fix: allow for custom rest api prefixes, props @coderevolution
* Fix: bug in Let's Encrypt generation with DNS verification: saving of 'disable_ocsp' setting, create_bundle_or_renew action with quotes
* Fix: change REST API response method to prevent script errors on environments with PHP warnings and errors, causing blank settings page
* Improvement: Simplify user enumeration test
* Improvement: catch unexpected response in SSL Labs object
* Improvement: z-index on on boarding modal on smaller screen sizes, props @rtpHarry
* Improvement: hide username field if no admin username is present, props @rtpHarry

= 6.0.9 =
* Fix: incorrectly disabled email field in Let's Encrypt wizard, props @cburgess
* Improvement: on rename admin user, catch existing username, and strange characters
* Improvement: catch openBaseDir restriction in cpanel detection function, props @alofnur
* Improvement: remove 6.0 update notices on subsites in a multisite network, props @wpcoderca, (@collizo4sky

= 6.0.8 =
* Improvement: Lets Encrypt wizard CSS styling
* Improvement: re-add link to article about Let's Encrypt so users can easily find the URL
* Improvement: let user choose a new username when selecting "rename admin user"

= 6.0.7 =
* Fix: restrict conditions in which htaccess rewrite runs, preventing conflicts with other rewriting plugins

= 6.0.6 =
* Fix: drop upgrade of .htaccess file in upgrade script

= 6.0.5 =
* Fix: race condition in .htaccess update script, where multiple updates simultaneously caused issues with the .htaccess file

= 6.0.4 =
* Fix: using the .htaccess redirect in combination with the block code execution in uploads causes an issue in the .htaccess redirect
* Fix: deactivating Really Simple SSL does not completely remove the wp-config.php fixes, causing errors, props @minalukic812

= 6.0.3 =
* Fix: Rest Optimizer causing other plugins to deactivate when recommended plugins were activated, props @sardelich

= 6.0.2 =
* Fix: do not show WP_DEBUG_DISPLAY notice if WP_DEBUG is false, props @janv01
* Fix: empty cron schedule, props @gilvansilvabr
* Improvement: several typo's and string improvements
* Fix: auto installer used function not defined yet
* Fix: rest api optimizer causing an error in some cases @giorgos93

= 6.0.1 =
* Fix translations not loading for scripts

= 6.0.0 =
* Tested up to WordPress 6.1.0
* Improvement: User Interface
* New: Server Health Check - powered by SSLLabs
* New: WordPress Hardening Features

== Upgrade notice ==
On settings page load, the .htaccess file is no rewritten. If you have made .htaccess customizations to the RSSSL block and have not blocked the plugin from editing it, do so before upgrading.
Always back up before any upgrade. Especially .htaccess, wp-config.php and the plugin folder. This way you can easily roll back.

== Screenshots ==
1. Your Really Simple SSL dashboard - For optimal configuration.
2. The Server Health Check - An in-depth look at your server.
3. New Hardening Features - Fortify your website by minimizing weaknesses.