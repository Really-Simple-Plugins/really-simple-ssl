<?php
namespace RSSSL\Security\WordPress\Two_Fa\Contracts;

use RSSSL\Security\WordPress\Two_Fa\Models\Rsssl_Two_Fa_User_Collection;

interface Rsssl_Has_Processing_Interface
{
    /**
     * Processes a collection of Data Transfer Objects.
     * @return Rsssl_Two_Fa_User_Collection
     */
    public function processBatch(array $args, string $switchValue): Rsssl_Two_Fa_User_Collection;
}