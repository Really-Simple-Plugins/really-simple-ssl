<?php
namespace RSSSL\Security\WordPress\Two_Fa\Controllers;

use RSSSL\Security\WordPress\Two_Fa\Contracts\Rsssl_Two_Fa_User_Repository_Interface;
use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_FA_Data_Parameters;

class Rsssl_Two_Fa_User_Controller {

    private Rsssl_Two_Fa_User_Repository_Interface $userRepository;

    /**
     * Rsssl_Two_Fa_User_Controller constructor.
     */
    public function __construct(
        Rsssl_Two_Fa_User_Repository_Interface $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    /**
     * Get users for the admin overview.
     *
     * @return array
     */
    public function getUsersForAdminOverview(Rsssl_Two_FA_Data_Parameters $params): array {
        $userCollection = $this->userRepository->getTwoFaUsers($params);
        $data = [];
        $negative_count = $params->negative_count;
        foreach ($userCollection->getUsers() as $twoFaUser) {
            if (empty($twoFaUser->getRoles())) {
                $negative_count++;
                continue;
            }
            // Directly use the domain object's getters.
            $data[] = [
                'ID'                     => $twoFaUser->getId(),
                'user'                   => $twoFaUser->getUsername(),
                'status_for_user'        => $twoFaUser->getStatus(),
                'rsssl_two_fa_providers' => $twoFaUser->getProvider(),
                'user_role'              => $twoFaUser->getRoles(),
                'can_reset'              => $twoFaUser->isStatusResettable(),
            ];
        }

        return [
            'request_success' => true,
            'data'            => $data,
            'totalRecords'    => $userCollection->getTotalRecords() - $negative_count,
            'offset'          => $params->offset,
            'number'          => $params->number,
            'negative_count'  => $negative_count,
        ];
    }
}