<?php

/**
 * Class CPTools.
 *
 * Contains some function necessary for checking things fast.
 *
 * @package progression
 * @subpackge php/tools
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 * @version 1.0.0
 */
class CPTools {

    private const JSON_PARAMETERS = JSON_HEX_QUOT | JSON_HEX_TAG;

    /**
     * Removes an object by id from an array.
     * @param array $arr The array.
     * @param APermanentObject $obj The object.
     * @return array
     */
    public static function removeFromArray(APermanentObject $obj, array $arr) : array {
        return array_filter($arr, function(APermanentObject $a) use ($obj) {
            $aKey = $a->getPermanentId();
            $oKey = $obj->getPermanentId();
            if (is_int($aKey) && is_int($oKey) && ($aKey < 0 || $oKey < 0)) {
                $aKey = spl_object_hash($a);
                $oKey = spl_object_hash($obj);
            }
            return (get_class($a) !== get_class($obj)) || ($aKey !== $oKey);
        });
    }

    /**
     * Encodes an object.
     * @param string|integer|float|bool|array|object $object The object to encode.
     * @return false|string
     */
    public static function jsonEncode(mixed $object) : string|false {
        return json_encode($object,self::JSON_PARAMETERS);
    }

    /**
     * Decodes the json.
     * @param string $jsonString The json string.
     * @return string|integer|float|bool|array|object
     */
    public static function jsonDecode(string $jsonString) : mixed {
        return json_decode($jsonString,true);
    }
}
?>