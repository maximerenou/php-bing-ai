<?php

$cookie = "YOUR_COOKIE_HERE"; //@TODO change

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set("Europe/Paris");

$ai = new \MaximeRenou\BingAI\BingAI();

$conversation = $ai->createChatConversation($cookie)
    ->withPreferences('fr-FR', 'fr-FR', 'FR');

\MaximeRenou\BingAI\Tools::$debug = false; // Set true for verbose

echo "Warning: Bing AI is currently limited to 5 questions per sessions." . PHP_EOL;
echo 'Type "q" to quit' . PHP_EOL;

while (true) {
    echo PHP_EOL . "> ";
    $text = rtrim(fgets(STDIN));

    if ($text == 'q')
        break;

    $prompt = new \MaximeRenou\BingAI\Chat\Prompt($text);
    $padding = 0;

    list($text, $cards) = $conversation->ask($prompt, function ($text, $cards) use (&$padding) {
        // Erase the last line
        for ($i = 0; $i < $padding; $i++)
            echo chr(8);

        // Print partial answer
        echo "- $text";
        $padding = mb_strlen($text) + 2;
    });

    // Erase the last line
    for ($i = 0; $i < $padding; $i++)
        echo chr(8);

    // Print final answer
    echo "- $text" . PHP_EOL;
}

exit(0);
