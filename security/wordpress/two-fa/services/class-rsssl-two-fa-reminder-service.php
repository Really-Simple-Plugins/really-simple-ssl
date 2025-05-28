<?php
namespace RSSSL\Security\WordPress\Two_Fa\Services;

use rsssl_mailer;
use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Repository_Interface;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Fa_User_Collection;
use RSSSL\Security\WordPress\Two_Fa\Repositories\Rsssl_Two_Fa_User_Repository;

class Rsssl_Two_Fa_Reminder_Service {

    private Rsssl_Two_Fa_User_Repository_Interface $userRepository;

    /**
     * Constructor.
     *
     * Here we hook our process method to a custom WP action.
     */
    public function __construct() {
        $this->userRepository = new Rsssl_Two_Fa_User_Repository();
        add_action('rsssl_process_two_fa_reminders', [$this, 'processReminders']);
    }

    /**
     * Kick off the reminder e‑mail process.
     *
     * Checks whether forced roles are set and then schedules a WP event
     * to process the reminder emails in a separate request.
     *
     * @return bool
     */
    public function maybeSendReminderEmails(array $forcedRoles):bool
    {
        // If no forced roles have been defined, there is nothing to do.
        if (empty($forcedRoles)) {
            update_option('rsssl_two_fa_reminder_pending', false, false);
            return false;
        }

        // Checking if there are users within the grace period who have not yet configured 2FA.
        $params = new Rsssl_Two_FA_Data_Parameters([
            'filter_column' => 'user_role',
            'filter_value'  => 'all',
        ]);
        $users = $this->userRepository->getForcedTwoFaUsersWithOpenStatus($params);

        $this->processReminders($users);

        // no further processing is pending. So we return true.
        return true;
    }

    /**
     * Process and send reminder emails to users who have not yet configured 2FA.
     *
     * This method is hooked to a WP action and is executed via a scheduled event.
     *
     * @return void
     */
    public function processReminders(Rsssl_Two_Fa_User_Collection $collection ): void
    {
        // if the collection has no users, there is nothing to do.
        if ($collection->getTotalRecords() === 0) {
            return;
        }

        // Preparing the reminder e‑mail.
        // Load the mailer class if it hasn't been loaded yet.
        if (!class_exists('rsssl_mailer')) {
            require_once rsssl_path . 'mailer/class-mail.php';
        }

        // Build the e‑mail subject.
        $subject = __("Important security notice", "really-simple-ssl");

        // Determine the login URL – use the custom login URL if one is set.
        $login_url = wp_login_url();
        if (function_exists('rsssl_get_option') && rsssl_get_option('change_login_url_enabled') !== false && !empty(rsssl_get_option('change_login_url'))) {
            $login_url = trailingslashit(site_url()) . rsssl_get_option('change_login_url');
        }

        // Build a login link.
        $login_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url($login_url),
            __('Please login', 'really-simple-ssl')
        );

        $message = sprintf(
        /* translators:
        1: Site URL.
        */
            __("You are receiving this email because you have an account registered at %s.", "really-simple-ssl"),
            site_url(),
        );
        $message .= "<br><br>";
        $message .= sprintf(
        /* translators:
        1: Login link with the text "Please login".
        2: Opening <strong> tag to emphasize the "within three days" text.
        3: Closing </strong> tag for "within three days".
        4: Opening <strong> tag to emphasize "you will be unable to login".
        5 Closing </strong> tag for "you will be unable to login".
        */

            __("The site's security policy requires you to configure Two-Factor Authentication to protect against account theft. %1\$s and configure Two-Factor authentication %2\$swithin three days%3\$s. If you haven't performed the configuration by then, %4\$syou will be unable to login%5\$s.", "really-simple-ssl"),
            $login_link,
            '<strong>',
            '</strong>',
            '<strong>',
            '</strong>'
        );

        $mailer                    = new rsssl_mailer();
        $mailer->subject           = $subject;
        $mailer->branded           = false;
        $mailer->sent_by_text      = "<b>".sprintf( __( 'Notification by %s', 'really-simple-ssl' ), site_url() )."</b>";
        $mailer->message           = $message;


        // Process each user in the grace period who still needs to set up 2FA.
        foreach ($collection->getUsers() as $user) {
            // Skip if the reminder has already been sent.
            $two_fa_reminder_sent = get_user_meta($user->getId(), 'rsssl_two_fa_reminder_sent', true);
            if ($two_fa_reminder_sent) {
                continue;
            }

            $email = get_userdata($user->getId())->user_email;
            $first_name = get_userdata($user->getId())->first_name;
            $last_name = get_userdata($user->getId())->last_name;

            // Prepare and send the e‑mail.
            $mailer->template_filename = apply_filters('rsssl_email_template', rsssl_path . '/mailer/templates/email-unbranded.html');
            $mailer->to = $email;
            $mailer->title = sprintf(
                /* translators:
                   %s: First name.
                   %s: Last name.
                */
                    __('Hi %s %s', 'really-simple-ssl'),
                    trim($first_name),
                    trim($last_name)
                ) . ',';
            $mailer->message = $message;
            $mailer->send_mail();

            // Mark that the reminder e‑mail has been sent for this user.
            update_user_meta($user->getId(), 'rsssl_two_fa_reminder_sent', true);
        }

        // Optionally, update a flag to indicate that no further processing is pending.
        update_option('rsssl_two_fa_reminder_pending', false, false);
    }
}