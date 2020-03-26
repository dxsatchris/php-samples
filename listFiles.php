<?php
$dir = "../my_directory";
$dh  = opendir($dir);
while (false !== ($filename = readdir($dh))) {
    $files[] = $filename;
}
$files = array_diff($files, array('.', '..'));
sort($files);

foreach( $files as $k => $v) {
	print($v);
	print('<br>');
}
/* print_r($files);

rsort($files);

print_r($files); */