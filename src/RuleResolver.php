<?php

namespace ZanPHP\HessianLite;


/**
 * Contains the sequence of rules and start symbols that match the rules.
 * Resolves a rule based on a symbol and optionally checks for expected outcomes;
 * @author vsayajin
 */
class RuleResolver
{
    const T_BINARY = "binary";
    const T_BOOLEAN = "boolean";
    const T_DATE = "date";
    const T_DOUBLE = "double";
    const T_INTEGER = "integer";
    const T_LST = "list";
    const T_LONG = "long";
    const T_MAP = "map";
    const T_NULL = "null";
    const T_OBJECT = "object";
    const T_REFERENCE = "reference";
    const T_RESERVED = "reserved";
    const T_STRING = "string";
    const T_TERMINATOR = "terminator";

    private $rules = [];
    private $symbols = [];

    public function __construct()
    {
        $rules[0] = new ParsingRule("binary", "binary0", "binary data length 0-16");
        $rules[1] = new ParsingRule("binary", "binary1", "binary data length 0-1023");
        $rules[2] = new ParsingRule("binary", "binaryLong", "8-bit binary data non-final chunk ('A')");
        $rules[3] = new ParsingRule("binary", "binaryLong", "8-bit binary data final chunk ('B')");
        $rules[4] = new ParsingRule("boolean", "bool", "boolean false ('F')");
        $rules[5] = new ParsingRule("boolean", "bool", "boolean true ('T')");
        $rules[6] = new ParsingRule("date", "date", "64-bit UTC millisecond date");
        $rules[7] = new ParsingRule("date", "compactDate", "32-bit UTC minute date");
        $rules[8] = new ParsingRule("double", "double64", "64-bit IEEE encoded double ('D')");
        $rules[9] = new ParsingRule("double", "double1", "double 0.0");
        $rules[10] = new ParsingRule("double", "double1", "double 1.0");
        $rules[11] = new ParsingRule("double", "double1", "double represented as byte (-128.0 to 127.0)");
        $rules[12] = new ParsingRule("double", "double2", "double represented as short (-32768.0 to 327676.0)");
        $rules[13] = new ParsingRule("double", "double4", "double represented as float");
        $rules[14] = new ParsingRule("integer", "parseInt", "32-bit signed integer ('I')");
        $rules[15] = new ParsingRule("integer", "compactInt1", "one-octet compact int (-x10 to x3f, x90 is 0)");
        $rules[16] = new ParsingRule("integer", "compactInt2", "two-octet compact int (-x800 to x7ff)");
        $rules[17] = new ParsingRule("integer", "compactInt3", "three-octet compact int (-x40000 to x3ffff)");
        $rules[18] = new ParsingRule("list", "vlenList", "variable-length list/vector ('U')");
        $rules[19] = new ParsingRule("list", "flenList", "fixed-length list/vector ('V')");
        $rules[20] = new ParsingRule("list", "vlenUntypedList", "variable-length untyped list/vector ('W')");
        $rules[21] = new ParsingRule("list", "flenUntypedList", "fixed-length untyped list/vector ('X')");
        $rules[22] = new ParsingRule("list", "directListTyped", "fixed list with direct length");
        $rules[23] = new ParsingRule("list", "directListUntyped", "fixed untyped list with direct length");
        $rules[24] = new ParsingRule("long", "long3", "three-octet compact long (-x40000 to x3ffff)");
        $rules[25] = new ParsingRule("long", "long64", "64-bit signed long integer ('L')");
        $rules[26] = new ParsingRule("long", "long32", "long encoded as 32-bit int ('Y')");
        $rules[27] = new ParsingRule("long", "long1", "one-octet compact long (-x8 to xf, xe0 is 0)");
        $rules[28] = new ParsingRule("long", "long2", "two-octet compact long (-x800 to x7ff, xf8 is 0)");
        $rules[29] = new ParsingRule("map", "untypedMap", "untyped map ('H')");
        $rules[30] = new ParsingRule("map", "typedMap", "map with type ('M')");
        $rules[31] = new ParsingRule("null", "parseNull", "null ('N')");
        $rules[32] = new ParsingRule("object", "typeDefinition", "object type definition ('C')");
        $rules[33] = new ParsingRule("object", "objectInstance", "object instance ('O')");
        $rules[34] = new ParsingRule("object", "objectDirect", "object with direct type");
        $rules[35] = new ParsingRule("reference", "reference", "reference to map/list/object - integer ('Q')");
        $rules[36] = new ParsingRule("reserved", "reserved", "reserved (expansion/escape)");
        $rules[37] = new ParsingRule("reserved", "reserved", "reserved");
        $rules[38] = new ParsingRule("reserved", "reserved", "reserved");
        $rules[39] = new ParsingRule("reserved", "reserved", "reserved");
        $rules[40] = new ParsingRule("string", "string0", "utf-8 string length 0-32");
        $rules[41] = new ParsingRule("string", "string1", "utf-8 string length 0-1023");
        $rules[42] = new ParsingRule("string", "stringLong", "utf-8 string non-final chunk ('R')");
        $rules[43] = new ParsingRule("string", "stringLong", "utf-8 string final chunk ('S')");
        $rules[44] = new ParsingRule("terminator", "terminator", "list/map terminator ('Z')");
        $symbols[32] = 0;
        $symbols[33] = 0;
        $symbols[34] = 0;
        $symbols[35] = 0;
        $symbols[36] = 0;
        $symbols[37] = 0;
        $symbols[38] = 0;
        $symbols[39] = 0;
        $symbols[40] = 0;
        $symbols[41] = 0;
        $symbols[42] = 0;
        $symbols[43] = 0;
        $symbols[44] = 0;
        $symbols[45] = 0;
        $symbols[46] = 0;
        $symbols[47] = 0;
        $symbols[52] = 1;
        $symbols[53] = 1;
        $symbols[54] = 1;
        $symbols[55] = 1;
        $symbols[65] = 2;
        $symbols[66] = 3;
        $symbols[70] = 4;
        $symbols[84] = 5;
        $symbols[74] = 6;
        $symbols[75] = 7;
        $symbols[68] = 8;
        $symbols[91] = 9;
        $symbols[92] = 10;
        $symbols[93] = 11;
        $symbols[94] = 12;
        $symbols[95] = 13;
        $symbols[73] = 14;
        $symbols[128] = 15;
        $symbols[129] = 15;
        $symbols[130] = 15;
        $symbols[131] = 15;
        $symbols[132] = 15;
        $symbols[133] = 15;
        $symbols[134] = 15;
        $symbols[135] = 15;
        $symbols[136] = 15;
        $symbols[137] = 15;
        $symbols[138] = 15;
        $symbols[139] = 15;
        $symbols[140] = 15;
        $symbols[141] = 15;
        $symbols[142] = 15;
        $symbols[143] = 15;
        $symbols[144] = 15;
        $symbols[145] = 15;
        $symbols[146] = 15;
        $symbols[147] = 15;
        $symbols[148] = 15;
        $symbols[149] = 15;
        $symbols[150] = 15;
        $symbols[151] = 15;
        $symbols[152] = 15;
        $symbols[153] = 15;
        $symbols[154] = 15;
        $symbols[155] = 15;
        $symbols[156] = 15;
        $symbols[157] = 15;
        $symbols[158] = 15;
        $symbols[159] = 15;
        $symbols[160] = 15;
        $symbols[161] = 15;
        $symbols[162] = 15;
        $symbols[163] = 15;
        $symbols[164] = 15;
        $symbols[165] = 15;
        $symbols[166] = 15;
        $symbols[167] = 15;
        $symbols[168] = 15;
        $symbols[169] = 15;
        $symbols[170] = 15;
        $symbols[171] = 15;
        $symbols[172] = 15;
        $symbols[173] = 15;
        $symbols[174] = 15;
        $symbols[175] = 15;
        $symbols[176] = 15;
        $symbols[177] = 15;
        $symbols[178] = 15;
        $symbols[179] = 15;
        $symbols[180] = 15;
        $symbols[181] = 15;
        $symbols[182] = 15;
        $symbols[183] = 15;
        $symbols[184] = 15;
        $symbols[185] = 15;
        $symbols[186] = 15;
        $symbols[187] = 15;
        $symbols[188] = 15;
        $symbols[189] = 15;
        $symbols[190] = 15;
        $symbols[191] = 15;
        $symbols[192] = 16;
        $symbols[193] = 16;
        $symbols[194] = 16;
        $symbols[195] = 16;
        $symbols[196] = 16;
        $symbols[197] = 16;
        $symbols[198] = 16;
        $symbols[199] = 16;
        $symbols[200] = 16;
        $symbols[201] = 16;
        $symbols[202] = 16;
        $symbols[203] = 16;
        $symbols[204] = 16;
        $symbols[205] = 16;
        $symbols[206] = 16;
        $symbols[207] = 16;
        $symbols[208] = 17;
        $symbols[209] = 17;
        $symbols[210] = 17;
        $symbols[211] = 17;
        $symbols[212] = 17;
        $symbols[213] = 17;
        $symbols[214] = 17;
        $symbols[215] = 17;
        $symbols[85] = 18;
        $symbols[86] = 19;
        $symbols[87] = 20;
        $symbols[88] = 21;
        $symbols[112] = 22;
        $symbols[113] = 22;
        $symbols[114] = 22;
        $symbols[115] = 22;
        $symbols[116] = 22;
        $symbols[117] = 22;
        $symbols[118] = 22;
        $symbols[119] = 22;
        $symbols[120] = 23;
        $symbols[121] = 23;
        $symbols[122] = 23;
        $symbols[123] = 23;
        $symbols[124] = 23;
        $symbols[125] = 23;
        $symbols[126] = 23;
        $symbols[127] = 23;
        $symbols[56] = 24;
        $symbols[57] = 24;
        $symbols[58] = 24;
        $symbols[59] = 24;
        $symbols[60] = 24;
        $symbols[61] = 24;
        $symbols[62] = 24;
        $symbols[63] = 24;
        $symbols[76] = 25;
        $symbols[89] = 26;
        $symbols[216] = 27;
        $symbols[217] = 27;
        $symbols[218] = 27;
        $symbols[219] = 27;
        $symbols[220] = 27;
        $symbols[221] = 27;
        $symbols[222] = 27;
        $symbols[223] = 27;
        $symbols[224] = 27;
        $symbols[225] = 27;
        $symbols[226] = 27;
        $symbols[227] = 27;
        $symbols[228] = 27;
        $symbols[229] = 27;
        $symbols[230] = 27;
        $symbols[231] = 27;
        $symbols[232] = 27;
        $symbols[233] = 27;
        $symbols[234] = 27;
        $symbols[235] = 27;
        $symbols[236] = 27;
        $symbols[237] = 27;
        $symbols[238] = 27;
        $symbols[239] = 27;
        $symbols[240] = 28;
        $symbols[241] = 28;
        $symbols[242] = 28;
        $symbols[243] = 28;
        $symbols[244] = 28;
        $symbols[245] = 28;
        $symbols[246] = 28;
        $symbols[247] = 28;
        $symbols[248] = 28;
        $symbols[249] = 28;
        $symbols[250] = 28;
        $symbols[251] = 28;
        $symbols[252] = 28;
        $symbols[253] = 28;
        $symbols[254] = 28;
        $symbols[255] = 28;
        $symbols[72] = 29;
        $symbols[77] = 30;
        $symbols[78] = 31;
        $symbols[67] = 32;
        $symbols[79] = 33;
        $symbols[96] = 34;
        $symbols[97] = 34;
        $symbols[98] = 34;
        $symbols[99] = 34;
        $symbols[100] = 34;
        $symbols[101] = 34;
        $symbols[102] = 34;
        $symbols[103] = 34;
        $symbols[104] = 34;
        $symbols[105] = 34;
        $symbols[106] = 34;
        $symbols[107] = 34;
        $symbols[108] = 34;
        $symbols[109] = 34;
        $symbols[110] = 34;
        $symbols[111] = 34;
        $symbols[81] = 35;
        $symbols[64] = 36;
        $symbols[69] = 37;
        $symbols[71] = 38;
        $symbols[80] = 39;
        $symbols[0] = 40;
        $symbols[1] = 40;
        $symbols[2] = 40;
        $symbols[3] = 40;
        $symbols[4] = 40;
        $symbols[5] = 40;
        $symbols[6] = 40;
        $symbols[7] = 40;
        $symbols[8] = 40;
        $symbols[9] = 40;
        $symbols[10] = 40;
        $symbols[11] = 40;
        $symbols[12] = 40;
        $symbols[13] = 40;
        $symbols[14] = 40;
        $symbols[15] = 40;
        $symbols[16] = 40;
        $symbols[17] = 40;
        $symbols[18] = 40;
        $symbols[19] = 40;
        $symbols[20] = 40;
        $symbols[21] = 40;
        $symbols[22] = 40;
        $symbols[23] = 40;
        $symbols[24] = 40;
        $symbols[25] = 40;
        $symbols[26] = 40;
        $symbols[27] = 40;
        $symbols[28] = 40;
        $symbols[29] = 40;
        $symbols[30] = 40;
        $symbols[31] = 40;
        $symbols[48] = 41;
        $symbols[49] = 41;
        $symbols[50] = 41;
        $symbols[51] = 41;
        $symbols[82] = 42;
        $symbols[83] = 43;
        $symbols[90] = 44;


        $this->rules = $rules;
        $this->symbols = $symbols;
    }

    /**
     * Takes a symbol and resolves a parsing rule to apply. Optionally it can
     * check if the type resolved matches an expected type
     * @param $symbol
     * @param string $typeExpected
     * @return ParsingRule rule to evaluate
     * @throws ParsingException
     */
    public function resolveSymbol($symbol, $typeExpected = '')
    {
        $num = ord($symbol);
        if (!isset($this->symbols[$num])) {
            throw new ParsingException("Code not recognized: 0x" . dechex($num));
        }
        $ruleIndex = $this->symbols[$num];
        $rule = $this->rules[$ruleIndex];
        if ($typeExpected) {
            if (!$this->checkType($rule, $typeExpected)) {
                throw new ParsingException("Type $typeExpected expected");
            }
        }
        return $rule;
    }

    private function checkType(ParsingRule $rule, $types)
    {
        $checks = explode(',', $types);
        foreach ($checks as $type) {
            if ($rule->type === trim($type)) {
                return true;
            }
        }
        return false;
    }
}