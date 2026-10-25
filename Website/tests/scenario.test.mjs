// SPDX-License-Identifier: MIT
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {parseScenario} from '../../.Build/pages/scenario.mjs';
import {DEFAULTS} from '../../.Build/pages/cases.mjs';
import {translate} from '../../.Build/pages/i18n.mjs';
test('Scenario export/import roundtrip preserves inputs',()=>{
 assert.deepEqual(parseScenario(JSON.stringify({kind:'nr-http-guard-scenario',version:1,input:DEFAULTS})),DEFAULTS);
});
for(const value of [null,[],{kind:'nr-http-guard-scenario',version:1,input:[]},{kind:'nr-http-guard-scenario',version:1,input:{bound:'false'}}])test('Malformed scenario gives a translated format error: '+JSON.stringify(value),()=>{
 let caught;try{parseScenario(JSON.stringify(value));}catch(error){caught=error;}
 assert.ok(caught);assert.notEqual(translate(caught.message,'de'),caught.message);
});
