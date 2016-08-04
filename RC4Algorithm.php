<?php

function rc4($key, &$str) {{{

	$s = array();
	for ($i = 0; $i < 256; $i++) {
		$s[$i] = $i;
	}

	$j = 0;
	for ($i = 0; $i < 256; $i++) {
		$j			= ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
		$temp		= $s[$i];
		$s[$i]	= $s[$j];
		$s[$j]	= $temp;
	}

	$i = 0;
	$j = 0;
	$res = '';
	for ($k = 0; $k < strlen($str); $k++) {
		$i				= ($i + 1) % 256;
		$j				= ($j + $s[$i]) % 256;
		$temp			= $s[$i];
		$s[$i]		= $s[$j];
		$s[$j]		= $temp;
		$str[$k]	= $str[$k] ^ chr($s[($s[$i] + $s[$j]) % 256]);
	}

}}}

$key	= 'SecureKey';
$data	= 'Data to be encrypted';

$ret	= mcrypt_encrypt ( MCRYPT_ARCFOUR , $key , $data , 'stream');
rc4($key,$data);

$b64	= base64_encode($ret);
echo $b64;
echo PHP_EOL;
$b64	= base64_encode($data);
echo $b64;
echo PHP_EOL;

$ret	= base64_decode($b64);
$data	= mcrypt_decrypt ( MCRYPT_ARCFOUR , $key , $ret , 'stream');
echo $data;
echo PHP_EOL;

?>
