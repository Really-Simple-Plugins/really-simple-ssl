<?php
// Copyright 1999-2020. Plesk International GmbH.
// Author: Frederic Leclercq

namespace PleskX\Api\Struct\Webspace;

class DiskUsage extends \PleskX\Api\Struct
{
    /** @var int */
    public $httpdocs;

    /** @var int */
    public $httpsdocs;

    /** @var int */
    public $subdomains;

    /** @var int */
    public $anonftp;

    /** @var int */
    public $logs;

    /** @var int */
    public $dbases;

    /** @var int */
    public $mailboxes;

    /** @var int */
    public $maillists;

    /** @var int */
    public $domaindumps;

    /** @var int */
    public $configs;

    /** @var int */
    public $chroot;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'httpdocs',
            'httpsdocs',
            'subdomains',
            'anonftp',
            'logs',
            'dbases',
            'mailboxes',
            'maillists',
            'domaindumps',
            'configs',
            'chroot',
        ]);
    }
}
