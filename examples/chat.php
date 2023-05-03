<?php

$cookie = "YOUR_COOKIE_HERE"; //@TODO change

if ($cookie == 'YOUR_COOKIE_HERE') {
    echo 'Please add your _U cookie to chat.php (line 3)' . PHP_EOL;
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set("Europe/Paris");

$ai = new \MaximeRenou\BingAI\BingAI($cookie);

$conversation = $ai->createChatConversation();

\MaximeRenou\BingAI\Tools::$debug = false; // Set true for verbose

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
        $padding = strlen($text) + 2;
    });

    // Erase the last line
    for ($i = 0; $i < $padding; $i++)
        echo chr(8);

    // Print final answer
    echo "- $text" . PHP_EOL;

    if ($conversation->kicked()) {
        echo "[Conversation ended]" . PHP_EOL;
        break;
    }

    $remaining = $conversation->getRemainingMessages();

    if ($remaining != 0) {
        echo "[$remaining remaining messages]" . PHP_EOL;
    } else {
        echo "[Limit reached]" . PHP_EOL;
        break;
    }
}

exit(0);
