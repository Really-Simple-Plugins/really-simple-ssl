<?php

namespace RSSSL\Security\WordPress\Two_Fa\Models;

use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Fa_Status;
use RSSSL\Security\WordPress\Two_Fa\Services\Rsssl_Two_Fa_Status_Service;

class Rsssl_Two_FA_user
{
    private int $id;
    private string $username;
    private string $status;
    private string $provider;
    private array $roles;

    private bool $canResetStatus;

    public function __construct(int $id, string $username, string $status, string $provider, array $roles)
    {
        $this->id = $id;
        $this->username = $username;
        $this->status = $status;
        $this->provider = $provider;
        $this->roles = $roles;
        $this->canResetStatus = $this->isStatusResettable();
    }

    // Getter methods

    /**
     * Get the user ID.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the username.
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the provider.
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get the roles.
     *
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Checks if the status is resettable.
     *
     * @return bool
     */
    public function isStatusResettable(): bool
    {
        // array of statuses that can be reset
        $resettableStatuses = ['expired', 'disabled', 'active'];
        // if the status is in the array, return true or false.
        return $this->canResetStatus = in_array($this->status, $resettableStatuses);
    }

    /**
     * Resets te status of the user.
     */
    public function resetStatus(): void
    {
        Rsssl_Two_Fa_Status::delete_two_fa_meta( $this->id );
        // Set the rsssl_two_fa_last_login to now, so the user will be forced to use 2fa.
        update_user_meta( $this->id, 'rsssl_two_fa_last_login', gmdate( 'Y-m-d H:i:s' ) );
    }
}