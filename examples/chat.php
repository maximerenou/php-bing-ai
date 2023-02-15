<?php

$cookie = "YOUR_COOKIE_HERE"; //@TODO change

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set("Europe/Paris");

$ai = new \MaximeRenou\BingAI\BingAI();

$conversation = $ai->createChatConversation($cookie)
    ->withPreferences('fr-FR', 'fr-FR', 'FR');

\MaximeRenou\BingAI\Tools::$debug = false; // Set true for 
verbose

echo 'Type "q" to quit' . PHP_EOL;

while (true) {
    echo PHP_EOL . "> ";
    $text = rtrim(fgets(STDIN));

    if ($text == 'q')
        break;

    echo PHP_EOL;

    $prompt = new \MaximeRenou\BingAI\Chat\Prompt($text);

    list($text, $cards) = $conversation->ask($prompt, function ($text, $cards) {
        echo "- $text \r";
    });

    echo "- $text" . PHP_EOL;
}

exit(0);
