// SPDX-License-Identifier: MIT
import {evaluate,normalize,RANGES,REVISION} from './engine.mjs';
import {DEFAULTS,PRESETS,CASES,scenario,validateCases} from './cases.mjs';
import {CORPUS} from './corpus.mjs';
import {createTranslator,translate} from './i18n.mjs';
import {INPUT_KEYS,parseScenario} from './scenario.mjs';
const query=new URL(location.href);
const preferred=query.searchParams.get('lang');
const translator=createTranslator(document,preferred==='de'||preferred!=='en'&&navigator.language.startsWith('de')?'de':'en');
const $=id=>document.getElementById(id);
const esc=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const keys=INPUT_KEYS;
let current,validation=[],activePreset=0,toastTimer;
function read(){const input=Object.fromEntries(keys.map(k=>[k,$(k).value]));input.bound=$('endpoint-client').checked;input.allowLoopback=$('allowLoopback').checked;return input;}
function apply(input){const s={...DEFAULTS,...input};keys.forEach(k=>$(k).value=String(s[k]??''));$('endpoint-client').checked=!!s.bound;$('public-client').checked=!s.bound;$('allowLoopback').checked=!!s.allowLoopback;update();}
function showView(view){document.querySelectorAll('.view').forEach(el=>el.hidden=el.id!==view+'-view');document.querySelectorAll('[data-view]').forEach(b=>{const selected=b.dataset.view===view;b.classList.toggle('selected',selected);if(selected)b.setAttribute('aria-current','page');else b.removeAttribute('aria-current');});if(view==='validation')runValidation();}
document.querySelectorAll('[data-view]').forEach(b=>b.addEventListener('click',()=>showView(b.dataset.view)));
document.querySelectorAll('[data-language]').forEach(b=>b.addEventListener('click',()=>{
 translator.setLanguage(b.dataset.language);
 const url=new URL(location.href);url.searchParams.set('lang',translator.language);history.replaceState(null,'',url);
}));
$('presets').innerHTML=PRESETS.map((p,i)=>`<button class="preset ${i===0?'active':''}" data-preset="${i}" aria-pressed="${i===0}"><strong>${esc(p.name)}</strong><span>${esc(p.hint)}</span></button>`).join('');
$('presets').addEventListener('click',e=>{const b=e.target.closest('[data-preset]');if(!b)return;activePreset=+b.dataset.preset;apply(scenario(PRESETS[activePreset].input));});
function update(){
 const input=read();current=evaluate(input);
 $('endpoint-fields').hidden=!input.bound;
 $('client-help').textContent=input.bound?'Simulates a trusted client bound to this endpoint. The grant never applies to other clients.':'Public unicast only. Configuring an endpoint does not grant access to ordinary URL importers.';
 let literal=false;
 try{const n=normalize(input.target);literal=!!n.ip;$('input-kind').textContent=literal?`IPv${n.ip.family} literal · DNS not needed`:'Hostname · simulated DNS required';}catch{$('input-kind').textContent='Target needs attention';}
 $('addresses').disabled=literal;$('dns-block').hidden=literal;
 document.querySelectorAll('[data-preset]').forEach(b=>{const i=+b.dataset.preset;const expected=scenario(PRESETS[i].input);const active=keys.every(k=>input[k]===expected[k])&&input.bound===expected.bound&&input.allowLoopback===expected.allowLoopback;b.classList.toggle('active',active);b.setAttribute('aria-pressed',String(active));});
 const r=current,state=r.allowed?'allow':'block';
 $('decision-summary').innerHTML=`<div class="decision-main"><span class="decision-symbol ${state}" aria-hidden="true">${r.allowed?'✓':'×'}</span><div><p class="status-caption">${r.allowed?'ALLOW':'BLOCK'} · ${input.bound?'BOUND ENDPOINT':'PUBLIC CLIENT'}</p><h3 id="decision-title">${esc(r.title)}</h3></div></div><p class="decision-detail">${esc(r.detail)}</p>`;
 $('decision-meta').innerHTML=`<dt>Rule</dt><dd>${esc(r.code)}</dd><dt>Origin</dt><dd>${esc(r.target?.origin||'—')}</dd><dt>Method</dt><dd>${esc(input.method)}</dd>`;
 $('candidate-count').textContent=r.addresses.length?`${r.addresses.length} candidate${r.addresses.length===1?'':'s'}`:'';
 $('address-results').innerHTML=r.addresses.length?r.addresses.map(a=>`<div class="address-row"><div class="address-top"><code>${esc(a.ip)}<span class="address-family">IPv${a.family}</span></code><span class="badge ${a.allowed?'allow':'block'}">${a.allowed?'✓ Pass':'× Block'}</span></div><p class="classification-label">${esc(a.classification.label)}</p><p class="range-label"><span class="mono">${esc(a.classification.cidr)}</span> · ${esc(a.classification.source)}</p>${a.classification.mapped?`<p class="mapped-note">IPv4-mapped IPv6 → <code>${esc(a.classification.embedded)}</code></p>`:''}<p class="address-reason">${esc(a.reason)}</p></div>`).join(''):'<p class="address-empty">No address evaluated. Resolve the target, configuration or DNS issue shown above.</p>';
 $('trace').innerHTML=r.trace.map(t=>`<li class="trace-item"><span class="trace-icon ${t.status}" aria-hidden="true">${t.status==='pass'?'✓':'×'}</span><div><p class="trace-title">${esc(t.label)}</p><p class="trace-detail">${esc(t.detail)}</p></div></li>`).join('');
 translator.render();
}
keys.forEach(k=>$(k).addEventListener('input',update));
document.querySelectorAll('[name=client],#allowLoopback').forEach(el=>el.addEventListener('change',update));
$('request-form').addEventListener('submit',e=>{e.preventDefault();update();toast('Policy evaluated · '+current.code);});
$('reset').addEventListener('click',()=>{const old=read();apply({...DEFAULTS,target:old.target,method:old.method,addresses:old.addresses});toast('Policy reset to the public profile');});
function toast(message){clearTimeout(toastTimer);$('toast').textContent=message;$('toast').hidden=false;translator.render();toastTimer=setTimeout(()=>$('toast').hidden=true,3200);}
$('theme').addEventListener('click',()=>{const dark=document.documentElement.dataset.theme==='dark'||(!document.documentElement.dataset.theme&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.dataset.theme=dark?'light':'dark';$('theme').setAttribute('aria-label',dark?'Switch to dark theme':'Switch to light theme');translator.render();});
$('copy').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(JSON.stringify(current,null,2));toast('Decision JSON copied');}catch{toast('Clipboard unavailable. Use Export scenario to save the inputs.');}});
$('export').addEventListener('click',()=>{const payload={kind:'nr-http-guard-scenario',version:1,model:REVISION,input:read()};const blob=new Blob([JSON.stringify(payload,null,2)],{type:'application/json'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='nr-http-guard-scenario.json';a.click();setTimeout(()=>URL.revokeObjectURL(url),500);toast('Scenario exported');});
$('import-button').addEventListener('click',()=>$('import-file').click());
$('import-file').addEventListener('change',async e=>{const file=e.target.files[0];if(!file)return;try{if(file.size>100000)throw new Error('Scenario file exceeds 100 KB.');const safe=parseScenario(await file.text());apply({...DEFAULTS,...safe});$('action-status').textContent='Scenario imported.';toast('Scenario imported');}catch(err){$('action-status').textContent=err.message;toast(err.message);}finally{e.target.value='';}});
function renderAtlas(){const q=$('range-search').value.toLowerCase(),family=$('family-filter').value;const filtered=RANGES.filter(r=>(family==='all'||r.net.family===+family)&&(r.cidr+' '+r.label+' '+r.source+' '+(window.document.documentElement.lang==='de'?translate(r.label,'de'):'')).toLowerCase().includes(q));$('atlas-body').innerHTML=filtered.map(r=>`<tr><td class="range-cell"><button class="range-example" data-range="${esc(r.cidr.split('/')[0])}" aria-label="Inspect ${esc(r.cidr)}">${esc(r.cidr)} ↗</button></td><td>${esc(r.label)}<small>${esc(r.source)}</small></td><td><span class="badge ${r.type==='mapped'?'info':'block'}">${r.type==='mapped'?'Classify IPv4':'× Blocked'}</span></td><td>${r.type==='private'?'Scoped grant only':r.type==='loopback'?'Exact IP + explicit opt-in':r.type==='mapped'?'Normalize embedded IPv4':'No override'}</td></tr>`).join('');$('atlas-empty').hidden=!!filtered.length;translator.render();}
$('range-search').addEventListener('input',renderAtlas);$('family-filter').addEventListener('change',renderAtlas);
$('atlas-body').addEventListener('click',e=>{const b=e.target.closest('[data-range]');if(!b)return;apply(scenario({target:b.dataset.range}));showView('explorer');$('target').focus();});
function runValidation(){validation=validateCases(evaluate);const pass=validation.filter(v=>v.passed).length;$('validation-stats').innerHTML=`<div class="stat"><strong>${validation.length}</strong><span>Representative cases</span></div><div class="stat success"><strong>${pass}</strong><span>Passed expected checks</span></div><div class="stat"><strong>${validation.length-pass}</strong><span>Failed checks</span></div>`;renderValidation();}
function renderValidation(){const filter=$('test-filter').value;const rows=validation.map((v,i)=>({...v,index:i})).filter(v=>filter==='all'||(filter==='failed'?!v.passed:v.group===filter));$('validation-body').innerHTML=rows.map(v=>`<tr><td>${esc(v.name)}<small>${esc(v.group)}</small></td><td><span class="badge ${v.allowed?'allow':'block'}">${v.allowed?'Allow':'Block'}</span></td><td><code>${esc(v.actual.code)}</code></td><td><span class="badge ${v.passed?'allow':'block'}">${v.passed?'✓ Passed':'× Fail'}</span></td><td><button class="text-button" data-case="${v.index}">Inspect →</button></td></tr>`).join('');$('tests-empty').hidden=!!rows.length;translator.render();}
$('run-tests').addEventListener('click',()=>{runValidation();toast('Validation complete: '+validation.filter(v=>v.passed).length+' / '+validation.length+' passed');});$('test-filter').addEventListener('change',renderValidation);
$('validation-body').addEventListener('click',e=>{const b=e.target.closest('[data-case]');if(!b)return;apply(CASES[+b.dataset.case].input);showView('explorer');$('target').focus();});
$('range-count').textContent=RANGES.length;$('test-count').textContent=CASES.length;
$('corpus-revision').textContent='Corpus revision: '+CORPUS.revision;
apply(DEFAULTS);renderAtlas();runValidation();
