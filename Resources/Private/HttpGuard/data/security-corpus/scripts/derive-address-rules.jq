def textvalue:
  if type == "object" then .["+content"] // ""
  elif . == null then ""
  else . end;
def family($cidr): if ($cidr|contains(":")) then 6 else 4 end;
def classification($cidr; $name):
  if ["10.0.0.0/8","172.16.0.0/12","192.168.0.0/16"]|index($cidr) then "private_ipv4"
  elif $cidr == "100.64.0.0/10" then "shared_cgnat"
  elif $cidr == "fc00::/7" then "unique_local_ipv6"
  elif ["127.0.0.0/8","::1/128"]|index($cidr) then "loopback"
  elif $cidr == "::ffff:0:0/96" then "ipv4_mapped_normalize"
  elif ["64:ff9b::/96","64:ff9b:1::/48","2002::/16","2001::/32"]|index($cidr) then "transition_or_translation"
  elif $name|test("Documentation|TEST-NET"; "i") then "documentation"
  elif $name|test("Benchmark"; "i") then "benchmark"
  elif $name|test("Link.Local"; "i") then "link_local"
  elif $name|test("Unspecified|This host|This network"; "i") then "unspecified_or_this_network"
  elif $name|test("Discard"; "i") then "discard"
  elif $name|test("Reserved|Broadcast"; "i") then "reserved_or_broadcast"
  else "special_purpose" end;
def endpoint_rule($class):
  if ["private_ipv4","shared_cgnat","unique_local_ipv6"]|index($class) then "requires_bound_endpoint_and_narrow_cidr"
  elif $class == "loopback" then "requires_bound_endpoint_allowLoopback_and_host_prefix"
  elif $class == "ipv4_mapped_normalize" then "normalize_embedded_ipv4_before_all_rules"
  else "forbidden" end;
def specialrules($source; $sourceid):
  [$source.registry.registry.record[]
  | . as $record
  | ($record.address|textvalue|split(",")[]|gsub("^\\s+|\\s+$"; "")) as $cidr
  | classification($cidr; $record.name) as $class
  | {cidr:$cidr,family:family($cidr),name:$record.name,class:$class,public:"deny",endpoint:endpoint_rule($class),source_id:$sourceid,iana_global_reachable:($record.global|textvalue),source_rfc:([$record.spec|..|objects|select(.["+@type"]? == "rfc")|.["+@data"]]|unique)}];
{
  schema_version:1,
  revision:"2026-10-08-iana-2025-10-v1",
  purpose:"Versioned normative address test data; not production implementation or evidence of passed tests",
  classification_order:["strict binary parse","normalize IPv4-mapped IPv6 to embedded IPv4","operator hard deny and provider hard deny","longest matching address-class prefix","public ordinary allocation or explicitly bound narrow endpoint"],
  public_ipv6_rule:"Must be inside an IANA ALLOCATED regular global-unicast prefix and outside every excluded special range; 2000::/3 alone does not authorize unallocated gaps",
  endpoint_constraints:{min_ipv4_prefix:24,min_ipv6_prefix:64,loopback_ipv4_prefix:32,loopback_ipv6_prefix:128,loopback_requires_flag:true,requires_exact_origin:true,requires_methods:true,requires_registry_issued_client_binding:true,all_resolved_addresses_must_match:true,hard_denies_override_every_endpoint:true},
  iana_special_rules:(specialrules($ipv4[0];"iana-ipv4-special")+specialrules($ipv6[0];"iana-ipv6-special")),
  supplemental_rules:[
    {cidr:"224.0.0.0/4",family:4,name:"IPv4 multicast",class:"multicast",public:"deny",endpoint:"forbidden",source_id:"rfc1112"},
    {cidr:"ff00::/8",family:6,name:"IPv6 multicast",class:"multicast",public:"deny",endpoint:"forbidden",source_id:"rfc4291"},
    {cidr:"::/96",family:6,name:"Deprecated IPv4-compatible IPv6",class:"transition_or_translation",public:"deny",endpoint:"forbidden",classification_exceptions:["::/128","::1/128"],source_id:"rfc4291"}
  ],
  provider_hard_denies:[
    {cidr:"169.254.169.254/32",family:4,name:"AWS IMDS IPv4",class:"metadata_hard_deny",public:"deny",endpoint:"forbidden",source_id:"aws-imds"},
    {cidr:"fd00:ec2::254/128",family:6,name:"AWS IMDS IPv6",class:"metadata_hard_deny",public:"deny",endpoint:"forbidden",source_id:"aws-imds"},
    {cidr:"100.100.100.200/32",family:4,name:"Alibaba ECS metadata",class:"metadata_hard_deny",public:"deny",endpoint:"forbidden",source_id:"alibaba-imds"},
    {cidr:"168.63.129.16/32",family:4,name:"Azure platform WireServer",class:"platform_hard_deny",public:"deny",endpoint:"forbidden",source_id:"azure-platform"}
  ],
  ipv6_allocated_global_prefixes:[$unicast[0].registry.record[]|select(.status=="ALLOCATED")|{cidr:.prefix,designation:.description,allocation_date:.["+@date"],source_id:"iana-ipv6-unicast"}],
  ipv6_reserved_global_prefixes:[$unicast[0].registry.record[]|select(.status=="RESERVED")|{cidr:.prefix,designation:.description,source_id:"iana-ipv6-unicast"}]
}
