<?php
$privPath = __DIR__.'/storage/oauth-private.key';
$pubPath = __DIR__.'/storage/oauth-public.key';
$p = openssl_pkey_get_private('file://'.$privPath);
if(!$p){echo "PRIVATE_LOAD_FAILED\n"; exit(1);} 
$d = openssl_pkey_get_details($p);
$pub = openssl_pkey_get_public('file://'.$pubPath);
if(!$pub){echo "PUBLIC_LOAD_FAILED\n"; exit(2);} 
$dp = openssl_pkey_get_details($pub);
echo "PRIVATE_TYPE:".($d['type'] ?? '').PHP_EOL;
echo "PUBLIC_TYPE:".($dp['type'] ?? '').PHP_EOL;
if(isset($d['rsa']['n'])){ echo "PRIV_RSA_SHA1:".sha1($d['rsa']['n']).PHP_EOL; } else echo "PRIV_NO_RSA\n";
if(isset($dp['rsa']['n'])){ echo "PUB_RSA_SHA1:".sha1($dp['rsa']['n']).PHP_EOL; } else echo "PUB_NO_RSA\n";
if(isset($d['rsa']['n']) && isset($dp['rsa']['n']) && $d['rsa']['n'] === $dp['rsa']['n']) echo "MATCH\n"; else echo "MISMATCH\n";
