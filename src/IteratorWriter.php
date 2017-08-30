<?php

namespace ZanPHP\HessianLite;

use Countable;
use Iterator;

/**
 * Special writer for classes derived from Iterator. Hessian 2 protocol
 * Resolves and writes either a list or a map using the type information optionally.
 * @author vsayajin
 *
 */
class IteratorWriter
{
    public $usetype;

    public function write(Iterator $list, Writer $writer)
    {
        echo 'iterator writer', "\n";
        $writer->getRefMap()->objectlist[] = $list;

        $total = $this->getCount($list);
        $class = get_class($list);
        $type = $writer->getTypeMap()->getRemoteType($class);

        $mappedType = $type ? $type : $class; // OJO con esto

        $islist = Utils::isListIterate($list);
        if ($islist) {
            list($stream, $terminate) = $this->listHeader($writer, $mappedType, $total);
            foreach ($list as $value) {
                $stream .= $writer->writeValue($value);
            }
            if ($terminate) {
                $stream .= 'Z';
            }
        } else {
            if ($this->usetype && $mappedType) {
                $stream = 'M';
                $stream .= $writer->writeType($mappedType);
            } else {
                $stream = 'H';
            }

            // ???
            foreach ($elements as $key => $value) {
                $stream .= $writer->writeValue($key);
                $stream .= $writer->writeValue($value);
            }
            $stream .= 'Z';
        }
        return new StreamResult($stream);
    }

    public function listHeader(Writer $writer, $type, $total = false)
    {
        $stream = '';
        $terminate = false;
        if ($this->usetype && $type) { // typed
            if ($total !== false) { // typed fixed length
                $stream .= 'V';
                $stream .= $writer->writeType($type);
                $stream .= $writer->writeInt($total);
            } else { // typed variable length
                $stream .= "\x55";
                $stream .= $writer->writeType($type);
                $terminate = true;
            }
        } else { // untyped
            if ($total !== false) { //untyped fixed length
                $stream .= "\x58";
                $stream .= $writer->writeInt($total);
            } else { // untyped variable length
                $stream .= "\x57";
                $terminate = true;
            }
        }

        return [$stream, $terminate];
    }

    private function getCount($list)
    {
        if ($list instanceof Countable) {
            return count($list);
        }
        return false;
    }
}