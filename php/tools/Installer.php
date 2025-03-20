<?php

/**
 * Class Installer.
 *
 * Prepares Progression in singleton mode.
 *
 * @package progression
 * @subpackge php/tools
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 * @version 1.0.0
 */
class Installer {

    /**
     * The location of the traits (for the persistent layer).
     */
    public const TRAIT_LOCATION = __DIR__ . '/../../traits/';

    /**
     * Internal constants.
     */
    private const KEY = 'key';
    private const TYPE = 'type';
    private const NONVIABLE = 'nonviable';
    private const PERSISTENT = 'persistent';

    /**
     * Installs Progression.
     * @return bool
     * @throws ReflectionException
     */
    public function install() : bool {
        $this->createDummyTraits();
        // Load the classes
        foreach ($GLOBALS['files'] as $file => $filePath) {
            $className = basename($file, ".php");
            if (starts_with_upper($className) && !str_ends_with($className, 'Trait')) {
                include_once $filePath;
                $reflectionClass = new ReflectionClass($className);

                if ($this->getAnnotation(self::PERSISTENT, $reflectionClass)) {
                    $this->createRealTrait($reflectionClass);
                }
            }
        }

        return true;
    }

    /**
     * Creates the process model and process instance of the system process model.
     * @return void
     * @throws NotImplementedException
     */
    public function createSystemProcess() : void {
        $systemEvent = new CPSystemEvent('__system_general_start_event');

        $systemProcessModel = new CPProcessModel('__system');
        $systemProcessModel->addElement($systemEvent);
        $systemProcessInstance = new ProcessInstance();
        $systemProcessInstance->setProcessModel($systemProcessModel);
        $systemProcessInstance->setState(ProcessState::RUNNING);

        SimplePersistence::instance()->startTransaction();
        $systemProcessInstance->createPermanentObject();
        SimplePersistence::instance()->endTransaction();
    }

    /**
     * Creates traits being used for the persistent layer.
     * @return void
     */
    private function createDummyTraits() : void {
        // Create some dummy traits as day are not existent yet
        if (!file_exists(self::TRAIT_LOCATION)) mkdir(self::TRAIT_LOCATION, 0777, true);

        foreach ($GLOBALS['files'] as $file) {
            $class = basename($file, ".php");
            if (starts_with_upper($class) && !str_ends_with($class, 'Trait')) {
                if (!file_exists(self::TRAIT_LOCATION . $class . ".php")) {
                    $code  = '<?php' . PHP_EOL;
                    $code .= 'trait ' . $class . 'PersistentTrait {' . PHP_EOL;
                    $code .= '}' . PHP_EOL;
                    $code .= '?>';
                    file_put_contents(self::TRAIT_LOCATION . $class . "PersistentTrait.php", $code);
                }
                include_once self::TRAIT_LOCATION . $class . "PersistentTrait.php";
            }
        }
    }

    /**
     * Creates the complete trait for the class.
     * @param ReflectionClass $class The class.
     * @return void
     */
    private function createRealTrait(ReflectionClass $class) : void {
        $code  = '<?php' . PHP_EOL;
        if ($class->implementsInterface('Graph')) {
            $code .= 'include_once __DIR__ . \'/../graph/Graph.php\';' . PHP_EOL;
        }
        if ($class->implementsInterface('GraphNode')) {
            $code .= 'include_once __DIR__ . \'/../graph/GraphPart.php\';' . PHP_EOL;
            $code .= 'include_once __DIR__ . \'/../graph/GraphNode.php\';' . PHP_EOL;
        }
        if ($class->implementsInterface('GraphEdge')) {
            $code .= 'include_once __DIR__ . \'/../graph/GraphPart.php\';' . PHP_EOL;
            $code .= 'include_once __DIR__ . \'/../graph/GraphEdge.php\';' . PHP_EOL;
        }
        $code .= 'trait ' . $class->getName() . 'PersistentTrait {' . PHP_EOL;
        $code .= '    use DefaultPermanentObjectTrait;' . PHP_EOL;
        foreach ($class->getProperties() as $property) {
            $type = $this->getType($property);
            if (!$type) continue;
            $name = $property->getName();
            if ($this->getAnnotation(self::KEY, $property)) {
                $code .= $this->createIdGetter($property) . PHP_EOL;
            }

            if (!$this->isScalar($type) && $this->isArray($type)) {
                if (!$class->hasMethod(self::getAdderName($name))) {
                    $code .= $this->createAdderMethod($property, $type) . PHP_EOL;
                }
                if (!$class->hasMethod(self::getRemoverName($name))) {
                    $code .= $this->createRemoverMethod($property, $type) . PHP_EOL;
                }
            }
            if (!$class->hasMethod(self::getGetterName($name, $type))) {
                $code .= $this->createGetterMethod($property, $type) . PHP_EOL;
            }
            if (!$class->hasMethod(self::getSetterName($name, $type))) {
                $code .= $this->createSetterMethod($property, $type) . PHP_EOL;
            }
        }
        $code .= $this->createSerializeMethod($class) . PHP_EOL;
        $code .= $this->createDeserializeMethod($class) . PHP_EOL;
        $code .= '}' . PHP_EOL;
        $code .= '?>';
        file_put_contents(self::TRAIT_LOCATION . $class->getName() . "PersistentTrait.php", $code);
    }

