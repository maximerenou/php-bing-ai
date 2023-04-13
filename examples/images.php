<?php

$cookie = "YOUR_COOKIE_HERE"; //@TODO change

require __DIR__ . '/../vendor/autoload.php';

$ai = new \MaximeRenou\BingAI\BingAI();

\MaximeRenou\BingAI\Tools::$debug = false; // Set true for verbose

echo 'Type "q" to quit' . PHP_EOL;

echo PHP_EOL . "> ";
$text = rtrim(fgets(STDIN));

if ($text == 'q')
    exit(0);

$creator = $ai->createImages($cookie, $text);

echo 'Generating...' . PHP_EOL;

$creator->wait();

if (! $creator->hasFailed()) {
    $images = $creator->getImages();

    foreach ($images as $image) {
        echo "- $image" . PHP_EOL;
    }

    exit(0);
}
else {
    echo 'Generation failed' . PHP_EOL;
    exit(1);
}
