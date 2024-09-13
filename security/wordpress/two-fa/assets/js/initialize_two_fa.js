/**
 * The Global rsssl_onboard object is defined in the PHP file that enqueues this script.
 * @global rsssl_onboard
 * It contains the following properties:
 * @typedef {Object} rsssl_onboard
 * @property {string} root - The root URL of the site.
 * @property {string} redirect_to - The URL to redirect to after the onboarding process is complete.
 * @property {string} user_id - The ID of the user.
 * @property {string} login_nonce - The nonce for the login.
 * @property {string} totp_data - The data for the TOTP.
 * @property {string} totp_data.totp_url - The URL for the TOTP.
 * @property {string} totp_data.backup_codes - The backup codes for the TOTP.
 * @property {string} totp_data.key - The key for the TOTP.
 * @property {string} totp_data.authcode - The authcode for the TOTP.
 * @property {string} totp_data.provider - The provider for the TOTP.
 * @property {string} totp_data.redirect_to - The URL to redirect to after the TOTP process is complete.
 */

/**
 * The Global rsssl_profile object is defined in the PHP file that enqueues this script.
 * @global rsssl_profile
 * It contains the following properties:
 * @typedef {Object} rsssl_profile
 * @property {string} root - The root URL of the site.
 * @property {string} redirect_to - The URL to redirect to after the profile process is complete.
 * @property {string} user_id - The ID of the user.
 * @property {string} login_nonce - The nonce for the login.
 * @property {string} totp_data - The data for the TOTP.
 * @property {string} totp_data.totp_url - The URL for the TOTP.
 * @property {string} totp_data.backup_codes - The backup codes for the TOTP.
 * @property {string} totp_data.key - The key for the TOTP.
 * @property {string} totp_data.authcode - The authcode for the TOTP.
 * @property {string} totp_data.provider - The provider for the TOTP.
 * @property {string} totp_data.redirect_to - The URL to redirect to after the TOTP process is complete.
 * @property {string} totp_data.email - The email for the TOTP.
 * @property {array} translatables - The translatable strings for the profile.
 * @property {string} translatables.keyCopied - The message to display when the key is copied.
 * @property {string} translatables.keyCopiedFailed - The error message to display.
 */

window.onload = function() {
    if(typeof rsssl_onboard !== 'undefined') {
        let onboarding = new Onboarding(rsssl_onboard.root, rsssl_onboard);
        onboarding.init();
    }

    if (typeof rsssl_profile !== 'undefined') {
        let profile = new Profile(rsssl_profile.root, rsssl_profile);
        profile.init();
    }
}