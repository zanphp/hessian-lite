<?php

namespace ZanPHP\HessianLite;

/**
 * General container for type, class definition and object/array references used in parsers and writers
 * @author vsayajin
 */
class ReferenceMap
{
    public $reflist = [];
    public $typelist = [];
    public $classlist = [];
    public $objectlist = [];

    public function incReference($obj = null)
    {
        $this->reflist[] = new Ref(count($this->objectlist));
        if ($obj != null) {
            $this->objectlist[] = $obj;
        }
    }

    public function getClassIndex($class)
    {
        foreach ($this->classlist as $index => $classdef) {
            if ($classdef->type == $class) {
                return $index;
            }
        }
        return false;
    }

    public function addClassDef(ClassDef $classdef)
    {
        $this->classlist[] = $classdef;
        return count($this->classlist) - 1;
    }

    public function getReference($object)
    {
        // 屏蔽引用功能: method([1,2], [1,2]) 会被序列化成引用, 因为 [1,2] === [1,2], 实际上不应该序列化成引用
        return false;
        return array_search($object, $this->objectlist, true);
    }

    public function getTypeIndex($type)
    {
        return array_search($type, $this->typelist, true);
    }

    public function reset()
    {
        $this->objectlist =
        $this->reflist =
        $this->typelist =
        $this->classlist = [];
    }

}