<?php

namespace ZanPHP\HessianLite;

require  __DIR__ . "/../vendor/autoload.php";

date_default_timezone_set('Asia/Shanghai');
mb_internal_encoding('UTF-8');

$writer = Factory::getWriter();
$stream = "";
$stream .= $writer->writeNull();
$stream .= $writer->writeBool(true);
$stream .= $writer->writeInt(42);
$stream .= $writer->writeDouble(3.14);
$stream .= $writer->writeBinary("HELLO");
$stream .= $writer->writeArray(array_values($_SERVER));
$stream .= $writer->writeMap($_SERVER);
$stream .= $writer->writeDate(microtime(true));
$stream .= $writer->writeString("HELLO");
$stream .= $writer->writeSmallString("HELLO");
$stream .= $writer->writeObject((object)$_SERVER);
//$writer->writeType();
//$writer->writeReference(1);


$parser = Factory::getParser($stream);
while (true) {
    try {
        var_dump($parser->parse());
    } catch (StreamEOF $_) {
        break;
    }
}






//$handler = new TypeMap();
//$handler->mapType('User', 'test.User');
//$handler->mapType('Empleado*', 'modelo.Empleado');
//$handler->mapType('EmpleadoDefault', 'modelo.Empleado');
//$handler->mapType('array', '*.List*');
//
//var_dump($handler->getRemoteType('User'));
//var_dump($handler->getLocalType('test.User'));
//
//var_dump($handler->getRemoteType('EmpleadoCaldo'));
//var_dump($handler->getLocalType('modelo.Empleado'));
//
//var_dump($handler->getLocalType('System.Collections.List'));
//var_dump($handler->getLocalType('System.Collections.List<int>'));
