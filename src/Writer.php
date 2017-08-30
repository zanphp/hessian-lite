<?php

namespace ZanPHP\HessianLite;

use Exception;

class Writer
{
    private $refmap;
    private $typemap;
    private $filterContainer;

    public function __construct(CallbackHandler $container = null)
    {
        $this->refmap = new ReferenceMap();
        $this->typemap = new TypeMap();
        $this->filterContainer = $container;
    }

    private function between($value, $min, $max)
    {
        return $min <= $value && $value <= $max;
    }

    private function resolveDispatch($type)
    {
        switch ($type) {
            case 'integer':
                $dispatch = 'writeInt';
                break;
            case 'boolean':
                $dispatch = 'writeBool';
                break;
            case 'string':
                $dispatch = 'writeString';
                break;
            case 'double':
                $dispatch = 'writeDouble';
                break;
            case 'array':
                $dispatch = 'writeArray';
                break;
            case 'object':
                $dispatch = 'writeObject';
                break;
            case 'NULL':
                $dispatch = 'writeNull';
                break;
            // 这里有阻塞IO，且没什么卵用
            // case 'resource': $dispatch = 'writeResource' ; break;
            default:
                throw new Exception("Handler for type $type not implemented");
        }
        return $dispatch;
    }

    public function getTypeMap()
    {
        return $this->typemap;
    }

    public function setTypeMap(TypeMap $typeMap)
    {
        $this->typemap = $typeMap;
    }

    public function getRefMap()
    {
        return $this->refmap;
    }

    public function setRefMap(ReferenceMap $refmap)
    {
        $this->refmap = $refmap;
    }

    public function writeValue($value)
    {
        $type = gettype($value);
        $dispatch = $this->resolveDispatch($type);
        if (is_object($value)) {
            $filter = $this->filterContainer->getCallback($value);
            if ($filter) {
                $value = $this->filterContainer->doCallback($filter, array($value, $this));
                if ($value instanceof StreamResult) {
                    return $value->stream;
                }
                $ntype = gettype($value);
                if ($type != $ntype)
                    $dispatch = $this->resolveDispatch($ntype);
            }
        }
        $data = $this->$dispatch($value);
        return $data;
    }

    public function writeNull()
    {
        return 'N';
    }

    public function writeArray(array $array)
    {
        if (empty($array))
            return 'N';

        $refindex = $this->refmap->getReference($array);
        if ($refindex !== false) {
            return $this->writeReference($refindex);
        }

        /* ::= x57 value* 'Z'		# variable-length untyped list
         ::= x58 int value*		# fixed-length untyped list
        ::= [x78-7f] value*	   # fixed-length untyped list
         */

        $total = count($array);
        if (Utils::isListFormula($array)) {
            $this->refmap->objectlist[] = &$array;
            $stream = '';
            if ($total <= 7) {
                $len = $total + 0x78;
                $stream .= pack('c', $len);
            } else {
                $stream = pack('c', 0x58);
                $stream .= $this->writeInt($total);
            }
            foreach ($array as $key => $value) {
                $stream .= $this->writeValue($value);
            }
            return $stream;
        } else {
            return $this->writeMap($array);
        }
    }

    public function writeMap($map, $type = '')
    {
        if (empty($map))
            return 'N';

        /*
        ::= 'M' type (value value)* 'Z'  # key, value map pairs
       ::= 'H' (value value)* 'Z'	   # untyped key, value
         */

        $refindex = $this->refmap->getReference($map);
        if ($refindex !== false) {
            return $this->writeReference($refindex);
        }

        $this->refmap->objectlist[] = &$map;

        if ($type == '') {
            $stream = 'H';
        } else {
            $stream = 'M';
            $stream .= $this->writeType($type);
        }
        foreach ($map as $key => $value) {
            $stream .= $this->writeValue($key);
            $stream .= $this->writeValue($value);
        }
        $stream .= 'Z';
        return $stream;
    }