    /**
     * Create the ID getter.
     * @param ReflectionProperty $property The property.
     * @return string
     */
    private function createIdGetter(ReflectionProperty $property) : string {
        $code  = '    /**' . PHP_EOL;
        $code .= '     * @inheritDoc' . PHP_EOL;
        $code .= '     */' . PHP_EOL;
        $code .= '    public function getPermanentId() : string|int|null {' . PHP_EOL;
        $code .= '        return $this->' . $property->getName() . ';' . PHP_EOL;
        $code .= '    }' . PHP_EOL;
        return $code;
    }

    /**
     * Serializes the contents of a class.
     * @param ReflectionClass $class The class.
     * @return string
     */
    private function createSerializeMethod(ReflectionClass $class) : string {
        if ($class->isAbstract()) return '';
        // Get the key
        $key = array_filter($class->getProperties(), function (ReflectionProperty $property) {
            return $this->getAnnotation(self::KEY, $property);
        });
        if (count($key) === 0) return '';
        $key = array_shift($key);
        $code  = '    public function __intern_serialize(array &$context = []) : array {' . PHP_EOL;
        $code .= '        $objId = get_class($this) . \'-\' . $this->' . $key->getName() . ';' . PHP_EOL;
        $code .= '        if (array_key_exists($objId, $context)) return $context[$objId];' . PHP_EOL;
        $code .= '        $s = [];' . PHP_EOL;
        $code .= '        $context[$objId] = &$s;' . PHP_EOL;
        foreach ($class->getProperties() as $property) {
            $type = $this->getType($property);
            $nonviable = $this->getAnnotation(self::NONVIABLE, $property);
            $code .= '        $s[\'' . $property->getName() . '\'] = $this->__serializeProperty($this->' .
                self::getGetterName($property->getName(), $type) . '(), $context, ' .
                ($nonviable ? 'true' : 'false') . ');' . PHP_EOL;
        }
        $code .= '        return $s;' . PHP_EOL;
        $code .= '    }' . PHP_EOL;

        return $code;
    }

    /**
     * Creates a deserialize method.
     * @param ReflectionClass $class The class.
     * @return string
     */
    private function createDeserializeMethod(ReflectionClass $class) : string {
        if ($class->isAbstract()) return '';
        $code  = '    public static function __intern_deserialize(array $obj, array &$context = []) : ' .
            $class->getName() . ' {' . PHP_EOL;
        $code .= '        $o = new ' . $class->getName() . '();' . PHP_EOL;
        // Get the key
        $key = array_filter($class->getProperties(), function (ReflectionProperty $property) {
            return $this->getAnnotation(self::KEY, $property);
        });
        if (count($key) === 0) return '';
        $key = array_shift($key);
        $code .= '        $objId =  \'' . $class->getName() . '-\' . $obj[\'' . $key->getName() . '\'];' . PHP_EOL;
        $code .= '        $context[$objId] = $o;' . PHP_EOL;
        foreach ($class->getProperties() as $property) {
            $code .= '        $o->' . $property->getName() . ' = $o->__deserializeProperty($obj[\'' . $property->getName() .
                '\'], $context);' . PHP_EOL;
        }
        $code .= '        return $o;' . PHP_EOL;
        $code .= '    }' . PHP_EOL;

        return $code;
    }

