<?php
$file = __DIR__ . '/worker' . rand(1, 10);
$fh = fopen($file, 'a+');

$i = 0;
while (true) {
    sleep(1);

    fwrite($fh, rand(1, 10) . PHP_EOL);

    $i++;
}

fclose($fh);