    private function writeObjectData($value)
    {
        $stream = '';

        $class = get_class($value);

        if (isset($value->__type) && $value->__type) {
            $__type = $value->__type;
        } else {
            $__type = $class;
        }

        $index = $this->refmap->getClassIndex($__type);

        if ($index === false) {
            $classdef = new ClassDef();
            $classdef->type = $__type;
            if ($class === "stdClass" || $class === \stdClass::class) {
                $classdef->props = array_keys(get_object_vars($value));
            } else
                $classdef->props = array_keys(get_class_vars($class));

            unset($classdef->props["__type"]);
            $index = $this->refmap->addClassDef($classdef);
            $total = count($classdef->props);

            if ($__type === $class) {
                $type = $this->typemap->getRemoteType($class);
                $__type = $type ? $type : $__type;
            }

            $stream .= 'C';
            // 写入类型
            $stream .= $this->writeString($__type);
            $stream .= $this->writeInt($total);
            foreach ($classdef->props as $name) {
                $stream .= $this->writeString($name);
            }
        }

        if ($index < 16) {
            $stream .= pack('c', $index + 0x60);
        } else {
            $stream .= 'O';
            $stream .= $this->writeInt($index);
        }

        $this->refmap->objectlist[] = $value;
        $classdef = $this->refmap->classlist[$index];
        foreach ($classdef->props as $key) {
            $val = $value->$key;
            $stream .= $this->writeValue($val);
        }

        return $stream;
    }

    public function writeObject($value)
    {
        $refindex = $this->refmap->getReference($value);
        if ($refindex !== false) {
            return $this->writeReference($refindex);
        }
        return $this->writeObjectData($value);
    }

    public function writeType($type)
    {
        $refindex = $this->refmap->getTypeIndex($type);
        if ($refindex !== false) {
            return $this->writeInt($refindex);
        }
        $this->references->typelist[] = $type;
        return $this->writeString($type);
    }

    public function writeReference($value)
    {
        $stream = pack('c', 0x51);
        $stream .= $this->writeInt($value);
        return $stream;
    }

    public function writeDate($value)
    {
        $ts = $value;
        $stream = '';
        if ($ts % 60 != 0) {
            $stream .= pack('c', 0x4a);
            $ts = $ts * 1000;
            $res = $ts / Utils::pow32;
            $stream .= pack('N', $res);
            $stream .= pack('N', $ts);
        } else { // compact date, only minutes
            $ts = intval($ts / 60);
            $stream .= pack('c', 0x4b);
            $stream .= pack('c', ($ts >> 24));
            $stream .= pack('c', ($ts >> 16));
            $stream .= pack('c', ($ts >> 8));
            $stream .= pack('c', $ts);
        }
        return $stream;
    }

    public function writeBool($value)
    {
        return $value ? 'T' : 'F';
    }

    public function writeInt($value)
    {
        /**
         * FIX 2016年08月20日 支持长整形
         * @author 炒饭
         */
        if ($this->between($value, -16, 47)) {
            return pack('c', $value + 0x90);
        } else
            if ($this->between($value, -2048, 2047)) {
                $b0 = 0xc8 + ($value >> 8);
                $stream = pack('c', $b0);
                $stream .= pack('c', $value);
                return $stream;
            } else
                if ($this->between($value, -262144, 262143)) {
                    $b0 = 0xd4 + ($value >> 16);
                    $b1 = $value >> 8;
                    $stream = pack('c', $b0);
                    $stream .= pack('c', $b1);
                    $stream .= pack('c', $value);
                    return $stream;
                } else
                    if ($this->between($value, -2147483648, 2147483647)) {
                        $stream = 'I';
                        $stream .= pack('c', ($value >> 24));
                        $stream .= pack('c', ($value >> 16));
                        $stream .= pack('c', ($value >> 8));
                        $stream .= pack('c', $value);
                        return $stream;
                    } else {
                        $stream = 'L';
                        $stream .= pack('c', ($value >> 56));
                        $stream .= pack('c', ($value >> 48));
                        $stream .= pack('c', ($value >> 40));
                        $stream .= pack('c', ($value >> 32));
                        $stream .= pack('c', ($value >> 24));
                        $stream .= pack('c', ($value >> 16));
                        $stream .= pack('c', ($value >> 8));
                        $stream .= pack('c', $value);
                        return $stream;
                    }
    }