    /**
     * Creates the adder method for a property.
     * @param ReflectionProperty $property The property.
     * @param string $type The type.
     * @return string
     */
    private function createAdderMethod(ReflectionProperty $property, string $type) : string {
        $name = $property->getName();
        $singleName = substr($name, 0, strlen($name) - 1);
        $type = str_replace(['[', ']'], '', $type);

        $code  = '    /**' . PHP_EOL;
        $code .= '     * Add a single ' . $singleName . ' to ' . $name . '.' . PHP_EOL;
        $code .= '     * @param ' . $type . ' $' . $singleName . ' The ' . $singleName . '.' . PHP_EOL;
        $code .= '     * @return void' . PHP_EOL;
        $code .= '     */' . PHP_EOL;
        $code .= '    public function ' . self::getAdderName($name, $type) .
            '(' . $type . ' $' . $singleName . ') : void {' . PHP_EOL;
        $code .= '        if (!isset($this->' . $name . ')) {' . PHP_EOL;
        $code .= '            $this->' . $name . ' = [];' . PHP_EOL;
        $code .= '        }' . PHP_EOL;
        $code .= '        $this->' . $name . '[] = $' . $singleName . ';' . PHP_EOL;
        $code .= '    }' . PHP_EOL;
        return $code;
    }

    /**
     * Creates the remove method for a property.
     * @param ReflectionProperty $property The property.
     * @param string $type The type.
     * @return string
     */
    private function createRemoverMethod(ReflectionProperty $property, string $type) : string {
        $name = $property->getName();
        $singleName = substr($name, 0, strlen($name) - 1);
        $type = str_replace(['[', ']'], '', $type);

        $code  = '    /**' . PHP_EOL;
        $code .= '     * Removes a single ' . $singleName . ' from ' . $name . '.' . PHP_EOL;
        $code .= '     * @param ' . $type . ' $' . $singleName . ' The ' . $singleName . ' to remove.' . PHP_EOL;
        $code .= '     * @return void' . PHP_EOL;
        $code .= '     */' . PHP_EOL;
        $code .= '    public function ' . self::getRemoverName($name, $type) .
            '(' . $type . ' $' . $singleName . ') : void {' . PHP_EOL;
        $code .= '        if (!isset($this->' . $name . ')) {' . PHP_EOL;
        $code .= '            $this->' . $name . ' = [];' . PHP_EOL;
        $code .= '        }' . PHP_EOL;
        $code .= '        $this->' . $name . ' = CPTools::removeFromArray($' . $singleName .
            ', $this->' . $name . ');' . PHP_EOL;
        $code .= '    }' . PHP_EOL;
        return $code;
    }

    /**
     * Creates the getter method for a property.
     * @param ReflectionProperty $property The property.
     * @param string $type The type.
     * @return string
     */
    private function createGetterMethod(ReflectionProperty $property, string $type) : string {
        $isArray = $this->isArray($type);
        $type = str_replace(['[', ']'], '', $type);
        $name = $property->getName();
        $code  = '    /**' . PHP_EOL;
        $code .= '     * Get ' . $name . '.' . PHP_EOL;
        $code .= '     * @return ' . ($isArray ? '' : '?') . $type . ($isArray ? '[]' : '') . PHP_EOL;
        $code .= '     */' . PHP_EOL;
        $code .= '    public function ' . self::getGetterName($name, $type) .
                '()' . ' : ' . ($isArray ? 'array' :
                (!$this->getAnnotation(self::KEY, $property) ? '?' : '') . $type) .
            ' {' . PHP_EOL;
        if ($isArray) {
            $code .= '        if (!isset($this->' . $name . ')) {' . PHP_EOL;
            $code .= '            $this->' . $name . ' = array();' . PHP_EOL;
            $code .= '        }' . PHP_EOL;
        }
        $code .= '        return $this->' . $name . ';' . PHP_EOL;
        $code .= '    }' . PHP_EOL;
        return $code;
    }

