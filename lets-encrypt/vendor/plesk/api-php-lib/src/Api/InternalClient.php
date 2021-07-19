<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api;
defined('ABSPATH') or die();
/**
 * Internal client for Plesk XML-RPC API (via SDK).
 */
class InternalClient extends Client
{
    public function __construct()
    {
        parent::__construct('localhost', 0, 'sdk');
    }

    /**
     * Setup login to execute requests under certain user.
     *
     * @param $login
     */
    public function setLogin($login)
    {
        $this->_login = $login;
    }
}
