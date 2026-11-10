<?php

//borrowed from https://gist.github.com/jeremejazz/0475ee9e0c78702f5a68
$files = glob( __DIR__ . '/*/*.php');

foreach ($files as $file) {
    require($file);
}