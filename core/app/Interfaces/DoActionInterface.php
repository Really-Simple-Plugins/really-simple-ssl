<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Interfaces;

use ReallySimplePlugins\RSS\Core\Features\Vulnerability\Controllers\VulnerabilityDataController;

interface DoActionInterface
{
    /**
     * Implement this method to handle custom actions triggered via the
     * existing `rsssl_do_action` mechanism.
     *
     * This interface allows new code to hook into the same action-dispatching
     * flow that is already used elsewhere in the plugin, without duplicating
     * or reimplementing that logic.
     *
     * The method is responsible for inspecting the given `$action` and `$data`,
     * performing the appropriate operation, and returning a modified `$response`
     * array.
     *
     * @param array  $response The response data that should be returned to the caller.
     * @param string $action   The action identifier that determines what logic to execute.
     * @param mixed  $data     Additional payload associated with the action.
     *
     * @return array The updated response array after the action has been handled.
     */
    public function rssslDoAction(array $response, string $action, $data): array;
}
