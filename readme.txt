=== Really Simple Security - Simple and Performant Security (formerly Really Simple SSL)===
Contributors: RogierLankhorst, markwolters, hesseldejong, vicocotea, marcelsanting, janwoostendorp, wimbraam
Donate link: https://www.paypal.me/reallysimplessl
Tags: security, https, 2fa, vulnerabilities, two factor
Requires at least: 6.6
License: GPL2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 9.4.3

Easily improve site security with WordPress Hardening, Two-Factor Authentication (2FA), Login Protection, Vulnerability Detection and SSL certificate.

== Description ==

=== Really simple, Effective and Performant WordPress Security ===
Really Simple Security is the most lightweight and easy-to-use security plugin for WordPress. It secures your WordPress website with SSL certificate generation, including proper 301 https redirection and SSL enforcement, scanning for possible vulnerabilities, Login Protection and implementing essential WordPress hardening features.

We believe that security should have the absolute minimum effect on website performance, user experience and maintainability. Therefore, Really Simple Security is:

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

= Login Protection =
Allow or enforce Two-Factor Authentication (2FA) for specific user roles. Users receive a two-factor code via Email.

=== Improve Security with Really Simple Security Pro ===
[Protect your site with all essential security features by upgrading to Really Simple Security Pro.](https://really-simple-ssl.com/)

= Advanced SSL enforcement =
* Mixed Content Scan & Fixer. Detect files that are requested over HTTP and fix them to HTTPS, both Front- and Back-end.
* Enable HTTP Strict Transport Security and configure your site for the HSTS Preload list.

= Firewall =
Really Simple Security Pro includes a performant and efficient WordPress firewall, to stop bots, crawlers and bad actors with IP and username blocks.

* 404 blocking - Blocks crawlers as they trigger unusual numbers of 404 errors.
* Region blocking - Only allow/block access to your site from specific regions.
* Automated and customisable Firewall rules.
* IP blocklist and allowlist.

= Security Headers =
Security headers protect your site visitors against the risk of clickjacking, cross-site-forgery attacks, stealing login credentials and malware.

* Independent of your Server Configuration, works on Apache, LiteSpeed, NGINX, etc.
* Protect your website visitors with X-XSS Protection, X-Content-Type-Options, X-Frame-Options, a Referrer Policy and CORS headers.
* Automatically generate your WordPress-tailored Content Security Policy.

= Vulnerability Measures =
When a vulnerability is detected in a plugin, theme or WordPress core you will get notified accordingly. With Vulnerability Measures, you can configure simple but effective measures to make sure that a critical vulnerability won't remain unattended.

* Force update: An update process will be tried multiple times until it can be assumed development of a theme or plugin is abandoned. You will be notified during these steps.
* Quarantine: When a plugin or theme can't be updated to solve a vulnerability, Really Simple Security can quarantine the plugin.

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
* 2FA (two factor authentication) with TOTP
* Passwordless login with passkey login
* Enforce strong passwords and frequent password change
* Limit Login Attempts

With Limit Login Attempts you can configure a threshold to temporarily or permanently block IP addresses or (non-existing) usernames. You can also throw a CAPTCHA after a failed login (hCaptcha or Google reCaptcha)

= Access Control =
* Restrict access to your site for specific regions.
* Add specific IP addresses or IP ranges to the Blocklist or Allowlist.

== Useful Links ==
* [Documentation](https://really-simple-ssl.com/knowledge-base-overview/)
* [Security Definitions](https://really-simple-ssl.com/definitions/)
* [Translate Really Simple Security](https://translate.wordpress.org/projects/wp-plugins/really-simple-ssl)
* [Issues & pull requests](https://github.com/Really-Simple-Plugins/really-simple-ssl/issues)
* [Feature requests](https://github.com/Really-Simple-Plugins/really-simple-ssl/labels/feature%20request)

== Love Really Simple Security? ==
If you want to support the continuing development of this plugin, please consider buying [Really Simple Security Pro](https://www.really-simple-ssl.com/pro/), which includes some excellent security features and premium support.

== About Really Simple Plugins ==
Our mission is to make complex WordPress requirements really easy. Really Simple Security is developed by [Really Simple Plugins](https://www.really-simple-ssl.com/about-us).

For generating SSL certificates, Really Simple Security uses the [le acme2 PHP](https://github.com/fbett/le-acme2-php/) Let's Encrypt client library, thanks to 'fbett' for providing it. Vulnerability Detection uses WP Vulnerability, an open-source initiative by Javier Casares. Want to join as a collaborator? We're on [GitHub](https://github.com/really-simple-plugins/really-simple-ssl) as well!

== Installation ==
To install this plugin:

1. Make a backup! See [our recommendations](https://really-simple-ssl.com/knowledge-base/backing-up-your-site/).
2. Download the plugin.
3. Upload the plugin to the /wp-content/plugins/ directory.
4. Go to "Plugins" in your WordPress admin, then click "Activate".
5. You will now see the Really Simple Security onboarding process, to quickly help you through the configuration process.

== Frequently Asked Questions ==
= Knowledge Base =
For more detailed explanations and documentation on all Really Simple Security features, please search the [Knowledge Base](https://www.really-simple-ssl.com/knowledge-base/)

= What happened with Really Simple SSL? =
All features that made Really Simple SSL the most powerful and easy-to-use SSL generation and redirect plugin are still part of Really Simple Security. The plugin is developed with a modular approach: if you don't want to use the full set of security features, the unused code will not be loaded and won't have any effect on your site's performance.

= Why Really Simple Security? =
In our experience, security solutions for WordPress are often hard to configure, trigger many false positives and have a significant impact on site performance. We have been receiving requests from our users to simplify WordPress security for years, so that has become our mission!

= I want to share my feedback or contribute to Really Simple Security =
You couldn't make us happier! Really Simple Security is GPL licensed and co-created by the WordPress community. All feedback is highly appreciated and has always helped us to better understand users' needs. For code contributions or suggestions, we're on [GitHub](https://github.com/really-simple-plugins/really-simple-ssl). For suggestions, please [open a support ticket](https://wordpress.org/support/plugin/really-simple-ssl/) You can also express your appreciation by [leaving a review](https://wordpress.org/support/plugin/really-simple-ssl/reviews/).

= What are Mixed Content issues? =
Most mixed content issues are caused by URLs in CSS or JS files. For detailed instructions on how to find mixed content read this [article](https://really-simple-ssl.com/knowledge-base/how-to-track-down-mixed-content-or-insecure-content/).

= Generating a Let's Encrypt SSL Certificate =
We added the possibility to generate a Free SSL Certificate with Let's Encrypt in our Really Simple Security Wizard. We have an updated list available for all possible integrations [here](https://really-simple-ssl.com/install-ssl-certificate/). Please leave feedback about another integration, incorrect information, or you need help.

= How do I fix a redirect loop? =
If you are experiencing redirect loops on your site, try these [instructions](https://really-simple-ssl.com/knowledge-base/my-website-is-in-a-redirect-loop/). This can sometimes happen during the migration to HTTPS or due to conflicting redirect rules.

= Is the plugin multisite compatible? =
Yes. There is a dedicated network settings page where you can control settings for your entire network, at once.

= How do I enforce strong passwords? =
Under Login Protection, you can configure minimum strength settings and require users to change their passwords after a defined interval. Disabling weak password usage is a best practice.

= How can I change my login URL? =
You can set a custom login URL under Advanced Site Hardening, which helps prevent brute force login attacks and bots targeting wp-login.php.

= Does this plugin redirect HTTP to HTTPS? =
Yes. The plugin enforces HTTPS and handles all necessary redirects, optionally using .htaccess or PHP.

= Can I use Really Simple Security besides WordFence? =
Really Simple Security and WordFence greatly overlap in term of functionality. If you like to use specific features from both plugins, we strongly recommend not to enable similar features twice. The benefit of Really Simple Security is that disabled features don't load any code, so won't have an impact on site performance.

== Changelog ==
= 9.4.3 =
* Improvement: improved compatibility with plain permalinks.
* Improvement: updated links in the plugin.
* Fix: handled a case where the user ID could be empty in 2FA.
* Fix: learn more button in vulnerability e-mail link now links to the correct page.
* Fix: fixed an issue where rsssl_user_can_manage could be undefined when downloading the system status.

= 9.4.2 =
* Fix: Adjusted .htaccess redirect requirements for subfolder configurations
* Fix: re-send e-mail button on the 2FA page will now show a message when the e-mail is sent.
* Fix: restored SCSS files.
* Fix: fixed an issue where the plugin kept redirecting to its settings page after activation.
* Improvement: updated the way other plugins are installed via the onboarding and dashboard page.
* Improvement: added notice with an option to force verify e-mail address.
* Improvement: updated minimum WordPress version to 6.6.

= 9.4.1 =
 * Fix: fixed a translations error where text domain was loaded too early.

= 9.4.0 =
 * Improvement: More detailed feedback when using CLI commands.
 * Improvement: On activation, detect `EXTENDIFY_PARTNER_ID` constant and run `wp rsssl activate_recommended_features`.
 * Improvement: Standardize RSS onboarding hoster list to brand names.
 * Improvement: "Disable user enumeration" now returns 401 Unauthorized (instead of 404 Not Found) for non-authenticated requests to the /wp/v2/users/ endpoint.
 * Include SimplyBook in “onboarding” and “other plugins” sections.
 * Fix: Adjust plugin initialization timing to prevent a textdomain warning.
 * Fix: Fixed the feedback when an email is resend during Two-Factor Authentication setup.
 * Fix: Fixed the Single Sign on link to support custom login urls.

= 9.3.5 =
* April 29th, 2025
* Improvement: Tested up to WordPress 6.8
* Improvement: Some translation updates
* Improvement: Check for autoloader in cron
* Fix: 2FA methods can now be set on profile page

= 9.3.3 =
* April 2nd, 2025
* Improvement: Added multiple WP-CLI commands to better align with recent plugin features
* Improvement: Added support for custom/multiple roles in Two Factor Authentication

= 9.3.2.1 =
* March 20th, 2025
* Fix: Properly handle unknown plugins in upgrade requests, preventing unintended behavior.

= 9.3.2 =
* March 5th, 2025
* Improvement: Added filters to customize Let's Encrypt Wizard behavior
* Fix: Removed default checkbox behavior from configuration settings.
* Fix: Handle multiple tooltip reasons for disabled select fields

= 9.3.1 =
* February 12th, 2025
* Improvement: Not able to use email needed functions when email is not yet verified.
* Fix: All instruction links are now correct.
* Fix: Undefined array key "m" when showing vulnerability details.
* Fix: Prevent errors when downgrading to free.
* Fix: Compatibility between 2FA and JetPack “Log in using WordPress.com account” setting

= 9.2.0 =
* January 20th, 2025
* Fix: Added nonce check to certificate re-check button.
* Fix: In some cases the review notice was not properly dismissible.

= 9.1.4 =
* Improvement: do not track 404's for logged in users
* Improvement: implemented the rsssl_wpconfig_path filter in all wp-config functions
* Improvement: Faster onboarding completion after clicking Finish button
* Improvement: CSS. Shields in user interface on datatables are no longer cut off

= 9.1.3 =
* November 28th
* Improvement: Width Vulnerabilities -> configuration
* Improvement: 2Fa lockout notice
* Improvement: catch use of short init in advanced-headers file
* Improvement: string improvements and translator comments
* Improvement: Bitnami support for rsssl_find_wordpress_base_path()
* Improvement: integrate Site health notifications with Solid Security
* Improvement: Enhanced random password generation in Rename Admin User feature
* Improvement: Always return string in wpconfig_path() function
* Improvement: Removes configuration options for a user in edit user.
* Fix: Remove duplicate site URL.
* Fix: ensure rsssl_sanitize_uri_value() function always returns a string, to prevent errors.
* Fix: multisite users who have enabled roles couldn’t use the 2fa if an other role than theirs has been forced.
* Fix: The ‘Skip Onboarding’ button presented an undefined page after selecting the email method as an option.
* Fix: Update translation loading according to the new 6.7 method.

= 9.1.2 =
* security: authentication bypass

= 9.1.1.1 =
* November 5th, 2024
*Improvement: updated black friday dates

= 9.1.1 =
* November 5th, 2024
* Improvement: setting a rsssl-safe-mode.lock file now also enables safe mode and deactivates the Firewall, 2FA and LLA for debugging purposes.
* Improvement: update to system status
* Improvement: textual changes
* Improvement: Updated instructions URLs
* Improvement: Changed site health notices from critical to recommended
* Improvement: dropped obsolete react library
* Fix: fixed a bug where the 2FA grace period was kept active after a reset

= 9.1.0 =
* October 22nd
* Improvement: Allow scanning for security headers via http://scan.really-simple-ssl.com  with one click
* Improvement: Remove unnecessary rsssl_update_option calls.
* Fix: prevent potential errors with login feedback..
* Fix: Catch type error when $transients is not an array.

= 9.0.2 =
* Fix: issue with deactivating 2fa

= 9.0.0 =
* September 16th
* Fix: Instructions URL in the Firewall settings.
* Fix: Fixed incorrect instructions URL
* Fix: Let's Encrypt returning an old certificate on auto-renewed certificates
* Improvement: As the X-Frame-Options is deprecated and replaced by frame ancestors, we drop the header as recommendation.
* Improvement: save and continue in vulnerabilities overview not working correctly

= 8.3.0.1 =
* Fix: Issues with the decryption model

= 8.3.0 =
* August 12th, 2024
* Feature: Password security scan. This feature scans your users for weak passwords, and allows you to enforce non-compromised passwords.
* Fix: Fixed some strings that were not translatable. This has been resolved.
* Fix: Premium support link did not work. Now links to the correct page.
* Improvement: Disable the cron schedules on deactivation.
* Fix: Links in emails were sometimes not correct. This has been fixed.
* Fix: Fatal error on permission detection. This has been resolved.
* Improvement: Custom header for the license checks for better compatibility with some hosting environments.
* Improvement: Added option to disable X-powered-by header.
* Improvement: New improved encryption method for some settings.

= 8.1.5 =
* June 21th, 2024
* Fix: documentation links to website broken
* Improvement: some text changes in helptexts
* Improvement: new structure to upgrade database tables

= 8.1.4 =
* June 11th, 2024
* Improvement: dropdown in onboarding not entirely visible
* Improvement: Styling of locked XML RPC overview
* Fix: Not loading cookie expiration change
* Fix: Visual Composer compatibility icw Enforce Strong Password
* Fix: Multiple CloudFlare detected notices in onboarding
* Fix: Checkbox position in onboarding

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
1. The Really Simple Security Dashboard provides a quick security overview.
2. Enable or enforce 2FA per user role.
3. Stay ahead of plugin, theme and WP core vulnerabilities.
4. Harden your site’s security with Basic Hardening features.
5. 1-minute configuration with the short security onboarding.