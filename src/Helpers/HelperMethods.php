<?php
namespace WScore\Repository\Helpers;

use InvalidArgumentException;
use WScore\Repository\Entity\EntityInterface;

class HelperMethods
{
    /**
     * @param array $input
     * @param array $keys
     * @return array
     */
    public static function filterDataByKeys(array $input, array $keys)
    {
        if (empty($keys)) {
            return $input;
        }
        $output = [];
        foreach($keys as $key) {
            if (array_key_exists($key, $input)) {
                $output[$key] = $input[$key];
            }
        }
        return $output;
    }

    /**
     * @param array $input
     * @param array $keys
     * @return array
     */
    public static function removeDataByKeys(array $input, array $keys)
    {
        foreach($keys as $key) {
            if (array_key_exists($key, $input)) {
                unset($input[$key]);
            }
        }
        return $input;
    }

    /**
     * @param array $input
     * @param array $convert
     * @return array
     */
    public static function convertDataKeys(array $input, array $convert)
    {
        foreach($convert as $key => $col) {
            if (array_key_exists($key, $input)) {
                $input[$col] = $input[$key];
                if ($key !== $col ) {
                    unset($input[$key]);
                }
            }
        }
        return $input;
    }

    /**
     * @param string          $value
     * @param string|callable $class
     * @return mixed
     */
    public static function convertToObject($value, $class)
    {
        if (!$class) {
            return $value;
        }
        if (is_callable($class)) {
            return $class($value);
        }
        if (is_string($class) && class_exists($class)) {
            return new $class($value);
        }
        throw new InvalidArgumentException('cannot convert to object');
    }

    /**
     * @param EntityInterface $entity
     * @param array           $convert
     * @return string
     */
    public static function flatKey(EntityInterface $entity, array $convert = [])
    {
        if (empty($convert)) {
            return self::flattenKey($entity->getKeys());
        }
        $keys = HelperMethods::filterDataByKeys($entity->toArray(), $convert);
        return HelperMethods::flattenKey($keys);
    }

    /**
     * @param array $keys
     * @return string
     */
    public static function flattenKey(array $keys)
    {
        $flat = [];
        foreach($keys as $key => $val) {
            $flat[] = "{$key}:{$val}";
        }
        return implode("\t", $flat);
    }
}