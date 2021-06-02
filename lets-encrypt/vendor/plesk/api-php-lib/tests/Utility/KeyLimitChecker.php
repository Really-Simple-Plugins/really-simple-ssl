<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskXTest\Utility;

class KeyLimitChecker
{
    const LIMIT_CLIENTS = 'limit_clients';
    const LIMIT_RESELLERS = 'limit_resellers';
    const LIMIT_DOMAINS = 'limit_domains';

    /**
     * Checks whether limit is within the required constraint.
     *
     * @param (string|int)[] $keyInfo  Structure returned by the getKeyInfo call
     * @param string $type             Type of the object that should be checked
     * @param int $minimalRequirement  Minimal value that should satisfy the limit
     *
     * @return bool  if license satisfies set limits
     */
    public static function checkByType(array $keyInfo, $type, $minimalRequirement)
    {
        $field = null;
        switch ($type) {
            case self::LIMIT_CLIENTS:
                if (intval($keyInfo['can-manage-customers']) === 0) {
                    return false;
                }
                $field = 'lim_cl';
                break;
            case self::LIMIT_RESELLERS:
                if (intval($keyInfo['can-manage-resellers']) === 0) {
                    return false;
                }
                $field = 'lim_cl';
                break;
            case self::LIMIT_DOMAINS:
                $field = 'lim_dom';
                break;
            default:
                return false;
        }

        return intval($keyInfo[$field]) === -1 || intval($keyInfo[$field]) > $minimalRequirement;
    }
}
