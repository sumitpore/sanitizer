<?php

namespace Rees\Sanitizer;

class ArrayDot
{
    /**
     * Checks whether the array is multidimensional or not
     *
     * @param array $array Array to be checked
     * @return boolean
     */
    public static function isMultiDimensionalArray($array)
    {
        rsort($array);
        return isset($array[0]) && is_array($array[0]);
    }


    /**
     * Collapse an array of arrays into a single array.
     * Adapted from: https://github.com/illuminate/support/blob/5.8/Arr.php#L47
     * @param  array  $array
     * @return array
     */
    public static function collapse($array)
    {
        if (!static::isMultiDimensionalArray($array)) {
            return $array;
        }

        $results = [];
        foreach ($array as $values) {
            if (! is_array($values)) {
                continue;
            }
            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Resolves wild card key to possible keys and returns array of those possible keys
     *
     * @param array $array Array in which keys to be found out
     * @param string $key Wildcard Key passed in "dot" format
     * @param string $prefix Prefix to be applied on found keys
     * @return array
     */
    public static function resolveWildcardKey($array, $key = null, $prefix = '')
    {
        $prefixSegments = [];

        // If no key is passed or * is the only string passed to the key, then
        // convert array to dot array & return keys from found in dot array
        if (empty($key) || (is_string($key) && '*' == $key)) {
            return array_map(
                function ($_key) use ($prefix) {
                    return !empty($prefix) ? $prefix . '.' . $_key : $_key;
                },
                array_keys(array_dot($array))
            );
        }

        // If key ends with '.', do not allow to proceed.
        if (is_string($key) && substr($key, -1) == '.') {
            throw new \InvalidArgumentException('Key can not end with `.`');
        }

        // If key does not contain '*', then check if the passed key exists in the
        // array or not. If it exists, then return the key, else return blank []
        if (is_string($key) && strpos($key, '*') === false) {
            $finalPrefix = !empty($prefix) ? $prefix . '.' . $key : $key;
            return array_has($array, $finalPrefix) ? [$key] : [];
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                $result = [];
               
                foreach ($array as $internalKey => $item) {
                    if (!is_array($item)) {
                        $result[] = implode(
                            '.',
                            array_filter(array_merge([$prefix], $prefixSegments, [$internalKey]), 'strlen')
                        );
                        continue;
                    }

                    $result[] = static::resolveWildcardKey(
                        $item,
                        $key,
                        implode(
                            '.',
                            array_filter(array_merge([$prefix], $prefixSegments, [$internalKey]), 'strlen')
                        )
                    );
                }

                return static::collapse($result);
            }

            // Invalid key is passed
            if (is_array($array) && ! array_key_exists($segment, $array)) {
                return [];
            }

            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
                $prefixSegments[] = $segment;
            }
        }

        $combinedPrefixSegments = implode('.', $prefixSegments);
        return !empty($prefix) ? [$prefix . '.' . $combinedPrefixSegments] : [$combinedPrefixSegments];
    }
}
