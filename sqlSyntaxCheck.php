<?php

$sql	= "INSERT INTO ( id , value , id ) VALUES ( '{datetime:?1}' , '{string:?2}' , '{ float:?3 }' )";

$sql	= config_parse($sql,array('2010-01-10 00:59:49' , 'value' , '1'));

echo "SQL1 $sql".PHP_EOL;
$sql = preg_replace_callback('~{( ?[a-z]+):([a-z0-9A-Z: _-]+ ?)}~','sql_match',$sql);

echo "SQL2 $sql".PHP_EOL;

function sql_match($matches) { 

	var_dump($matches);

	$type	 = trim($matches[1]);
	$check = trim($matches[2]);
	switch(trim($type)) {
		case 'int':
			if(!is_numeric($check) || (string) $check !== (string) (int)$check) {
				return FALSE;
			}
			return (string)(int)$check;
			break;
		case 'float':
			if (!is_numeric($check)) { 
				return FALSE;
			}
			return (string) (float) $check;
			break;
		case 'string':
			return $check;
			break;
		case 'date':
			if (preg_match('~^([0-9]{4})-([0-1]{2}?)-([0-3]{2}?)$~', $check, $date_matches) === 1) { 
				return sprintf('%04d-%02d-%02d', $date_matches[1], $date_matches[2], $date_matches[3]);
			} 
			return FALSE;
			break;
		case 'datetime':
			if (preg_match('~^([0-9]{4})-([0-1]{2}?)-([0-3]{2}?) ?([0-9]{2}):([0-9]{2}):([0-9]{2})$~', $check, $date_matches) === 1) { 
				return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $date_matches[1], $date_matches[2], $date_matches[3], $date_matches[4], $date_matches[5], $date_matches[6]);
			} 
			return FALSE;
			break;

		default:	
			return FALSE;
		break;
	}

	return FALSE;
}

function config_parse ( $sql , $replacements ) {{{

	if($sql === FALSE) {
		return FALSE;
	}
	$pattern = '/\?[0-9]{0,3}/';
	preg_match_all($pattern,$sql,$matches);
	if(count($replacements) != count($matches[0])) {
		return FALSE;
	}

	for($i=0;$i<count($matches[0]);$i++) {
		if ($i < 9) {
			$values[$i] = '/\\'.$matches[0][$i].'(?![0-9])/'; // Rewrite what we need to replace a little
		} else {
			$values[$i] = '/\\'.$matches[0][$i].'/'; // Rewrite what we need to replace a little
		}
	}

	$ret = preg_replace($values,$replacements,$sql);

	return $ret;

}}}

?>