    /**
     * Creates the setter method for a property.
     * @param ReflectionProperty $property The property
     * @param string $type The type.
     * @return string
     */
    private function createSetterMethod(ReflectionProperty $property, string $type) : string {
        $isArray = $this->isArray($type);
        $type = str_replace(['[', ']'], '', $type);
        $name = $property->getName();
        $code  = '    /**' . PHP_EOL;
        $code .= '     * Set ' . $name . '.' . PHP_EOL;
        $code .= '     * @param ' . $type . ($isArray ? '[]' : '') . ' $' . $name . ' The ' . $name . '.' . PHP_EOL;
        $code .= '     * @return void' . PHP_EOL;
        $code .= '     */' . PHP_EOL;
        $code .= '    public function ' . self::getSetterName($name, $type) . '(' .
            ($isArray ? 'array' : '?' . $type) . ' $' . $name . ') : void {' . PHP_EOL;
        $code .= '        $this->' . $name . ' = $' . $name . ';' . PHP_EOL;
        $code .= '    }' . PHP_EOL;
        return $code;
    }

    /**
     * Get the getter name.
     * @param string $name The property name.
     * @param string $type The type name.
     * @return string
     */
    private static function getGetterName(string $name, string $type) : string {
        if ($type !== 'boolean' && $type !== 'bool') {
            return 'get' . ucfirst($name);
        } else {
            if (str_starts_with($name, 'is')) {
                return $name;
            } else {
                return 'is' . ucfirst($name);
            }
        }
    }

    /**
     * Get the setter name.
     * @param string $name The property name.
     * @param string $type The type name.
     * @return string
     */
    private static function getSetterName(string $name, string $type) : string {
        if ($type !== 'boolean' && $type !== 'bool') {
            return 'set' . ucfirst($name);
        } else {
            if (str_starts_with($name, 'is')) {
                return 'set' . substr($name, 2);
            } else {
                return 'set' . ucfirst($name);
            }
        }
    }

    /**
     * Get the adder name.
     * @param string $name The property name.
     * @param string $type The type name.
     * @return string
     */
    private static function getAdderName(string $name, string $type = "") : string {
        return 'add' . substr(ucfirst($name), 0, strlen($name) - 1);
    }

    /**
     * Get the remover name.
     * @param string $name The property name.
     * @param string $type The type name.
     * @return string
     */
    private static function getRemoverName(string $name, string $type = "") : string {
        return 'remove' . substr(ucfirst($name), 0, strlen($name) - 1);
    }

    /**
     * Check whether the given type is an array or not.
     * @param string $type The type to check.
     * @return bool
     */
    private function isArray(string $type) : bool {
        return str_starts_with($type, '[');
    }

    /**
     * Checks if the given string type is scalar.
     * @param string $type The type.
     * @return bool
     */
    public function isScalar(string $type) : bool {
        return match ($type) {
            "integer", "boolean", "bool", "int", "string", "float", "double", "content" => true,
            default => false,
        };
    }

    /**
     * Get the specified of the property.
     * @param ReflectionProperty $reflectionProperty The property.
     * @return bool|string
     */
    private function getType(ReflectionProperty $reflectionProperty) : bool|string {
        return $this->getAnnotation(self::TYPE, $reflectionProperty);
    }

    /**
     * Get the value of the expected annotation.
     * @param string $annotationType The type of annotation.
     * @param ReflectionClass|ReflectionProperty|ReflectionMethod $reflection The reflection object.
     * @param bool $isText Whether the annotation has text.
     * @return bool|string
     */
    private function getAnnotation(string $annotationType,
                                   ReflectionClass|ReflectionProperty|ReflectionMethod $reflection,
                                   bool $isText = false) : bool|string {
        if (preg_match('/@'.$annotationType.'(.*)/', $reflection->getDocComment(), $matches) >= 1) {
            if (!$isText) $value = preg_replace("/\s+/", "", trim($matches[1], " ,:"));
            else $value = trim($matches[1], " ,:");
            return $value ? $value : true;
        } else return false;
    }

}
?>