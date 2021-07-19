<?php
// Copyright 1999-2020. Plesk International GmbH.

namespace PleskX\Api;
defined('ABSPATH') or die();
abstract class Struct
{
    public function __set($property, $value)
    {
        throw new \Exception("Try to set an undeclared property '$property'.");
    }

    /**
     * Initialize list of scalar properties by response.
     *
     * @param \SimpleXMLElement $apiResponse
     * @param array $properties
     *
     * @throws \Exception
     */
    protected function _initScalarProperties($apiResponse, array $properties)
    {
        foreach ($properties as $property) {
            if (is_array($property)) {
                $classPropertyName = current($property);
                $value = $apiResponse->{key($property)};
            } else {
                $classPropertyName = $this->_underToCamel(str_replace('-', '_', $property));
                $value = $apiResponse->$property;
            }

            $reflectionProperty = new \ReflectionProperty($this, $classPropertyName);
            $docBlock = $reflectionProperty->getDocComment();
            $propertyType = preg_replace('/^.+ @var ([a-z]+) .+$/', '\1', $docBlock);

            if ('string' == $propertyType) {
                $value = (string) $value;
            } elseif ('int' == $propertyType) {
                $value = (int) $value;
            } elseif ('bool' == $propertyType) {
                $value = in_array((string) $value, ['true', 'on', 'enabled']);
            } else {
                throw new \Exception("Unknown property type '$propertyType'.");
            }

            $this->$classPropertyName = $value;
        }
    }

    /**
     * Convert underscore separated words into camel case.
     *
     * @param string $under
     *
     * @return string
     */
    private function _underToCamel($under)
    {
        $under = '_'.str_replace('_', ' ', strtolower($under));

        return ltrim(str_replace(' ', '', ucwords($under)), '_');
    }
}
