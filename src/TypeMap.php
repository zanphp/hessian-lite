<?php

namespace ZanPHP\HessianLite;

use Exception;

/**
 * Maps local PHP class names to remote types for both sending and receiving in the service.
 * @author vsayajin
 */
class TypeMap
{

    const REG_ALL = '([_0-9a-z\-]*)';

    public $types = [];
    public $localRules = [];
    public $remoteRules = [];

    public function __construct($map = [])
    {
        foreach ($map as $local => $remote) {
            $this->mapType($local, $remote);
        }
    }

    /**
     * Creates a mapping between a local PHP type and remote types
     * You can use the '*' wildcard in either the local or remote types
     * for easier mapping. Ex. mapType('array', '*.IList*'); will map
     * every incoming object of type IList to a PHP array
     * @param string $local Name of the local PHP class
     * @param string $remote Name of the remote mapping type
     * @throws Exception
     */
    public function mapType($local, $remote)
    {
        $ruleLocal = self::isRule($local);
        $ruleRemote = self::isRule($remote);

        if ($ruleLocal && $ruleRemote) {
            throw new Exception('Typemap : Cannot use wildcards in both local and remote types');
        }

        if ($ruleLocal) {
            $rule = self::ruleToRegexp($local);
            $this->localRules[$rule] = $remote;
        } else if ($ruleRemote) {
            $rule = self::ruleToRegexp($remote);
            $this->remoteRules[$rule] = $local;
        } else {
            $this->types[$remote] = $local;
        }
    }

    public function getLocalType($remoteType)
    {
        if (class_exists($remoteType)) {
            return $remoteType;
        }

        if (isset($this->types[$remoteType])) {
            $local = $this->types[$remoteType];
            return $local != 'array' ? $local : false;
        }
        foreach ($this->remoteRules as $rule => $local) {
            if (preg_match($rule, $remoteType)) {
                $this->types[$remoteType] = $local;
                return $local != 'array' ? $local : false;
            }
        }
        return $remoteType;
    }

    public function getRemoteType($localType)
    {
        $remote = array_search($localType, $this->types);
        if ($remote !== false)
            return $remote;

        foreach ($this->localRules as $rule => $remote) {
            if (preg_match($rule, $localType)) {
                return $remote;
            }
        }
        //return false;
        return $localType;
    }

    private static function ruleToRegexp($string)
    {
        $rule = str_replace('.', '\.', $string);
        return '/' . str_replace('*', self::REG_ALL, $rule) . '/';
    }

    private static function isRule($text)
    {
        return strpos($text, '*') !== false;
    }

    public function merge(TypeMap $map)
    {
        $this->types = array_merge($this->types, $map->types);
        $this->localRules = array_merge($this->localRules, $map->localRules);
        $this->remoteRules = array_merge($this->remoteRules, $map->remoteRules);
    }

}