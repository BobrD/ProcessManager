<?php

$fh = fopen(__DIR__ . '/work', 'a+');
while (true) {
    fwrite($fh, time() . PHP_EOL);
    sleep(1);
}
fclose($fh);