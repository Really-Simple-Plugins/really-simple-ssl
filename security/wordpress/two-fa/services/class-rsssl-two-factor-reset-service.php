<?php
namespace RSSSL\Security\WordPress\Two_Fa\Services;

use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Repository_Interface;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Status;

// Assuming this provides delete_two_fa_meta()

class Rsssl_Two_Factor_Reset_Service {

    private Rsssl_Two_Fa_User_Repository_Interface $userRepository;

    /**
     * Inject the repository and hook the batched process callback.
     */
    public function __construct(Rsssl_Two_Fa_User_Repository_Interface $userRepository)
    {
        $this->userRepository = $userRepository;
        // Hook the WP action for processing batches
        add_action('rsssl_process_batched_users', [$this, 'batchedProcess'], 10, 3);
    }

    /**
     * Kick off the reset fix process.
     *
     * This method creates a parameters object that signals that we only want expired users,
     * then queries the repository for the count of expired users. If any are found, it schedules
     * a WP event to process them in batches.
     *
     * @return void
     */
    public function resetFix(): void
    {
        //Building base params
        $params = new Rsssl_Two_FA_Data_Parameters([
            'filter_column' => 'user_role',
            'filter_value'  => 'all',
        ]);

        // no need to run if there are no forced roles
        if (empty($params->getForcedRoles())) {
            update_option('rsssl_reset_fix', false, false);
            return;
        }
        $params->setNumber(1000); //Setting the batch size
        $expired_users = $this->userRepository->geTwoFAExpiredUsers($params);
        if ($expired_users->getTotalRecords() > 0) {
            wp_schedule_single_event(time() + 20, 'rsssl_process_batched_users', [$expired_users->getUsers(), $expired_users->getTotalRecords(), $params->number]);
        } else {
            update_option('rsssl_reset_fix', false, false);
        }

    }

    /**
     * Process expired users in batches.
     *
     * This method is called via the scheduled WP event. It uses the repository to fetch
     * a batch of expired users (based on the passed-in parameters) and resets the two-factor
     * status on each user.
     *
     * @return void
     */
    public function batchedProcess(Rsssl_Two_FA_Data_Parameters $params, int $user_count, int $batch_size = 500): void
    {
        // Loop until all expired users have been processed.
        while ($user_count > 0) {
            $params->number = $batch_size;
            // Fetch a batch of users via the repository.
            $usersCollection = $this->userRepository->getTwoFaUsers($params);
            foreach ($usersCollection->getUsers() as $twoFaUser) {
                // Delete the two-factor meta for the user.
                Rsssl_Two_Fa_Status::delete_two_fa_meta($twoFaUser->getId());
                // Update the last login meta so that the user is forced to re‑authenticate with 2FA.
                update_user_meta($twoFaUser->getId(), 'rsssl_two_fa_last_login', gmdate('Y-m-d H:i:s'));
            }
            $user_count -= $batch_size;
        }
    }
}