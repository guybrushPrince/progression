<?php
trait DefaultPermanentObjectTrait {

    private const DB_LOCATION = __DIR__ . '/../../_db';
    private const OBJ = '_object';
    private const LINK_ID = '__key';
    private const LINK_CLASS = '__class';

    /**
     * A simple internal cache of objects to speed up the engine.
     * @var APermanentObject[]
     */
    private static array $_objectCache = [];

    /**
     * The cache is dirty if an object was created.
     * @var string[][]
     */
    private static array $_dirty = [];

    /**
     * Initialize the default db.
     * @return bool
     */
    public static function init() : bool {
        if (!file_exists(self::DB_LOCATION)) {
            return mkdir(self::DB_LOCATION, 0777, true);
        }
        return true;
    }

    /**
     * Get the internal id of an object.
     * @param int|string $id The id.
     * @param string $class The class.
     * @return string
     */
    private static function getObjectId(int|string $id, string $class) : string {
        return $class . '-' . $id;
    }

    /**
     * Get the database file where the object is stored in.
     * @param int|string $id The id.
     * @param string $class The class.
     * @return string
     */
    private static function getDBFile(int|string $id, string $class) : string {
        return self::getDBFolder($class) . self::getObjectId($id, $class);
    }

    /**
     * Get the database folder where the object is stored in.
     * @param string $class The class.
     * @return string
     */
    private static function getDBFolder(string $class) : string {
        $folder = self::DB_LOCATION . '/' . $class . '/';
        if (!array_key_exists($folder, self::$_dirty) && !file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        return $folder;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public static function getPermanentObject(int|string $id, string $class, array &$context = []) : ?IProgressionSerializable {
        $objId = self::getObjectId($id, $class);
        if (array_key_exists($objId, $context)) return $context[$objId];
        if (array_key_exists($objId, self::$_objectCache)) return self::$_objectCache[$objId];
        $file = self::getDBFile($id, $class);
        if (file_exists($file)) {
            $obj = $class::__intern_deserialize(CPTools::jsonDecode(file_get_contents($file)), $context);
            self::$_objectCache[$objId] = $obj;
            return $obj;
        } else return null;
    }

    /**
     * @inheritDoc
     */
    public function createPermanentObject(array &$context = []) : bool {
        if (is_null($this->getPermanentId())) $this->id = self::nextKey();
        $objId = self::getObjectId($this->getPermanentId(), $this->getPermanentClass());
        self::$_objectCache[$objId] = $this;
        $folder = self::getDBFolder($this->getPermanentClass());
        if (array_key_exists($folder, self::$_dirty)) self::$_dirty[$folder][] = $objId;
        $file = self::getDBFile($this->getPermanentId(), $this->getPermanentClass());
        return file_put_contents($file, CPTools::jsonEncode($this->__intern_serialize($context)));
    }

    /**
     * @inheritDoc
     */
    public function updatePermanentObject() : bool {
        $file = self::getDBFile($this->getPermanentId(), $this->getPermanentClass());
        return file_put_contents($file, CPTools::jsonEncode($this->__intern_serialize()));
    }

    /**
     * @inheritDoc
     */
    public function deletePermanentObject() : bool {
        $file = self::getDBFile($this->getPermanentId(), $this->getPermanentClass());
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function getAllPermanentObjects(string $class, array &$context = []) : array {
        $folder = self::getDBFolder($class);
        if (array_key_exists($folder, self::$_dirty)) {
            return array_map(function (string $objId) {
                return self::$_objectCache[$objId];
            }, self::$_dirty[$folder]);
        }
        if (file_exists($folder)) {
            $files = array_filter(scandir($folder), function (string $f) {
                return !($f === '.' || $f === '..');
            });
            self::$_dirty[$folder] = $files;
            return array_map(function (string $f) use ($class, $folder, &$context) {
                if (array_key_exists($f, self::$_objectCache)) return self::$_objectCache[$f];
                $obj = $class::__intern_deserialize(CPTools::jsonDecode(file_get_contents($folder . $f)), $context);
                self::$_objectCache[$f] = $obj;
                return $obj;
            }, $files);
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getPermanentObjectsWhere(string $field, mixed $value, string $class,
                                                    ?int $limit = null, array &$context = []) : array {
        return self::getPermanentObjectsWhereAll([$field => $value], $class, $limit, $context);
    }

    /**
     * @inheritDoc
     */
    public static function getPermanentObjectsWhereAll(array $map, string $class, ?int $limit = null,
                                                       array &$context = []) : array {
        $all = self::getAllPermanentObjects($class, $context);
        return array_filter($all, function ($obj) use ($map) {
            foreach ($map as $field => $value) {
                $v = $obj->$field;
                if (!is_array($v)) $v = [ $v ];
                $v = array_map(function ($val) {
                    if ($val instanceof APermanentObject) {
                        return self::getObjectId($val->getPermanentId(), $val->getPermanentClass());
                    } else return $val;
                }, $v);
                if (!is_array($value)) $value = [ $value ];
                $value = array_map(function ($val) {
                    if ($val instanceof APermanentObject) {
                        return self::getObjectId($val->getPermanentId(), $val->getPermanentClass());
                    } else return $val;
                }, $value);
                $in = array_intersect($v, $value);
                if (count($in) === 0) return false;
            }
            return true;
        });
    }

    /**
     * Serialize property.
     * @param mixed $value The value.
     * @param array $context The context.
     * @param bool $nonviable Whether the property is nonviable (and should be created as well), or not.
     * @return mixed
     * @throws NotImplementedException
     */
    private function __serializeProperty(mixed $value, array &$context = [], bool $nonviable = false) : mixed {
        if ($value instanceof APermanentObject) {
            $objId = self::getObjectId($value->getPermanentId(), $value->getPermanentClass());
            if (!array_key_exists($objId, $context) && $nonviable) {
                $value->createPermanentObject($context);
            }
            return [
                self::LINK_ID => $value->getPermanentId(),
                self::LINK_CLASS => $value->getPermanentClass()
            ];
        } else if (is_array($value)) {
            return array_map(function (mixed $val) use ($context, $nonviable) {
                return $this->__serializeProperty($val, $context, $nonviable);
            }, $value);
        } else return $value;
    }

    /**
     * Deserialize property.
     * @param mixed $value The value.
     * @param array $context The context.
     * @return mixed
     */
    private function __deserializeProperty(mixed $value, array &$context = []) : mixed {
        if (is_array($value)) {
            if (array_key_exists(self::LINK_ID, $value) && array_key_exists(self::LINK_CLASS, $value)) {
                $clazz = $value[self::LINK_CLASS];
                $id = $value[self::LINK_ID];
                $objId = self::getObjectId($id, $clazz);
                if (array_key_exists($objId, $context)) {
                    return $context[$objId];
                } else {
                    return $clazz::getPermanentObject($id, $clazz, $context);
                }
            } else {
                return array_map(function (mixed $val) use (&$context) {
                    return $this->__deserializeProperty($val, $context);
                }, $value);
            }
        } else return $value;
    }

    /**
     * Get a random id.
     * @return int
     */
    private static function nextKey() : int {
        return rand() << 32 | rand();
    }
}
?>