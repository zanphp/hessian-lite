# hessian-lite

从hessian官方package删除大量无关的RPC代码，精简出来的序列化与反序列化的库;

## BUGFIX

1. 移植 `https://github.com/cytle/HessianPHP` 修复的BUG
2. 修复Writer::writeArray中区分数组为list与map，遇到 ["key"=>"value"] 判断成 list 的问题
3. 修复Parser::untypedMap、Parser::typedMap反序列化java.util.Map<Object, Object>时, php数组key不能为对象的问题
4. 屏蔽引用功能: ReferenceMap::getReference 永远返回false, method([1,2], [1,2]) 会被序列化成引用, 因为 [1,2] === [1,2], 实际上不一定是引用
5. 修改buffer实现

### BUG4 detail

```text

String testWrapperClassArray(Boolean[] tBooleans, Byte[] tBytes , 
    Character[] tChars, Short[] tShorts, Integer[] tInts, Long[] tLongs, Float[] tFloats, Double[] tDoubles);

$args = [
    new JavaValue("java.lang.Boolean[]", [true, false]),
    new JavaValue("java.lang.Byte[]", [1,2]),
    new JavaValue("java.lang.Character[]", ['a', 'b']),
    new JavaValue("java.lang.Short[]", [1,2]),
    new JavaValue("java.lang.Integer[]", [1,2]),
    new JavaValue("java.lang.Long[]", [1,2]),
    new JavaValue("java.lang.Float[]", [3.14,3.14]),
    new JavaValue("java.lang.Double[]", [3.14,3.14]),
];

java.lang.Long[]
java.lang.Integer[]
java.lang.Short[]
官方默认的实现都会引用到 java.lang.Byte[]

```


## Feature

1. Writer::writeMap 中会将有__type key的数组强制转换为对象,使用对象序列化 

```php
        if (isset($map["__type"])) {
            return $this->writeObject((object)$map);
        }
```


## TODO

1. buffer 修改成 swoole_buffer 实现, 如有必要