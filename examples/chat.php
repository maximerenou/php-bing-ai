<?php

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set("Europe/Paris");

$cookie = "COOKIE_HERE";
$id = null;

$ai = new \MaximeRenou\BingAI\BingAI();

$conversation = $ai->createChatConversation($cookie, $id)
    ->withPreferences('fr-FR', 'fr-FR', 'FR');

echo 'Type "q" to quit' . PHP_EOL;

while (true) {
    echo PHP_EOL . "> ";
    $text = rtrim(fgets(STDIN));

    if ($text == 'q')
        break;

    echo PHP_EOL;

    $message = new \MaximeRenou\BingAI\Chat\Message($text);

    list($text, $cards) = $conversation->send($message, function ($text, $cards) {
        echo "- $text \r";
    });

    echo "- $text" . PHP_EOL;
}

exit(0);