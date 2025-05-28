<?php

namespace RSSSL\Security\WordPress\Two_Fa\Traits;

trait Rsssl_Args_Builder
{
    /**
     * Builds the arguments array for REST API routes.
     *
     * @param array $properties The properties to include in the arguments array.
     * @param array $optional_properties The properties to include as optional in the arguments array.
     * @return array The built arguments array.
     */
    public function build_args(array $properties, array $optional_properties = array()): array
    {
        $args = array();
        foreach ($properties as $property) {
            $args[$property] = array(
                'required' => true,
                'type' => $this->get_property_type($property),
            );
        }
        foreach ($optional_properties as $property) {
            $args[$property] = array(
                'required' => false,
                'type' => $this->get_property_type($property),
            );
        }
        return $args;
    }

    /**
     * Determines the type of a property.
     *
     * @param string $property The property name.
     * @return string The type of the property.
     */
    private function get_property_type(string $property): string
    {
        $types = array(
            'provider' => 'string',
            'user_id' => 'integer',
            'login_nonce' => 'string',
            'redirect_to' => 'string',
            'two-factor-totp-authcode' => 'string',
            'key' => 'string',
        );

        return $types[$property] ?? 'string';
    }
}