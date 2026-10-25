// SPDX-License-Identifier: MIT
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {DE,translate} from '../../.Build/pages/i18n.mjs';
import {evaluate} from '../../.Build/pages/engine.mjs';
import {CASES,PRESETS} from '../../.Build/pages/cases.mjs';
test('German covers scenario labels and every decision explanation',()=>{
 for(const p of PRESETS)for(const text of [p.name,p.hint])assert.ok(text in DE,text);
 for(const c of CASES){
  if(c.group!=='Repository')assert.ok(c.name in DE,c.name);
  const r=evaluate(c.input);
  for(const text of [r.title,r.detail,...r.addresses.map(a=>a.reason)])assert.notEqual(translate(text,'de'),text,text);
 }
});
test('English and machine identifiers remain stable',()=>{
 for(const [source] of Object.entries(DE))assert.equal(translate(source,'en'),source);
 for(const value of ['PUBLIC_ALLOW','10.23.4.12/32','https://erp.internal.example:8443','ADDR-001-first','::ffff:7f00:1'])assert.equal(translate(value,'de'),value);
});
test('Rendered target values do not get translated',()=>{
 assert.equal(translate('IPv4-mapped IPv6 → 192.168.1.10','de'),'Eingebettetes IPv4 → 192.168.1.10');
 assert.equal(translate('Operator deny 10.0.0.0/8 takes precedence over all grants.','de'),'Die betreiberweite Sperre 10.0.0.0/8 hat Vorrang vor allen Freigaben.');
});
test('Every static English text node has a German translation or an explicit identifier exemption',()=>{
 const html=readFileSync(new URL('../../.Build/pages/index.html',import.meta.url),'utf8');
 const literal=/^(?:\d+|[→↗◐]|DE|EN|GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS|CONNECT|HTTP \/ HTTPS|IPv4|IPv6|IPv4 \+ IPv6|A \+ AAAA|10\.23\.4\.12\/32|2000::\/3|::ffff:192\.168\.1\.1|netresearch\/nr-http-guard|nr-http-guard|Netresearch DTT GmbH|GitHub ↗)$/;
 const texts=[...html.matchAll(/>([^<>]+)</g)].map(m=>m[1].trim()).filter(Boolean);
 for(const text of texts)assert.ok(text in DE||literal.test(text),'Missing static German text: '+text);
});
test('Public artifact contains only local application assets and prohibits target connections',()=>{
 const html=readFileSync(new URL('../../.Build/pages/index.html',import.meta.url),'utf8');
 assert.match(html,/connect-src 'none'/);
 assert.ok(!html.includes('Private workspace'));
 assert.match(html,/data-language="de"/);assert.match(html,/data-language="en"/);
 for(const m of html.matchAll(/(?:src|href)="([^"]+)"/g))if(!m[1].startsWith('https:')&&!m[1].startsWith('#'))assert.ok(m[1].startsWith('./'),m[1]);
});
