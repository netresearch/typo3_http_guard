// SPDX-License-Identifier: MIT
import {ADDRESS_CASES} from './corpus.mjs';
const base={method:'GET',addresses:'',deniedCidrs:'',bound:false,endpointOrigin:'https://erp.internal.example:8443',allowedCidrs:'10.23.4.12/32',methods:'GET, POST',allowLoopback:false,coreHosts:''};
export const DEFAULTS={...base,target:'https://[2606:4700:4700::1111]/'};
export const PRESETS=[
 {name:'Public IPv6',hint:'Global unicast',input:{target:'https://[2606:4700:4700::1111]/'}},
 {name:'Loopback',hint:'Local machine',input:{target:'http://127.0.0.1:8080/'}},
 {name:'Internal ERP',hint:'Scoped exception',input:{target:'https://erp.internal.example:8443/orders',addresses:'10.23.4.12',bound:true}},
 {name:'Cloud metadata',hint:'Immutable deny',input:{target:'http://169.254.169.254/latest/meta-data/'}},
 {name:'Mixed DNS',hint:'One bad candidate',input:{target:'https://api.example.com/',addresses:'93.184.215.14\nfd12:3456::1'}},
 {name:'Mapped IPv4',hint:'Same IPv4 policy',input:{target:'http://[::ffff:192.168.1.10]/'}},
];
export const scenario = input => ({...base,...input});
const vectors=[
 ['Public IPv4','8.8.8.8',true,'PUBLIC_ALLOW'],
 ['Public IPv6','2606:4700:4700::1111',true,'PUBLIC_ALLOW'],
 ['Unspecified IPv4','0.0.0.0',false,'PUBLIC_PROFILE_DENY'],
 ['This network','0.1.2.3',false,'HARD_DENY'],
 ['RFC1918 /8','10.0.0.1',false,'PUBLIC_PROFILE_DENY'],
 ['RFC1918 /12 start','172.16.0.0',false,'PUBLIC_PROFILE_DENY'],
 ['RFC1918 /12 end','172.31.255.255',false,'PUBLIC_PROFILE_DENY'],
 ['Outside RFC1918 /12','172.32.0.1',true,'PUBLIC_ALLOW'],
 ['RFC1918 /16','192.168.255.255',false,'PUBLIC_PROFILE_DENY'],
 ['CGNAT start','100.64.0.0',false,'PUBLIC_PROFILE_DENY'],
 ['CGNAT end','100.127.255.255',false,'PUBLIC_PROFILE_DENY'],
 ['Outside CGNAT','100.128.0.1',true,'PUBLIC_ALLOW'],
 ['IPv4 loopback','127.0.0.1',false,'PUBLIC_PROFILE_DENY'],
 ['Entire loopback /8','127.255.255.254',false,'PUBLIC_PROFILE_DENY'],
 ['IPv4 link-local','169.254.10.1',false,'HARD_DENY'],
 ['AWS metadata IPv4','169.254.169.254',false,'HARD_DENY'],
 ['AWS metadata IPv6','fd00:ec2::254',false,'HARD_DENY'],
 ['Alibaba metadata','100.100.100.200',false,'HARD_DENY'],
 ['Azure platform','168.63.129.16',false,'HARD_DENY'],
 ['Protocol assignments','192.0.0.9',false,'HARD_DENY'],
 ['TEST-NET-1','192.0.2.1',false,'HARD_DENY'],
 ['TEST-NET-2','198.51.100.1',false,'HARD_DENY'],
 ['TEST-NET-3','203.0.113.1',false,'HARD_DENY'],
 ['Benchmark IPv4','198.19.255.255',false,'HARD_DENY'],
 ['Multicast IPv4','224.0.0.1',false,'HARD_DENY'],
 ['Reserved IPv4','240.1.2.3',false,'HARD_DENY'],
 ['Limited broadcast','255.255.255.255',false,'HARD_DENY'],
 ['IPv6 unspecified','::',false,'HARD_DENY'],
 ['IPv6 loopback','::1',false,'PUBLIC_PROFILE_DENY'],
 ['ULA fc','fc00::1',false,'PUBLIC_PROFILE_DENY'],
 ['ULA fd','fdff:ffff::1',false,'PUBLIC_PROFILE_DENY'],
 ['IPv6 link-local start','fe80::1',false,'HARD_DENY'],
 ['IPv6 link-local end','febf:ffff::1',false,'HARD_DENY'],
 ['IPv6 multicast','ff02::1',false,'HARD_DENY'],
 ['IPv6 documentation','2001:db8::1',false,'HARD_DENY'],
 ['IPv6 documentation 3fff','3fff:fff::1',false,'HARD_DENY'],
 ['IPv6 benchmark','2001:2::1',false,'HARD_DENY'],
 ['NAT64 WKP','64:ff9b::808:808',false,'HARD_DENY'],
 ['NAT64 local','64:ff9b:1::a00:1',false,'HARD_DENY'],
 ['6to4','2002:0808:0808::1',false,'HARD_DENY'],
 ['Teredo','2001:0:1234::1',false,'HARD_DENY'],
 ['Deprecated IPv4-compatible','::192.168.1.1',false,'HARD_DENY'],
 ['Discard-only','100::1',false,'HARD_DENY'],
 ['Dummy IPv6','100:0:0:1::1',false,'HARD_DENY'],
 ['Unallocated IPv6','4000::1',false,'HARD_DENY'],
 ['Mapped private','::ffff:192.168.1.1',false,'PUBLIC_PROFILE_DENY'],
 ['Mapped public','::ffff:8.8.8.8',true,'PUBLIC_ALLOW'],
 ['Mapped hex loopback','::ffff:7f00:1',false,'PUBLIC_PROFILE_DENY'],
 ['Mapped metadata','::ffff:169.254.169.254',false,'HARD_DENY'],
];
// Unspecified 0.0.0.0 is hard-blocked, like the rest of 0/8.
vectors.find(v=>v[0]==='Unspecified IPv4')[3]='HARD_DENY';
export const CASES=vectors.map(([name,target,allowed,code])=>({name,group:'Addresses',input:scenario({target}),allowed,code}));
const add=(name,input,allowed,code,group='Policy')=>CASES.push({name,group,input:scenario(input),allowed,code});
const erp={target:'https://erp.internal.example:8443/orders',addresses:'10.23.4.12',bound:true};
add('Scoped private endpoint',erp,true,'ENDPOINT_ALLOW');
add('No grant despite static answer',{...erp,bound:false},false,'PUBLIC_PROFILE_DENY');
add('Operator deny wins',{...erp,deniedCidrs:'10.23.4.0/24'},false,'OPERATOR_DENY');
add('Wrong origin port',{...erp,target:'https://erp.internal.example:9443'},false,'ENDPOINT_SCOPE');
add('Wrong origin scheme',{...erp,target:'http://erp.internal.example:8443'},false,'ENDPOINT_SCOPE');
add('Wrong method',{...erp,method:'DELETE'},false,'ENDPOINT_SCOPE');
add('Address outside endpoint set',{...erp,addresses:'10.23.4.13'},false,'ENDPOINT_CIDR');
add('Endpoint does not grant arbitrary public targets',{...erp,addresses:'8.8.8.8'},false,'ENDPOINT_CIDR');
add('No broad private grants',{...erp,allowedCidrs:'10.0.0.0/8'},false,'CONFIG_INVALID');
add('Mapped broad grant rejected',{...erp,allowedCidrs:'::ffff:0:0/96'},false,'CONFIG_INVALID');
add('Narrow IPv6 endpoint',{target:'https://[fd12::1]',bound:true,endpointOrigin:'https://[fd12::1]',allowedCidrs:'fd12::/64'},true,'ENDPOINT_ALLOW');
add('Loopback requires opt-in',{target:'http://127.0.0.1',bound:true,endpointOrigin:'http://127.0.0.1',allowedCidrs:'127.0.0.1/32'},false,'CONFIG_INVALID');
add('Loopback explicit opt-in',{target:'http://127.0.0.1',bound:true,endpointOrigin:'http://127.0.0.1',allowedCidrs:'127.0.0.1/32',allowLoopback:true},true,'ENDPOINT_ALLOW');
add('Loopback requires exact host grant',{target:'http://127.0.0.1',bound:true,endpointOrigin:'http://127.0.0.1',allowedCidrs:'127.0.0.0/24',allowLoopback:true},false,'CONFIG_INVALID');
add('Broad loopback CIDR invalidates exact grant',{target:'http://127.0.0.1',bound:true,endpointOrigin:'http://127.0.0.1',allowedCidrs:'127.0.0.0/24\n127.0.0.1/32',allowLoopback:true},false,'CONFIG_INVALID');
add('IPv6 loopback opt-in',{target:'http://[::1]',bound:true,endpointOrigin:'http://[::1]',allowedCidrs:'::1/128',allowLoopback:true},true,'ENDPOINT_ALLOW');
add('Metadata grant cannot override block',{target:'http://[fd00:ec2::254]',bound:true,endpointOrigin:'http://[fd00:ec2::254]',allowedCidrs:'fd00:ec2::/64'},false,'HARD_DENY');
add('Public operator deny',{target:'8.8.8.8',deniedCidrs:'8.8.8.0/24'},false,'OPERATOR_DENY');
add('Mapped operator deny',{target:'::ffff:8.8.8.8',deniedCidrs:'8.8.8.0/24'},false,'OPERATOR_DENY');
add('Core allowlist intersects',{...erp,coreHosts:'other.example'},false,'CORE_HOST_DENIED');
add('Core allowlist does not grant private',{...erp,bound:false,coreHosts:'erp.internal.example'},false,'PUBLIC_PROFILE_DENY');
add('All DNS candidates pass',{target:'api.example.com',addresses:'8.8.8.8\n2606:4700:4700::1111'},true,'PUBLIC_ALLOW','DNS');
add('Mixed public/private DNS',{target:'api.example.com',addresses:'8.8.8.8\n10.0.0.1'},false,'PUBLIC_PROFILE_DENY','DNS');
add('Mixed public/loopback IPv6 DNS',{target:'api.example.com',addresses:'8.8.8.8\n::1'},false,'PUBLIC_PROFILE_DENY','DNS');
add('Empty DNS fails closed',{target:'api.example.com'},false,'DNS_EMPTY','DNS');
add('Invalid DNS fails closed',{target:'api.example.com',addresses:'8.8.8.8\n999.1.1.1'},false,'DNS_INVALID','DNS');
add('Too many DNS candidates',{target:'api.example.com',addresses:Array(65).fill('8.8.8.8').join('\n')},false,'DNS_LIMIT','DNS');
for(const [name,target] of [ ['Integer IP','2130706433'],['Hex IP','0x7f000001'],['Octal IPv4','0177.0.0.1'],['Short IPv4','127.1'],['Userinfo','https://user:pass@example.com/'],['Fragment','https://example.com/#a'],['Unicode host','https://bücher.example'],['Zone ID','http://[fe80::1%25eth0]/'],['Unsupported scheme','file:///etc/passwd'],['Backslash','https://example.com\\@127.0.0.1/'],['Bad port','https://example.com:65536/'],['Repeated FQDN dot','https://example.com../'],['Empty port','https://example.com:/'],['Whitespace',' https://example.com/'],['Malformed IPv6','http://[1::2::3]/'] ])add(name,{target},false,'TARGET_INVALID','Normalization');
add('Canonical hostname and FQDN dot',{target:'HTTPS://API.EXAMPLE.COM.:443/',addresses:'8.8.8.8'},true,'PUBLIC_ALLOW','Normalization');
add('Invalid deny fails closed',{target:'8.8.8.8',deniedCidrs:'8.8.8.8/33'},false,'CONFIG_INVALID');
add('CIDR host bits rejected',{...erp,allowedCidrs:'10.23.4.12/24'},false,'CONFIG_INVALID');
add('Noncanonical CIDR prefix rejected',{...erp,allowedCidrs:'10.23.4.0/024'},false,'CONFIG_INVALID');
add('Noncanonical IPv6 CIDR rejected',{...erp,allowedCidrs:'FD12:0000::/64'},false,'CONFIG_INVALID');
add('Unused loopback CIDR invalidates profile',{target:'8.8.8.8',bound:true,endpointOrigin:'https://8.8.8.8',allowedCidrs:'8.8.8.8/32\n127.0.0.1/32'},false,'CONFIG_INVALID');
add('Mapped endpoint CIDR rejected',{...erp,allowedCidrs:'::ffff:10.23.4.12/128'},false,'CONFIG_INVALID');
add('CONNECT forbidden',{target:'8.8.8.8',method:'CONNECT'},false,'METHOD_FORBIDDEN');
add('Unallocated gap inside 2000::/3',{target:'2001:1000::1'},false,'HARD_DENY','Addresses');
for(const c of ADDRESS_CASES) {
 const allowed=c.expected.public_decision==='allow';
 const code=allowed?'PUBLIC_ALLOW':c.expected.endpoint_rule==='forbidden'?'HARD_DENY':'PUBLIC_PROFILE_DENY';
 CASES.push({name:c.id,group:'Repository',input:scenario({target:c.input.address}),allowed,code});
}
export function validateCases(evaluate) { return CASES.map(c=>{const actual=evaluate(c.input);return {...c,actual,passed:actual.allowed===c.allowed&&actual.code===c.code};}); }
