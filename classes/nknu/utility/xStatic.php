<?php
namespace nknu\utility;
use nknu\base\xBase;
class xStatic extends xBase {
    public static function ToBase64(string $cData) : string {
        return base64_encode($cData);
    }
    public static function ToSafeBase64($cData) : string {
        $cBase64 = xStatic::ToBase64($cData);
        return xStatic::Base64ToSafeBase64($cBase64);
    }
    public static function Base64ToSafeBase64(string $cBase64) : string {
        return str_replace(array('+', '/'), array('-', '_'), $cBase64);
    }
    public static function SafeBase64ToBase64(string $cSafeBase64) : string {
        return str_replace(array('-', '_'), array('+', '/'), $cSafeBase64);
    }
    public static function SafeBase64ToString(string $cSafeBase64) : string {
        $cBase64 = xStatic::SafeBase64ToBase64($cSafeBase64);
        return xStatic::Base64ToString($cBase64);
    }
    public static function Base64ToString(string $cBase64) : string {
        return base64_decode($cBase64);
    }
    public static function ToJson($object) : string {
        return json_encode($object);
    }
    public static function ToClass(string $cJson) : \stdClass {
        return json_decode($cJson);
    }
}
