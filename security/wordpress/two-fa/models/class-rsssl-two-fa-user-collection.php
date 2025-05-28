<?php

namespace RSSSL\Security\WordPress\Two_Fa\Models;

class Rsssl_Two_Fa_User_Collection
{

    /**
     * An array to hold TwoFaUser objects.
     *
     * @var Rsssl_Two_FA_user[]
     */
    private array $users = [];

    /**
     * The total number of records (useful for pagination).
     *
     * @var int
     */
    private int $totalRecords = 0;

    /**
     * Add a TwoFaUser to the collection.
     */
    public function add(Rsssl_Two_FA_user $user): void
    {
        $this->users[] = $user;
    }

    /**
     * Retrieve all TwoFaUser objects in the collection.
     *
     * @return Rsssl_Two_FA_user[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * Set the total number of records.
     */
    public function setTotalRecords(int $totalRecords): void
    {
        $this->totalRecords = $totalRecords;
    }

    /**
     * Get the total number of records.
     *
     * @return int
     */
    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }
}