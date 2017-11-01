<?php
namespace think;

use think\sms\supplier;

/**
 * Class Sms
 * @package think\Sms
 *
 * @method static template()
 * @method static applyTemplate($name, $content)
 * @method static deleteTemplate($templateCode);
 * @method static message($mobile,$templateCode,$data);
 */
class Sms
{
    /** @var $supplier */
    protected static $supplier;
    protected static $studlyCache = [];

    private static function buildSupplier()
    {
        $options = Config::get('sms');
        $type    = !empty($options['type']) ? $options['type'] : 'baidu';

        if (!isset(self::$supplier)) {

            $class = false !== strpos($type, '\\') ? $type : '\\think\\sms\supplier\\' . self::studly($type);

            self::$supplier = new $class($options);
        }
        return self::$supplier;
    }
    /**
     * 下划线转驼峰(首字母大写)
     *
     * @param  string $value
     * @return string
     */
    public static function studly($value)
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([self::buildSupplier(), $name], $arguments);
    }
}
