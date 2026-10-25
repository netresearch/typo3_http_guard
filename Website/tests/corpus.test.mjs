// SPDX-License-Identifier: MIT
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createHash} from 'node:crypto';
import {CORPUS,CORPUS_SHA256,ADDRESS_CASES} from '../../.Build/pages/corpus.mjs';
import {classify,parseIP} from '../../.Build/pages/engine.mjs';
test('Built rules are the exact current runtime corpus',()=>{
 const raw=readFileSync(new URL('../../Resources/Private/HttpGuard/data/security-corpus/address-rules.json',import.meta.url));
 assert.equal(createHash('sha256').update(raw).digest('hex'),CORPUS_SHA256);
 assert.deepEqual(CORPUS,JSON.parse(raw));
});
for(const c of ADDRESS_CASES)test('Repository classification: '+c.id,()=>{
 const ip=parseIP(c.input.address);assert.ok(ip);
 const actual=classify(ip);
 assert.equal(actual.classId,c.expected.address_class);
 assert.equal(actual.type==='public',c.expected.public_decision==='allow');
 assert.equal(ip.effective?.canonical||ip.canonical,c.expected.normalized_address);
 if(c.expected.matched_cidr)assert.equal(actual.cidr,c.expected.matched_cidr);
});
