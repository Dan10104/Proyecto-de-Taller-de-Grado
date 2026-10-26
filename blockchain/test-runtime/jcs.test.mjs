import { test } from 'node:test';
import assert from 'node:assert/strict';
import { serialize, parseStrict } from '../runtime/jcs.mjs';

test('strict JSON rejects duplicate keys, comments, trailing commas and invalid Unicode', () => {
  for (const input of ['{"a":1,"a":2}', '{"a":1,"\\u0061":2}', '{"nested":[{"key":1,"key":2}]}', '{/*comment*/"a":1}', '{"a":1,}', '', 'true false']) assert.throws(()=>parseStrict(input));
  assert.equal(serialize(parseStrict('{"x":{"a":1},"y":{"a":2}}')), '{"x":{"a":1},"y":{"a":2}}');
  assert.throws(()=>serialize(parseStrict('"\\ud800"')));
  assert.throws(()=>serialize(parseStrict('1e400')));
});

test('RFC8785 sample numbers, escaping, UTF-16 ordering, null and empty', () => {
  assert.equal(serialize({numbers: [333333333.33333329, 1e30, 4.50, 2e-3, 1e-27]}), '{"numbers":[333333333.3333333,1e+30,4.5,0.002,1e-27]}');
  assert.equal(serialize([1e20, 1e21, 1e-6, 1e-7, -0, '\u2028', '\u000f\n"\\', null, '']), '[100000000000000000000,1e+21,0.000001,1e-7,0,"\u2028","\\u000f\\n\\"\\\\",null,""]');
  const keys = ['\u20ac','\r','\ufb33','1','\ud83d\ude00','\u0080','\u00f6'];
  assert.equal(serialize(Object.fromEntries(keys.map(k=>[k,0]))), '{"\\r":0,"1":0,"\u0080":0,"\u00f6":0,"\u20ac":0,"\ud83d\ude00":0,"\ufb33":0}');
  assert.notEqual(serialize({text:'é'}), serialize({text:'e\u0301'}));
  for (const bad of [NaN, Infinity, -Infinity, '\ud800', {'\udfff': 1}]) assert.throws(()=>serialize(bad));
});

test('RFC8785 Appendix B binary64 vectors', () => {
  for (const [hex, expected] of [
    ['0000000000000000','0'],['8000000000000000','0'],['0000000000000001','5e-324'],['8000000000000001','-5e-324'],
    ['7fefffffffffffff','1.7976931348623157e+308'],['ffefffffffffffff','-1.7976931348623157e+308'],
    ['4340000000000000','9007199254740992'],['c340000000000000','-9007199254740992'],
    ['4430000000000000','295147905179352830000'],['44b52d02c7e14af5','9.999999999999997e+22'],
    ['44b52d02c7e14af6','1e+23'],['44b52d02c7e14af7','1.0000000000000001e+23'],
    ['444b1ae4d6e2ef4e','999999999999999700000'],['444b1ae4d6e2ef4f','999999999999999900000'],
    ['444b1ae4d6e2ef50','1e+21'],['3eb0c6f7a0b5ed8c','9.999999999999997e-7'],['3eb0c6f7a0b5ed8d','0.000001'],
    ['41b3de4355555553','333333333.3333332'],['41b3de4355555554','333333333.33333325'],
    ['41b3de4355555555','333333333.3333333'],['41b3de4355555556','333333333.3333334'],['41b3de4355555557','333333333.33333343'],
    ['becbf647612f3696','-0.0000033333333333333333'],['43143ff3c1cb0959','1424953923781206.2'],
  ]) assert.equal(serialize(Buffer.from(hex,'hex').readDoubleBE()), expected, hex);
});
