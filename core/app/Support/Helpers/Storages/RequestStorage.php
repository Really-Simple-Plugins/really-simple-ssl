<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Support\Helpers\Storages;

use ReallySimplePlugins\RSS\Core\Support\Helpers\Storage;

/**
 * Request storage helper used in DI container.
 */
final class RequestStorage extends Storage
{
    public function __construct()
    {
        $body = $this->getRequestBody();

        parent::__construct([
            'global' => $_REQUEST,
            'files' => $_FILES,
            'body' => $body,
        ]);
    }

    private function getRequestBody(): array
    {
        $body = [];
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }
        return $body;
    }
}
