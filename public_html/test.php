<?php

$x = array();
$y = array();
$pow = 1;

for ($n = 1; $n < 100; $n++) {
	$pow *= 364 / 365;
	if ($n == 1) {
		$x[1] = 1;
	} else {
		$x[$n] = $x[$n - 1] * (366 - $n) / 365;
	}
	$y[$n] = 1 - $x[$n];
	echo "[$n] " . $x[$n] / $pow . PHP_EOL;
}