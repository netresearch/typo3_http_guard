#!/usr/bin/env python3
"""Six destructive mutations are applied only to disposable native copies."""
import argparse,pathlib,shutil,subprocess,json,hashlib
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--package',type=pathlib.Path,required=True)
parser.add_argument('--scratch',type=pathlib.Path,required=True)
parser.add_argument('--evidence',type=pathlib.Path,required=True)
parser.add_argument('--g7-vendor',type=pathlib.Path,required=True)
parser.add_argument('--g8-vendor',type=pathlib.Path,required=True)
parser.add_argument('--classic7-vendor',type=pathlib.Path,required=True)
parser.add_argument('--editor',required=True)
parser.add_argument('--image',required=True)
args=parser.parse_args()
package=args.package.resolve()
scratch=args.scratch.resolve();scratch.mkdir(parents=True,exist_ok=True)
evidence=args.evidence.resolve();evidence.mkdir(parents=True,exist_ok=True)
editor=args.editor
image=args.image
vendors={'7':args.g7_vendor.resolve(),'8':args.g8_vendor.resolve(),'7ter':args.classic7_vendor.resolve()}
source_hashes={str(p.relative_to(package)):hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted((package/'Classes/HttpGuard').rglob('*.php'))}
(evidence/'production-source-hashes.json').write_text(json.dumps(source_hashes,indent=2)+'\n')
cases=['address_check_removed','pin_removed','fallback_enabled','proxy_passthrough','stream_passthrough','grant_binding_removed']
summary=[]
def op(sel,operation,**kw):return dict(target={'select':sel},operation=operation,**kw)
for variant in ['7','8','7ter']:
 major=8 if variant=='8' else 7
 for case in cases:
  dest=scratch/f'g{variant}-{case}';dest.mkdir(parents=True,exist_ok=True)
  for relative in ['Classes/HttpGuard','Resources/Private/HttpGuard/data','Tests/HttpGuard']:
   shutil.copytree(package/relative,dest/relative,dirs_exist_ok=True)
  (dest/'Build').mkdir(exist_ok=True)
  shutil.copyfile(package/'Build/phpunit-http-guard.xml',dest/'Build/phpunit-http-guard.xml')
  shutil.copyfile(package/'composer.json',dest/'composer.json')
  changed={}
  def add(name,*ops):
   p=dest/'Classes/HttpGuard'/name;item=changed.setdefault(str(p),dict(path=str(p),sha256=hashlib.sha256(p.read_bytes()).hexdigest(),edits=[]));item['edits'].extend(ops)
  if case=='address_check_removed':
   add('PolicyEngine.php',op('method:PolicyEngine::validateAddresses','replace_node',parseAs='member',php="private function validateAddresses(array $addresses, ?EndpointProfile $profile, array &$metadata=['source'=>'none','class'=>'unknown']):array { return array_values(array_unique($addresses)); }"))
  elif case=='pin_removed':
   add('Transport/TransferLease.php',op('method:TransferLease::prepare','replace_statement',match="if($plan->target->literalIp===null){$raw[CURLOPT_RESOLVE]=[self::pin($plan)];}",php='{}'))
  elif case=='fallback_enabled':
   add('Transport/TransferLease.php',op('method:TransferLease::prepare','replace_expression',match='$this->outer->reject($reason)',php="$this->outer->resolve((new \\GuzzleHttp\\Client(['proxy'=>'','timeout'=>0.3]))->send($this->request))"))
  elif case=='proxy_passthrough':
   add('Transport/OptionSanitizer.php',op('method:OptionSanitizer::assertNoProxy','replace_node',parseAs='member',php='private static function assertNoProxy(array $options):void {}'),op('method:OptionSanitizer::sanitize','replace_expression',match="$leaf['proxy']=''",php="$leaf['proxy']=$options['proxy']??''"))
   add('Transport/TransferLease.php',op('method:TransferLease::prepare','replace_statement',match="$leaf['proxy']='';",php='{}'))
  elif case=='stream_passthrough':
   add('Transport/TerminalGuardMiddleware.php',op('method:TerminalGuardMiddleware::__invoke','replace_statement',match="$sanitizer=new OptionSanitizer($this->config->data['redirects']['max'],$this->config->data['tls']['requireVerification']);",php="{ if(($options['stream']??false)===true){return $next($request,$options);} $sanitizer=new OptionSanitizer($this->config->data['redirects']['max'],$this->config->data['tls']['requireVerification']); }"))
  else:
   add('PolicyEngine.php',op('method:PolicyEngine::buildPlan','replace_statement',match="if($profile!==null&&($profile->origin!==$target->origin||!in_array($method,$profile->methods,true))){throw new PolicyException('endpoint_mismatch');}",php='{}'))
  payload=evidence/f'g{variant}-{case}-ast.json';payload.write_text(json.dumps({'report':'agent','files':list(changed.values())}))
  result=subprocess.run([editor,'apply','--input',str(payload)],capture_output=True,text=True);(evidence/f'g{variant}-{case}-ast-result.json').write_text(result.stdout+result.stderr)
  if result.returncode:raise RuntimeError('AST mutation failed '+case+result.stdout+result.stderr)
  run_dir=evidence/f'g{variant}-{case}';run_dir.mkdir(exist_ok=True)
  cmd=['docker','run','--rm','--network','host','--add-host','guard.test:10.23.4.12','--mount',f'type=bind,src={dest},dst=/guard','--mount',f'type=bind,src={vendors[variant]},dst=/deps,readonly','--mount',f'type=bind,src={run_dir},dst=/witness','-e','HTTP_GUARD_TEST_AUTOLOAD=/deps/autoload.php','-e',f'HTTP_GUARD_MUTATION_CASE={case}','-e','HTTP_GUARD_WITNESS_DIRECTORY=/witness',image,'php','/deps/bin/phpunit','--configuration','/guard/Build/phpunit-http-guard.xml','--filter','MutationWitnessTest']
  run=subprocess.run(cmd,capture_output=True,text=True,timeout=40);(evidence/f'g{variant}-{case}.log').write_text(run.stdout+run.stderr)
  witness=json.loads((run_dir/f'{case}.json').read_text())
  wire_contact=any(witness['after'][label]['http'] > previous['http'] for label,previous in witness['before'].items())
  attempted=witness['transport']['nativeConstructed'] > 0 or witness['leaf_calls'] > 0
  row=dict(guzzle=major,tuple_variant=variant,mutation=case,exit_code=run.returncode,killed=run.returncode==1 and wire_contact and attempted,witness=witness)
  summary.append(row);(evidence/'summary.json').write_text(json.dumps(summary,indent=2))
  print(f'Guzzle {variant}: {case}: exit {run.returncode}, native {witness["transport"]["nativeConstructed"]}, leaf {witness["leaf_calls"]}, outcome {witness["outcome"]}',flush=True)
if not all(row['killed'] for row in summary):raise RuntimeError('A mutant survived')
