<?php

$cookie = "YOUR_COOKIE_HERE"; //@TODO change

if ($cookie == 'YOUR_COOKIE_HERE') { 
    echo 'Please add your _U cookie to images.php (line 3)' . PHP_EOL;
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

$ai = new \MaximeRenou\BingAI\BingAI($cookie);

\MaximeRenou\BingAI\Tools::$debug = false; // Set true for verbose

$boosts = $ai->getImageCreator()->getRemainingBoosts();

echo "You have $boosts remaining boosts." . PHP_EOL;

echo 'Type "q" to quit' . PHP_EOL;

echo PHP_EOL . "> ";
$text = rtrim(fgets(STDIN));

if ($text == 'q')
    exit(0);

$creator = $ai->createImages($text);

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
