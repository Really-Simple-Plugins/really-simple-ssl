<?php

namespace  ReallySimplePlugins\RSS\Core\Support\Helpers;

use Adbar\Dot;

/**
 * Wrapper for easy access to storage data. Create a new instance with an array
 * of data in the constructor. Now all data can be accessed using Dot notation.
 *
 * @usage $storage = new Storage(['key' => 'value']);
 * @usage $storage->get('key', 'default');
 */
class Storage extends Dot
{
    /**
     * Returns the parameter keys.
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * Returns the sanitized string of the parameter value.
     * @uses sanitize_text_field()
     */
    public function getString(string $key, string $default = '', bool $trim = false): string
    {
        $value = sanitize_text_field($this->get($key, $default));
        return $trim ? trim($value) : $value;
    }

    /**
     * Returns the sanitized string of the parameter value.
     * @uses sanitize_textarea_field()
     */
    public function getTextarea(string $key, string $default = '', bool $trim = false): string
    {
        $value = sanitize_textarea_field($this->get($key, $default));
        return $trim ? trim($value) : $value;
    }

    /**
     * Strips out all characters that are not allowable in an email and returns
     * the value.
     * @uses sanitize_email()
     */
    public function getEmail(string $key, string $default = ''): string
    {
        return sanitize_email($this->get($key, $default));
    }

    /**
     * Returns the parameter value as a slug.
     * @uses sanitize_title
     */
    public function getTitle(string $key, string $default = ''): string
    {
        return sanitize_title($this->get($key, $default));
    }

    /**
     * Sanitizes content for allowed HTML tags for post content.
     * @uses wp_kses_post()
     */
    public function getPost(string $key, string $default = ''): string
    {
        return wp_kses_post($this->get($key, $default));
    }

    /**
     * Returns a sanitized URL.
     * @uses sanitize_url()
     */
    public function getUrl(string $key, string $default = ''): string
    {
        return sanitize_url($this->get($key, $default));
    }

    /**
     * Keys are used as internal identifiers. Lowercase alphanumeric characters,
     * dashes, and underscores are allowed.
     * @uses sanitize_key()
     */
    public function getKey(string $key, string $default = ''): string
    {
        return sanitize_key($this->get($key, $default));
    }

    /**
     * Returns the alphabetic characters of the parameter value.
     */
    public function getAlpha(string $key, string $default = ''): string
    {
        return preg_replace('/[^[:alpha:]]/', '', $this->get($key, $default));
    }

    /**
     * Returns the alphabetic characters of the parameter value. With spaces.
     */
    public function getAlphaSpace(string $key, string $default = ''): string
    {
        return preg_replace('/[^[:alpha:] ]/', '', $this->get($key, $default));
    }

    /**
     * Returns the alphabetic characters and digits of the parameter value.
     */
    public function getAlnum(string $key, string $default = ''): string
    {
        return preg_replace('/[^[:alnum:]]/', '', $this->get($key, $default));
    }

    /**
     * Returns the digits of the parameter value.
     *
     * @param string $default The default value runs through
     * FILTER_SANITIZE_NUMBER_INT as well
     */
    public function getDigits(string $key, string $default = ''): string
    {
        // we need to remove - and + because they're still allowed by the filter
        return str_replace(['-', '+'], '', $this->filter($key, $default, FILTER_SANITIZE_NUMBER_INT));
    }

    /**
     * Returns the parameter value typecast as integer.
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Returns the parameter value typecast as float.
     */
    public function getFloat(string $key, $default = 0): float
    {
        return (float) $this->get($key, $default);
    }

    /**
     * Returns the parameter value filtered as a boolean. Uses flag:
     * FILTER_VALIDATE_BOOLEAN
     */
    public function getBoolean(string $key, $default = false): bool
    {
        return $this->filter($key, $default, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns the parameter value validated as a 2 character country code. If
     * the preg_match for exactly two alphabetic characters fails, the default
     * value is returned.
     */
    public function getCountryCode(string $key, string $default = ''): string
    {
        $country = strtoupper(trim($this->get($key, $default)));
        if (preg_match('/^[a-z]{2}$/i', $country)) {
            return $country;
        }

        return $default;
    }

    /**
     * Returns a boolean if the value is considered not empty.
     * @param array<TKey>|int|string|null $keys
     */
    public function isNotEmpty($keys = null): bool
    {
        return $this->isEmpty($keys) === false;
    }

    /**
     * Returns a boolean if the value of one of the keys is considered empty.
     * @param array<TKey>|int|string|null $keys
     */
    public function isOneEmpty($keys = []): bool
    {
        foreach ($keys as $key) {
            if ($this->isEmpty($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter key.
     * @return mixed
     * @see http://php.net/manual/en/function.filter-var.php
     */
    public function filter(string $key, $default = null, int $filter = FILTER_DEFAULT, $options = [])
    {
        $value = $this->get($key, $default);
        // Always turn $options into an array - this allows filter_var option shortcuts.
        if (!\is_array($options) && $options) {
            $options = ['flags' => $options];
        }
        // Add a convenience check for arrays.
        if (\is_array($value) && !isset($options['flags'])) {
            $options['flags'] = FILTER_REQUIRE_ARRAY;
        }

        return filter_var($value, $filter, $options);
    }
}