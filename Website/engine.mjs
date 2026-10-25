// SPDX-License-Identifier: MIT
// A deterministic policy model. No DNS, HTTP, or transport operations.
import {CORPUS} from './corpus.mjs';
export const REVISION = 'explorer-2 · '+CORPUS.revision;
const fail = message => { throw new Error(message); };
export function parseIP(input) {
  const s = String(input).toLowerCase();
  if (!s || /[\s%\[\]]/.test(s)) return null;
  if (!s.includes(':')) {
    const a = s.split('.');
    if (a.length !== 4 || a.some(v => !/^(0|[1-9]\d{0,2})$/.test(v) || +v > 255)) return null;
    const value = a.reduce((n,v) => n * 256n + BigInt(v), 0n);
    return {family:4, bits:32, value, canonical:a.join('.')};
  }
  let text = s;
  if (s.includes('.')) {
    const last = s.lastIndexOf(':');
    const v4 = parseIP(s.slice(last + 1));
    if (!v4 || v4.family !== 4) return null;
    text = s.slice(0,last + 1) + (v4.value >> 16n).toString(16) + ':' + (v4.value & 65535n).toString(16);
  }
  if (!/^[a-f0-9:]+$/.test(text) || (text.match(/::/g)||[]).length > 1) return null;
  const compressed = text.includes('::');
  const halves = text.split('::');
  const left = halves[0] ? halves[0].split(':') : [];
  const right = compressed && halves[1] ? halves[1].split(':') : [];
  if ([...left,...right].some(v=>! /^[a-f0-9]{1,4}$/.test(v))) return null;
  if (compressed ? left.length+right.length >= 8 : left.length !== 8) return null;
  const groups = compressed ? [...left,...Array(8-left.length-right.length).fill('0'),...right] : left;
  const nums = groups.map(v=>parseInt(v,16));
  const value = nums.reduce((n,v)=>n*65536n+BigInt(v),0n);
  let start=-1,len=0;
  for(let i=0;i<8;) { if(nums[i]!==0){i++;continue;} let j=i;while(j<8&&nums[j]===0)j++;if(j-i>len&&j-i>=2){start=i;len=j-i;}i=j; }
  const a=nums.map(v=>v.toString(16));
  const canonical = start<0 ? a.join(':') : a.slice(0,start).join(':')+'::'+a.slice(start+len).join(':');
  const mapped = value >> 32n === 65535n;
  const effective = mapped ? parseIP([24n,16n,8n,0n].map(n=>Number((value>>n)&255n)).join('.')) : null;
  return {family:6,bits:128,value,canonical,mapped,effective};
}
export function parseCIDR(text,strict=true) {
  const parts=String(text).split('/');
  if(parts.length>2) fail('Invalid CIDR: '+text);
  const ip=parseIP(parts[0]);
  if(!ip) fail('Invalid IP or CIDR: '+text);
  if(strict&&ip.mapped)fail('Use an IPv4 CIDR instead of an IPv4-mapped IPv6 CIDR: '+text);
  const prefix=parts.length===1 ? ip.bits : /^(0|[1-9]\d{0,2})$/.test(parts[1]) ? Number(parts[1]) : -1;
  if(prefix<0||prefix>ip.bits) fail('Invalid prefix length: '+text);
  const shift=BigInt(ip.bits-prefix);
  if(((ip.value>>shift)<<shift)!==ip.value)fail('CIDR must use the network address: '+text);
  if(strict&&parts[0]!==ip.canonical&&!ip.mapped)fail('CIDR address must use canonical spelling: '+text);
  return {...ip,prefix,network:(ip.value>>shift)<<shift,text:parts[0]+'/'+prefix};
}
export function contains(cidr,ip) {
  const candidates=ip.mapped ? [ip,ip.effective] : [ip];
  return candidates.some(a=>a.family===cidr.family&&(a.value>>BigInt(a.bits-cidr.prefix))===(cidr.network>>BigInt(a.bits-cidr.prefix)));
}
const CLASS_LABELS = {
 unspecified_or_this_network:'Unspecified / this network',private_ipv4:'Private IPv4',shared_cgnat:'Shared address space',loopback:'Loopback',link_local:'Link-local',special_purpose:'Special purpose',documentation:'Documentation',benchmark:'Benchmarking',reserved_or_broadcast:'Reserved / broadcast',transition_or_translation:'Transition / translation',discard:'Discard-only',unique_local_ipv6:'Unique local IPv6',metadata_hard_deny:'Cloud metadata',platform_hard_deny:'Cloud platform',ipv4_mapped_normalize:'IPv4-mapped IPv6',
};
export const RANGES = [...CORPUS.iana_special_rules,...CORPUS.supplemental_rules,...CORPUS.provider_hard_denies].map(r=>({cidr:r.cidr,label:CLASS_LABELS[r.class],classId:r.class,source:(r.source_rfc||[]).map(x=>x.toUpperCase().replace('RFC','RFC ')).join(' · ')||r.source_id||'Repository corpus',type:r.class==='ipv4_mapped_normalize'?'mapped':r.endpoint==='forbidden'?'hard':r.class==='loopback'?'loopback':'private',net:parseCIDR(r.cidr,false)}));
const CLASSIFICATION_RANGES=RANGES.filter(r=>r.type!=='mapped').sort((a,b)=>b.net.prefix-a.net.prefix);
const ALLOCATIONS=CORPUS.ipv6_allocated_global_prefixes.map(r=>parseCIDR(r.cidr)).sort((a,b)=>b.prefix-a.prefix);
export function classify(ip) {
 const effective=ip.mapped?ip.effective:ip;
 const hit=CLASSIFICATION_RANGES.find(r=>contains(r.net,effective));
 if(hit){const {net,...info}=hit;return {...info,mapped:!!ip.mapped,embedded:ip.effective?.canonical};}
 const allocated=effective.family===6?ALLOCATIONS.find(c=>contains(c,effective)):null;
 if(effective.family===6&&!allocated)return {label:'Unallocated / non-global IPv6',classId:'unallocated_or_non_global_ipv6',cidr:'outside allocated global prefixes',source:'Repository corpus',type:'hard',mapped:false};
 return {label:effective.family===4?'Public IPv4':'Global unicast IPv6',classId:'ordinary_global_unicast',cidr:allocated?.text||'outside special-purpose ranges',source:allocated?'IANA global unicast allocation':'Public profile',type:'public',mapped:!!ip.mapped,embedded:ip.effective?.canonical};
}
export function normalize(raw) {
  if(typeof raw!=='string'||!raw||raw.length>4096) fail('Enter a target of 1–4096 characters.');
  if(/[\s\\\u0000-\u001f\u007f]/.test(raw)) fail('Whitespace, controls and backslashes are forbidden.');
  if(raw.includes('#')) fail('URL fragments are forbidden.');
  let s=raw,assumed=false;
  const bareIP=parseIP(s);
  if(bareIP) { s='https://'+(bareIP.family===6?'['+s+']':s);assumed=true; }
  else if(!/^[a-z][a-z\d+.-]*:\/\//i.test(s)){s='https://'+s;assumed=true;}
  const m=s.match(/^([a-z][a-z\d+.-]*):\/\/([^/?#]+)(.*)$/i);
  if(!m) fail('Use an absolute HTTP(S) URL, host, or IP address.');
  const scheme=m[1].toLowerCase();
  if(!['http','https'].includes(scheme)) fail('Only HTTP and HTTPS are permitted.');
  const authority=m[2];
  if(authority.includes('@')) fail('URL userinfo is forbidden.');
  if(authority.includes('%')||/[^\x00-\x7f]/.test(authority)) fail('Host percent-encoding, zone IDs and Unicode hosts are forbidden; use an ASCII A-label.');
  let host,portText,ip;
  if(authority.startsWith('[')) {
    const a=authority.match(/^\[([^\]]+)\](?::(\d+))?$/);
    if(!a) fail('Invalid bracketed IPv6 authority.');
    host=a[1];portText=a[2];ip=parseIP(host);
    if(!ip||ip.family!==6) fail('Invalid IPv6 literal.');
    host=ip.canonical;
  } else {
    const a=authority.match(/^([^:]+)(?::(\d+))?$/);
    if(!a) fail('IPv6 literals in URLs require square brackets.');
    host=a[1].toLowerCase();portText=a[2];
    if(host.endsWith('.'))host=host.slice(0,-1);
    ip=parseIP(host);
    if(!ip) {
      if(/^(?:[\d.]+|0x[0-9a-f.]+)$/i.test(host)||/^(?:\d+|0x[0-9a-f]+)$/i.test(host.split('.').at(-1))) fail('Ambiguous numeric host: use canonical dotted-decimal IPv4.');
      if(host.length>253||host.split('.').some(v=>! /^(?:[a-z\d](?:[a-z\d-]{0,61}[a-z\d])?)$/.test(v))) fail('Invalid ASCII hostname.');
    }
  }
  const port=portText===undefined?(scheme==='https'?443:80):Number(portText);
  if(!Number.isInteger(port)||port<1||port>65535) fail('Port must be between 1 and 65535.');
  const h=ip?.family===6?'['+host+']':host;
  const origin=scheme+'://'+h+((scheme==='https'&&port===443)||(scheme==='http'&&port===80)?'':':'+port);
  return {scheme,host,port,ip,origin,canonical:origin+(m[3]||'/'),assumed};
}
export const list = text => String(text||'').split(/[\s,]+/).filter(Boolean);
export function evaluate(input) {
  const trace=[],addresses=[];
  let target;
  const result=(allowed,code,title,detail)=>({allowed,code,title,detail,target:target?{scheme:target.scheme,host:target.host,port:target.port,origin:target.origin,canonical:target.canonical,assumed:target.assumed}:null,trace,addresses,revision:REVISION});
  const blocked=(code,title,detail)=>{trace.push({label:title,status:'fail',detail});return result(false,code,title,detail);};
  try{target=normalize(input.target);}catch(e){return blocked('TARGET_INVALID','Invalid target',e.message);}
  trace.push({label:'Normalize target',status:'pass',detail:target.origin+(target.assumed?' · HTTPS assumed for a representative host/IP':'')});
  const method=String(input.method||'GET').toUpperCase();
  if(!/^[A-Z]+$/.test(method)||['CONNECT','TRACE'].includes(method))return blocked('METHOD_FORBIDDEN','Method blocked','CONNECT and TRACE are not supported by this explorer.');
  let denies,endpoint=null,core=[];
  try {
    denies=list(input.deniedCidrs).map(value=>parseCIDR(value));
    if(denies.length>128)fail('Use at most 128 operator deny rules.');
    if(input.bound) {
      const ep=normalize(input.endpointOrigin||'');
      if(!/^[a-z][a-z\d+.-]*:\/\//i.test(input.endpointOrigin||'')||/[/?]/.test((input.endpointOrigin||'').replace(/^[a-z]+:\/\//i,'')))fail('Endpoint origin must be absolute, without path or query.');
      const nets=list(input.allowedCidrs).map(value=>parseCIDR(value));
      if(!nets.length||nets.length>128)fail('Provide 1–128 endpoint CIDRs.');
      for(const n of nets) {
        const effectivePrefix=n.mapped?n.prefix-96:n.prefix;
        if(effectivePrefix<(n.mapped||n.family===4?24:64))fail('Endpoint CIDRs must be /24 or narrower for IPv4 and /64 or narrower for IPv6.');
        const loopback=n.family===4?contains(parseCIDR('127.0.0.0/8'),n):contains(n,parseIP('::1'));
        if(loopback&&(!input.allowLoopback||n.prefix!==n.bits))fail('Loopback CIDRs need allowLoopback and an exact /32 or /128, even when another address is requested.');
      }
      const methods=list(input.methods).map(m=>m.toUpperCase());
      if(!methods.length||methods.some(m=>!/^[A-Z]+$/.test(m)||m==='CONNECT'))fail('Provide valid endpoint methods; CONNECT is forbidden.');
      endpoint={origin:ep.origin,nets,methods,allowLoopback:!!input.allowLoopback};
    }
    core=list(input.coreHosts).map(h=>{
      if(/[/?#@*]/.test(h))fail('Core allowlist accepts exact hosts only, without wildcards, ports or paths.');
      const t=normalize(h);
      if(t.port!==443)fail('Core allowlist accepts hosts, not ports.');
      return t.host;
    });
  }catch(e){return blocked('CONFIG_INVALID','Invalid policy configuration',e.message);}
  trace.push({label:'Validate configuration',status:'pass',detail:denies.length+' operator deny rule(s) · '+(endpoint?'bound endpoint':'public client')});
  if(core.length&&!core.includes(target.host))return blocked('CORE_HOST_DENIED','Core host allowlist rejected','The exact target host is absent from the additional Core allowlist.');
  if(endpoint&&(endpoint.origin!==target.origin||!endpoint.methods.includes(method)))return blocked('ENDPOINT_SCOPE','Endpoint scope mismatch','The bound client permits only '+endpoint.methods.join(', ')+' to '+endpoint.origin+'.');
  let ips;
  if(target.ip)ips=[target.ip];
  else {
    const candidates=list(input.addresses);
    if(!candidates.length)return blocked('DNS_EMPTY','No simulated DNS answers','Enter every representative A and AAAA candidate. This tool does not resolve hostnames.');
    if(candidates.length>64)return blocked('DNS_LIMIT','Too many DNS candidates','The draft limits an answer set to 64 usable addresses.');
    ips=candidates.map(parseIP);
    if(ips.some(v=>!v))return blocked('DNS_INVALID','Invalid DNS candidate','Each simulated answer must be a canonical IPv4 or valid IPv6 literal, without brackets.');
  }
  ips=[...new Map(ips.map(ip=>[ip.family+':'+ip.canonical,ip])).values()];
  trace.push({label:target.ip?'Use address literal':'Read simulated DNS answers',status:'pass',detail:ips.length+' candidate(s) · '+(target.ip?'DNS not needed':'manual fixture; no lookup performed')});
  for(const ip of ips) {
    const cls=classify(ip),deny=denies.find(c=>contains(c,ip));
    let allowed=false,code,reason;
    if(deny){code='OPERATOR_DENY';reason='Operator deny '+deny.text+' takes precedence over all grants.';}
    else if(cls.type==='hard'){code='HARD_DENY';reason='Immutable special-purpose / platform block: '+cls.cidr+'.';}
    else if(endpoint) {
      const match=endpoint.nets.filter(c=>contains(c,ip)).sort((a,b)=>{
        const ap=a.mapped?a.prefix-96:a.prefix,bp=b.mapped?b.prefix-96:b.prefix;
        return bp-ap;
      })[0];
      if(!match){code='ENDPOINT_CIDR';reason='Address is outside the bound endpoint’s IP set.';}
      else if(cls.type==='loopback'&&(!endpoint.allowLoopback||match.prefix!==match.bits)){code='LOOPBACK_OPT_IN';reason='Loopback needs allowLoopback and an exact /32 or /128 grant.';}
      else {allowed=true;code='ENDPOINT_ALLOW';reason='Exact origin and method match; IP matches '+match.text+'.';}
    } else if(cls.type==='public'){allowed=true;code='PUBLIC_ALLOW';reason='Regular public unicast outside excluded special-purpose ranges.';}
    else {code='PUBLIC_PROFILE_DENY';reason=cls.label+' requires an explicitly bound, scoped endpoint grant.';}
    addresses.push({ip:ip.canonical,family:ip.family,classification:cls,allowed,code,reason});
  }
  const rejected=addresses.find(a=>!a.allowed);
  if(rejected)return blocked(rejected.code,'Request blocked',rejected.reason+(addresses.length>1?' One rejected candidate blocks the entire answer set.':''));
  trace.push({label:'Apply all address policies',status:'pass',detail:'Every candidate passed. '+(endpoint?'Endpoint '+endpoint.origin:'Public profile')+' applies.'});
  return result(true,endpoint?'ENDPOINT_ALLOW':'PUBLIC_ALLOW','Request allowed',endpoint?'The request matches the bound endpoint’s origin, method and address set.':'Every candidate is regular public unicast outside excluded special-purpose ranges.');
}
