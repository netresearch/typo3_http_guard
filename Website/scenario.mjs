// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
export const INPUT_KEYS=['target','method','addresses','deniedCidrs','endpointOrigin','allowedCidrs','methods','coreHosts'];
export const METHODS=['GET','HEAD','POST','PUT','PATCH','DELETE','OPTIONS','CONNECT'];
export function parseScenario(text){
 let value;try{value=JSON.parse(text);}catch{throw new Error('Invalid scenario JSON.');}
 if(!value||typeof value!=='object'||Array.isArray(value)||value.kind!=='nr-http-guard-scenario'||value.version!==1||!value.input||typeof value.input!=='object'||Array.isArray(value.input))throw new Error('Choose an exported nr-http-guard scenario.');
 const safe={};
 for(const key of INPUT_KEYS){
  if(value.input[key]!==undefined&&typeof value.input[key]!=='string')throw new Error('Invalid scenario field: '+key);
  if(typeof value.input[key]==='string'&&value.input[key].length>20000)throw new Error('Scenario field too long: '+key);
  if(value.input[key]!==undefined)safe[key]=value.input[key];
 }
 for(const key of ['bound','allowLoopback']){
  if(value.input[key]!==undefined&&typeof value.input[key]!=='boolean')throw new Error('Invalid scenario flag: '+key);
  safe[key]=value.input[key]===true;
 }
 if(safe.method&&!METHODS.includes(safe.method))throw new Error('Unsupported HTTP method.');
 return safe;
}
