import {test} from 'node:test';
import assert from 'node:assert/strict';
import {evaluate,parseIP,parseCIDR,contains,normalize} from '../../.Build/pages/engine.mjs';
import {CASES} from '../../.Build/pages/cases.mjs';
for(const c of CASES)test(c.group+': '+c.name,()=>{
 const r=evaluate(c.input);assert.equal(r.allowed,c.allowed,r.detail);assert.equal(r.code,c.code,r.detail);
});
test('CIDR boundary checks use bits rather than string prefixes',()=>{
 assert.equal(contains(parseCIDR('10.0.0.0/8'),parseIP('100.1.2.3')),false);
 assert.equal(contains(parseCIDR('fe80::/10'),parseIP('febf:ffff::1')),true);
 assert.equal(contains(parseCIDR('fe80::/10'),parseIP('fec0::1')),false);
});
test('IPv6 compression and embedded IPv4 normalization',()=>{
 assert.equal(parseIP('2001:4860:0000:0000:0000:0000:0000:8888').canonical,'2001:4860::8888');
 assert.equal(parseIP('::ffff:192.168.1.1').effective.canonical,'192.168.1.1');
 assert.equal(normalize('https://[0:0:0:0:0:ffff:7f00:1]:443').origin,'https://[::ffff:7f00:1]');
});
test('Policy output is serializable without leaking BigInt internals',()=>assert.doesNotThrow(()=>JSON.stringify(evaluate({target:'8.8.8.8'}))));
