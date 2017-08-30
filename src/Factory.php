<?php

namespace ZanPHP\HessianLite;

class Factory
{
    private static $cacheRules;

    private static function getRulesResolver()
    {
        if (static::$cacheRules === null) {
            self::$cacheRules = new RuleResolver();
        }
        return self::$cacheRules;
    }

    public static function getParser($bin, array $filter = [])
    {
        $resolver = self::getRulesResolver();
        $parser = new Parser($resolver, new Stream($bin), new CallbackHandler(array_merge([
            'date' => [DatetimeAdapter::class, 'toObject'],
        ], $filter)));
        return $parser;
    }

    public static function getWriter(array $filter = [])
    {
        return new Writer(new CallbackHandler(array_merge([
            '@DateTime' => [DatetimeAdapter::class, 'writeTime'],
            '@Iterator' => [new IteratorWriter(), 'write'],
        ], $filter)));
    }
}




