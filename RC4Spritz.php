<?php
/*
Copyright (c) 2016, tino, http://tinoest.co.uk
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

function rc4Spritz($key, &$str) {{{

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

	$i = $j = $k = $z = 0;
	$w = 1;
	for ($y = 0; $y < strlen($str); $y++) {
		$i				= ($i + $w) % 256;
		$j				= ($k + $s[($j + $s[$i]) % 256]) % 256;
		$k				= ($i + $k + $s[$j]) % 256;
		$temp			= $s[$i];
		$s[$i]		= $s[$j];
		$s[$j]		= $temp;
		$z				= $s[($j + $s[($i + $s[($z + $k) % 256]) % 256]) % 256];
		$str[$y]	= $str[$y] ^ chr($z);
	}

}}}

$key	= 'SecureKey';
$data	= 'Data to be encrypted';

rc4Spritz($key,$data);

$b64	= base64_encode($data);
echo $b64;
echo PHP_EOL;

$ret	= base64_decode($b64);
rc4Spritz($key,$ret);
echo $ret;
echo PHP_EOL;

?>