    public function writeString($value)
    {
        $len = Utils::stringLength($value);

        if ($len < 32) {
            return pack('C', $len) . $this->writeStringData($value);
        } else
            if ($len < 1024) {
                $b0 = 0x30 + ($len >> 8);
                $stream = pack('C', $b0);
                $stream .= pack('C', $len);
                return $stream . $this->writeStringData($value);
            } else {
                // TODO :chunks
                $total = $len;
                $stream = '';
                $tag = 'S';
                $stream .= $tag . pack('n', $len);
                $stream .= $this->writeStringData($value);
                return $stream;
            }
    }

    public function writeSmallString($value)
    {
        $len = Utils::stringLength($value);
        if ($len < 32) {
            return pack('C', $len) . $this->writeStringData($value);
        } else if ($len < 1024) {
            $b0 = 0x30 + ($len >> 8);
            return pack('C', $b0) . pack('C', $len) . $this->writeStringData($value);
        } else {
            assert(false);
        }
    }

    private function writeStringData($string)
    {
        return Utils::writeUTF8($string);
    }

    public function writeDouble($value)
    {

        $frac = abs($value) - floor(abs($value));
        if ($value == 0.0) {
            return pack('c', 0x5b);
        }
        if ($value == 1.0) {
            return pack('c', 0x5c);
        }

        // Issue 10, Fix thanks to nesnnaho...@googlemail.com,

        /**
         * FIX 2016年10月06日 范围搞错，应该为[-128, 127]
         * @author 炒饭
         */
        // if($frac == 0 && $this->between($value, -127, 128)){
        if ($frac == 0 && $this->between($value, -128, 127)) {
            return pack('c', 0x5d) . pack('c', $value);
        }
        if ($frac == 0 && $this->between($value, -32768, 32767)) {
            $stream = pack('c', 0x5e);
            $stream .= Utils::floatBytes($value);
            return $stream;
        }

        // TODO double 4 el del 0.001, revisar
        $mills = (int)($value * 1000);
        /**
         * FIX 2016年09月26日18:58:21 64位下，写入浮点数出错
         * @author 炒饭
         */
        // - if (0.001 * $mills == $value)
        if (0.001 * $mills == $value
            && $this->between($mills, -2147483648, 2147483647)
        ) {
            $stream = pack('c', 0x5f);
            $stream .= pack('c', $mills >> 24);
            $stream .= pack('c', $mills >> 16);
            $stream .= pack('c', $mills >> 8);
            $stream .= pack('c', $mills);
            return $stream;
        }

        // 64 bit double
        $stream = 'D';
        $stream .= Utils::doubleBytes($value);
        return $stream;
    }

//    public function writeResource($handle)
//    {
//        $type = get_resource_type($handle);
//        $stream = '';
//        if ($type == 'file' || $type == 'stream') {
//            while (!feof($handle)) {
//                $content = fread($handle, 32768);
//                $len = strlen($content);
//                if ($len < 15) { // short binary
//                    $stream .= pack('C', $len + 0x20);
//                    $stream .= $content;
//                } else {
//                    $tag = 'b';
//                    if (feof($handle))
//                        $tag = 'B';
//                    $stream .= $tag . pack('n', $len);
//                    $stream .= $content;
//                }
//            }
//            fclose($handle);
//        } else {
//            throw new Exception("Cannot handle resource of type '$type'");
//        }
//        return $stream;
//    }

    public function writeBinary($bin)
    {
        $len = strlen($bin);
        if ($len === 0) {
            return "";
        }

        if ($len < 15) { // short binary
            return pack('C', $len + 0x20) . $bin;
        }

        $stream = "";
        $chunks = str_split($bin, intval(0xffff / 2));
        $lastChunk = array_pop($chunks);
        foreach ($chunks as $chunk) {
            $stream .= 'b' . pack('n', strlen($chunk)) . $chunk;
        }
        $stream .= 'B' . pack('n', strlen($lastChunk)) . $lastChunk;
        return $stream;
    }
}
