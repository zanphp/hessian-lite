<?php

namespace ZanPHP\HessianLite;

use ReflectionClass;

/**
 * Manages resolution for custom callbacks. It uses an internal cache of
 * found and not found callbacks to speed up resolution in big object lists.
 * WARNING: do not use stdClass for, well, there may be dragons type of thing...
 * @author vsayajin
 *
 */
class CallbackHandler
{
    public $callbacks = [];
    public $notFound = [];
    public $cache = [];

    public function __construct($callbacks = [])
    {
        $this->callbacks = $callbacks;
    }

    /**
     * Resolves a callback either by name or by examining the class
     * of an object passed. Takes into account superclasses and interfaces
     * @param mixed $obj string or object
     * @return bool|mixed
     */
    public function getCallback($obj)
    {
        if (!$this->callbacks)
            return false;
        if (is_string($obj)) {
            return isset($this->callbacks[$obj]) ? $this->callbacks[$obj] : false;
        }

        if (!is_object($obj)) {
            return false;
        }

        $class = get_class($obj);
        if (isset($this->notFound[$class])) {
            return false;
        }

        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }

        $ref = new ReflectionClass($class);
        $types[] = $ref->getName();
        if ($ref->getParentClass()) {
            $types[] = $ref->getParentClass()->getName();
        }

        $ints = $ref->getInterfaceNames();
        if ($ints) {
            $types = array_merge($types, $ints);
        }

        foreach ($types as $type) {
            $check = '@' . $type;
            if (isset($this->callbacks[$check])) {
                $callback = $this->callbacks[$check];
                $this->cache[$class] = $callback;
                return $callback;
            }
        }

        $this->notFound[$class] = true;
        return false;
    }

    public function doCallback($callable, $arguments = [])
    {
        if (is_callable($callable)) {
            return call_user_func_array($callable, $arguments);
        }
    }
}

