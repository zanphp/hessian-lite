<?php

namespace ZanPHP\HessianLite;

use Exception;
use stdClass;

class Parser
{
    /**
     * @var RuleResolver
     */
    public $resolver;

    public $stream;
    /**
     * @var ReferenceMap
     */
    public $refmap;

    /**
     * @var TypeMap
     */
    public $typemap;

    /**
     * @var CallbackHandler
     */
    public $filterContainer;

    public function __construct(RuleResolver $resolver, Stream $stream = null, CallbackHandler $filters = null)
    {
        $this->resolver = $resolver;
        $this->refmap = new ReferenceMap();
        $this->typemap = new TypeMap();
        $this->stream = $stream;
        $this->filterContainer = $filters;
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

    public function read($count = 1)
    {
        return $this->stream->read($count);
    }

    public function readNum($count = 1)
    {
        return ord($this->stream->read($count));
    }

    public function parseCheck($code = null, $expect = false)
    {
        $value = $this->parse($code, $expect);

        if ($value instanceof Ref) {
            return $this->refmap->objectlist[$value->index];
        } else {
            return $value;
        }
    }

    public function parse($code = null, $expect = false)
    {
        $end = true;

        if (!$code) {
            $code = $this->read();
        }

        do {
            $rule = $this->resolver->resolveSymbol($code, $expect);
            $fun = $rule->func;
            $num = ord($code);
//            echo "[fun=$fun, code=$code, ord=$num, hex=0x" . dechex($num) . ", pos={$this->stream->pos}]\n";
            $value = $this->$fun($code, $num);
            if ($value instanceof IgnoreCode) {
                $end = false;
                $code = $this->read();
            } else {
                $end = true;
            }
        } while (!$end);

        $filter = $this->filterContainer->getCallback($rule->type);
        if ($filter) {
            $value = $this->filterContainer->doCallback($filter, array($value, $this));
        }
        if (is_object($value)) {
            $filter = $this->filterContainer->getCallback($value);
            if ($filter) {
                $value = $this->filterContainer->doCallback($filter, array($value, $this));
            }
        }

        return $value;
    }


    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    private function binary0($code, $num)
    {
        $len = $num - 0x20;
        return $this->read($len);
    }

    private function binary1($code, $num)
    {
        $len = (($num - 0x34) << 8) + ord($this->read());
        return $this->read($len);
    }

    private function binaryLongData()
    {
        $tempLen = unpack('n', $this->read(2));
        $len = $tempLen[1];
        return $this->read($len);
    }

    private function binaryLong($code, $num)
    {
        $final = true;
        $data = '';
        do {
            $final = $num != 0x41;
            if ($num == 0x41 || $num == 0x42) {
                $data .= $this->binaryLongData();
            } else {
                $data .= $this->parse($code, 'binary');
            }
            if (!$final) {
                $code = $this->read();
                $num = ord($code);
            }
        } while (!$final);
        return $data;
    }

    //--- int

    private function compactInt1($code, $num)
    {
        if ($code == 0x90)
            return 0;
        return ord($code) - 0x90;
    }

    private function compactInt2($code, $num)
    {
        $b0 = ord($this->read());
        return ((ord($code) - 0xc8) << 8) + $b0;
    }

    private function compactInt3($code, $num)
    {
        $b1 = ord($this->read());
        $b0 = ord($this->read());
        return ((ord($code) - 0xd4) << 16) + ($b1 << 8) + $b0;
    }

    // 32位数字
    private function parseInt($code, $num)
    {
        $data = unpack('N', $this->read(4));
        $value = $data[1];

        if ($value > 0x7fffffff) {
            $value = $value - 0x100000000;
        }
        return $value;
    }

    private function bool($code, $num)
    {
        return $code == 'T';
    }

    //--- datetime

    private function date($code, $num)
    {
        return Utils::timestampFromBytes64($this->read(8));
    }

    private function compactDate($code, $num)
    {
        $data = unpack('C4', $this->read(4));
        $num = ($data[1] << 24) +
            ($data[2] << 16) +
            ($data[3] << 8) +
            $data[4];
        $ts = $num * 60;
        return $ts;
    }

    // double

    private function double1($code, $num)
    {
        if ($num == 0x5b)
            return (float)0;
        if ($num == 0x5c)
            return (float)1.0;
        $bytes = $this->read(1);

        /**
         * FIX 2016年09月26日20:47:38 修复负整数情况
         * @author 炒饭
         */
        $num = ord($bytes);
        if ($num > 0x7f) {
            $num = $num - 0x100;
        }
        return (float)$num;
    }

    private function double2($code, $num)
    {
        $bytes = $this->read(2);
        $b = unpack('s', strrev($bytes));

        /**
         * FIX 2016年09月26日20:47:38 修复负整数情况
         * @author 炒饭
         */
        $num = $b[1];
        if ($num > 0x7fff) {
            $num = $num - 0x10000;
        }

        return (float)$num;
    }

    private function double4($code, $num)
    {
        $b = $this->read(4);
        $num = (ord($b[0]) << 24) +
            (ord($b[1]) << 16) +
            (ord($b[2]) << 8) +
            ord($b[3]);

        /**
         * FIX 2016年09月26日20:47:38 修复负整数情况
         * @author 炒饭
         */
        if ($num > 0x7fffffff) {
            $num = $num - 0x100000000;
        }
        return 0.001 * $num;
        // from the java implementation, this makes no sense
        // why not just use the float bytes as any sane language and pack it like 'f'?
    }

    private function double64($code, $num)
    {
        $bytes = $this->read(8);
        if (Utils::isLittleEndian()) {
            $bytes = strrev($bytes);
        }
        $double = unpack("dflt", $bytes);
        return $double['flt'];
    }

    // --- long

    private function long1($code, $num)
    {
        if ($code == 0xe0)
            return 0;
        return $num - 0xe0;
    }

    private function long2($code, $num)
    {
        return (($num - 0xf8) << 8) + $this->readNum();
    }

    private function long3($code, $num)
    {
        return ((ord($code) - 0x3c) << 16)
            + ($this->readNum() << 8)
            + $this->readNum();
    }

    private function long32($code, $num)
    {
        $value = ($this->readNum() << 24) +
            ($this->readNum() << 16) +
            ($this->readNum() << 8) +
            $this->readNum();

        if ($value > 0x7fffffff) {
            return $value - 0x100000000;
        }

        return $value;
    }

    private function long64($code, $num)
    {
        return ($this->readNum() << 56) +
            ($this->readNum() << 48) +
            ($this->readNum() << 40) +
            ($this->readNum() << 32) +
            ($this->readNum() << 24) +
            ($this->readNum() << 16) +
            ($this->readNum() << 8) +
            $this->readNum();
    }

    private function parseNull($code, $num)
    {
        return null;
    }

    private function reserved($code, $num)
    {
        throw new ParsingException("Code $code reserved");
    }

    // --- string

    private function string0($code, $num)
    {
        return $this->readUTF8Bytes($num);
    }

    private function string1($code, $num)
    {
        $len = (($num - 0x30) << 8) + ord($this->read());
        return $this->readUTF8Bytes($len);
    }

    private function stringLongData()
    {
        $tempLen = unpack('n', $this->read(2));
        $len = $tempLen[1];
        return $this->readUTF8Bytes($len);
    }

    private function stringLong($code, $num)
    {
        $final = true;
        $data = '';
        // TODO Probar con textos bien largos con caracteres utf-8 puede haber problemas
        do {
            $final = $num != 0x52;
            if ($num == 0x52 || $num == 0x53)
                $data .= $this->stringLongData();
            else
                $data .= $this->parse($code, 'string');
            if (!$final) {
                $code = $this->read();
                $num = ord($code);
            }
        } while (!$final);
        return $data;
    }

    /**
     * 从Java端返回错误的字符，进行解析
     *
     * @author 炒饭
     * 2016年10月14日17:18:54
     */
    private function readUTF8FromBadStr($bytes)
    {
        if (count($bytes) !== 6) {
            return '?';
        }

        try {
            $bytes = array_map(function ($v) {
                return ord($v);
            }, $bytes);

            // 获取第一个utf-8码
            $v0 = (($bytes[0] & 0xf) << 12) + (($bytes[1] & 0x3f) << 6) + ($bytes[2] & 0x3f);

            // 获取第二个utf-8码
            $v1 = (($bytes[3] & 0xf) << 12) + (($bytes[4] & 0x3f) << 6) + ($bytes[5] & 0x3f);

            // 合并为一个utf-16
            $code = ($v0 << 16) + $v1;

            // to hex
            $code = base_convert($code, 10, 16);

            $code = mb_convert_encoding(pack('H*', $code), 'UTF-8', 'UTF-16BE');

            return $code;

        } catch (Exception $e) {
            return '?';
        }
    }

    /**
     * 修复支持在错误java端下获取辅助平面字符，原本方法修改名称为readUTF8BytesQuick
     *
     * @author 炒饭
     * 2016年10月14日17:18:54
     */
    private function readUTF8Bytes($len)
    {
        $string = '';

        for ($i = 0; $i < $len; $i++) {
            $ch = $this->read(1);
            $charCode = ord($ch);

            if ($charCode < 0x80) {
                $string .= $ch;
            } else if (($charCode & 0xe0) === 0xc0) {
                $string .= $ch . $this->read(1);
            } else if ($charCode === 0xed) {
                /*
                 * 以毒攻毒
                 * 0xD800..0xDBFF
                 * 解出的字符，在[0xD8, 0xDC)区间内，即为U+10000到U+10FFFF码位的字符
                 */

                // 读取第二个字节
                $ch1 = $this->read();

                $charCode1 = ord($ch1);

                // 判断第二个4位是否为在[0x8, 0xC)区间内
                $secondFourBit = ($charCode1 & 0x3c) >> 2;
                if ($secondFourBit >= 0x8 && $secondFourBit < 0xC) {
                    // 字符串offset再后移一位
                    $i++;

                    $bytes = [
                        $ch,
                        $ch1,
                        $this->read(1),
                        $this->read(1),
                        $this->read(1),
                        $this->read(1),
                    ];

                    // 读取问题字符
                    $string .= $this->readUTF8FromBadStr($bytes);
                } else {
                    // 当做正常的3个字符串输出
                    $string .= $ch . $ch1 . $this->read();
                }
            } else if (($charCode & 0xf0) === 0xe0) {
                // 3字节字符识别
                $string .= $ch . $this->read(2);
            } else if (($charCode & 0xf8) === 0xf0) {
                // 4字节字符识别
                $string .= $ch . $this->read(3);
            } else {
                throw new ParsingException("Bad utf-8 encoding at pos " . $this->stream->pos);
            }
        }

        if (Utils::isInternalUTF8())
            return $string;

        return utf8_decode($string);
    }

    // 正确方法，但是不能支持在错误java端下获取辅助平面字符
    private function readUTF8BytesQuick($len)
    {
        $string = $this->read($len);
        $pos = 0;
        $pass = 1;

        $needIconv = false;

        while ($pass <= $len) {
            $charCode = ord($string[$pos]);
            if ($charCode < 0x80) {
                $pos++;
            } elseif (($charCode & 0xe0) == 0xc0) {
                $pos += 2;
                $string .= $this->read(1);
            } elseif (($charCode & 0xf0) == 0xe0) {
                $pos += 3;
                $string .= $this->read(2);
                $needIconv = true;
            } elseif (($charCode & 0xf8) == 0xf0) {
                $pos += 4;
                $string .= $this->read(3);
            }
            $pass++;
        }

        if (!Utils::isInternalUTF8()) {
            $string = utf8_decode($string);
        }

        // utf8mb4忽略无法理解的编码
        if ($needIconv) {
            return iconv('GBK', 'UTF-8//TRANSLIT', iconv('UTF-8', 'GBK//IGNORE', $string));
        }

        return $string;
    }

    //-- list
    private function vlenList($code, $num)
    {
        $type = $this->parseType();
        $array = array();
        $this->refmap->incReference();
        $this->refmap->objectlist[] = &$array;
        while ($code != 'Z') {
            $code = $this->read();
            if ($code != 'Z') {
                $item = $this->parse($code);
                if ($item instanceof Ref) {
                    $array[] = &$this->refmap->objectlist[$item->index];
                } else {
                    $array[] = $item;
                }
            }
        }
        return $array;
    }

    private function flenList($code, $num)
    {
        $type = $this->parseType();
        $len = $this->parse(null, 'integer');
        $array = array();
        $this->refmap->incReference();
        $this->refmap->objectlist[] = &$array;
        for ($i = 0; $i < $len; $i++) {
            $item = $this->parse();
            if ($item instanceof Ref) {
                $array[] = &$this->refmap->objectlist[$item->index];
            } else {
                $array[] = $item;
            }
        }
        return $array;
    }

    private function vlenUntypedList($code, $num)
    {
        $array = array();
        $this->refmap->incReference();
        $this->refmap->objectlist[] = &$array;
        while ($code != 'Z') {
            $code = $this->read();
            if ($code != 'Z') {
                $item = $this->parse($code);
                if ($item instanceof Ref) {
                    $array[] = &$this->refmap->objectlist[$item->index];
                } else {
                    $array[] = $item;
                }
            }
        }
        return $array;
    }

    private function flenUntypedList($code, $num)
    {
        $array = array();
        $this->refmap->incReference();
        $this->refmap->objectlist[] = &$array;
        $len = $this->parse(null, 'integer');
        for ($i = 0; $i < $len; $i++) {
            $item = $this->parse();
            if ($item instanceof Ref) {
                $array[] = &$this->refmap->objectlist[$item->index];
            } else {
                $array[] = $item;
            }
        }
        return $array;
    }

    private function directListTyped($code, $num)
    {
        $len = ord($code) - 0x70;
        $type = $this->parseType();
        $array = array();
        $this->refmap->incReference();
        $this->refmap->objectlist[] = &$array;
        for ($i = 0; $i < $len; $i++) {
            $item = $this->parse();
            if ($item instanceof Ref) {
                $array[] = &$this->refmap->objectlist[$item->index];
            } else {
                $array[] = $item;
            }
        }
        return $array;
    }

    private function directListUntyped($code, $num)
    {
        $len = ord($code) - 0x78;
        $array = array();
        $this->refmap->incReference();
        $this->refmap->objectlist[] = &$array;
        for ($i = 0; $i < $len; $i++) {
            $item = $this->parse();
            if ($item instanceof Ref) {
                $array[] = &$this->refmap->objectlist[$item->index];
            } else {
                $array[] = $item;
            }
        }
        return $array;
    }

    private function parseType()
    {
        $type = $this->parse(null, 'string,integer');
        if (is_integer($type)) {
            $index = $type;
            if (!isset($this->refmap->reflist[$index])) {
                throw new ParsingException("Reference index $index not found");
            }
            return $this->refmap->typelist[$index];
        }
        $this->refmap->typelist[] = $type;
        return $type;
    }

    //-- map
    function untypedMap($code, $num)
    {
        $map = array();
        $this->refmap->incReference();
        $this->refmap->objectlist[] = &$map;
        $code = $this->read();
        while ($code != 'Z') {
            $key = $this->parse($code);
            $value = $this->parse();

            if ($key instanceof Ref) {
                $key = $this->refmap->objectlist[$value->index];
            }
            if ($value instanceof Ref) {
                $value = $this->refmap->objectlist[$value->index];
            }

            $map[$key] = $value;
            if ($code != 'Z')
                $code = $this->read();
        }
        return $map;
    }

    private function typedMap($code, $num)
    {
        $type = $this->parseType();
        $map = array();
        $this->refmap->incReference();
        $this->refmap->objectlist[] = &$map;
        // TODO references and objects
        $code = $this->read();
        while ($code != 'Z') {
            $key = $this->parse($code);
            $value = $this->parse();

            if ($key instanceof Ref) {
                $key = $this->refmap->objectlist[$value->index];
            }
            if ($value instanceof Ref) {
                $value = $this->refmap->objectlist[$value->index];
            }

            $map[$key] = $value;
            if ($code != 'Z')
                $code = $this->read();
        }
        return $map;
    }

    //-- object
    private function typeDefinition($code, $num)
    {
        $type = $this->parseType();
        $numfields = $this->parse(null, 'integer');
        $classdef = new ClassDef();
        $classdef->type = $type;
        for ($i = 0; $i < $numfields; $i++) {
            $classdef->props[] = $this->parse(null, 'string');
        }
        $this->refmap->addClassDef($classdef);
        return $classdef;
    }

    private function objectInstance($code, $num)
    {
        $index = $this->parse(null, 'integer');
        return $this->fillMap($index);
    }

    private function objectDirect($code, $num)
    {
        $index = $num - 0x60;
        return $this->fillMap($index);
    }

    private function getObject($type)
    {
        if (!class_exists($type)) {
            echo "Type $type cannot be found for object instantiation, check your type mappings\n";
            $obj = new stdClass();
            $obj->__type = $type;
            return $obj;
        }

        return new $type();
    }

    private function fillMap($index)
    {
        if (!isset($this->refmap->classlist[$index])) {
            throw new ParsingException("Class def index $index not found");
        }
        $classdef = $this->refmap->classlist[$index];

        $localType = $this->typemap->getLocalType($classdef->type);
        $obj = $this->getObject($localType);

        $this->refmap->incReference();
        $this->refmap->objectlist[] = $obj;

        foreach ($classdef->props as $prop) {
            $item = $this->parse();
            if ($item instanceof Ref) {
                /** @var Ref $item */
                $item = $this->refmap->objectlist[$item->index];
            }

            $obj->$prop = $item;
        }

        return $obj;
    }

    private function reference()
    {
        $index = $this->parse(null, 'integer');
        if (!isset($this->refmap->reflist[$index])) {
            throw new ParsingException("Reference index $index not found");
        }
        return $this->refmap->reflist[$index];
    }
}
