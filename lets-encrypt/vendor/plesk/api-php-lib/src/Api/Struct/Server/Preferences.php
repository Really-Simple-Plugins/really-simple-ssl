<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api\Struct\Server;

class Preferences extends \PleskX\Api\Struct
{
    /** @var int */
    public $statTtl;

    /** @var int */
    public $trafficAccounting;

    /** @var int */
    public $restartApacheInterval;

    public function __construct($apiResponse)
    {
        $this->_initScalarProperties($apiResponse, [
            'stat_ttl',
            'traffic_accounting',
            'restart_apache_interval',
        ]);
    }
}
