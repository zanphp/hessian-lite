# hessian-lite

从hessian官方package删除大量无关的RPC代码，精简出来的序列化与反序列化的库;

## BUGFIX

1. 移植 `https://github.com/cytle/HessianPHP` 修复的BUG
2. 修复Writer::writeArray中区分数组为list与map，遇到 ["key"=>"value"] 判断成 list 的问题
3. 修复Parser::untypedMap、Parser::typedMap反序列化java.util.Map<Object, Object>是, php数组key不能为对象的问题
4. 屏蔽引用功能: ReferenceMap::getReference 永远返回false, method([1,2], [1,2]) 会被序列化成引用, 因为 [1,2] === [1,2], 实际上不一定是引用
5. 修改buffer实现

## TODO

1. buffer 修改成 swoole_buffer 实现, 如有必要