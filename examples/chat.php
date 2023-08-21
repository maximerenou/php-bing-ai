<?php
require __DIR__ . '/../vendor/autoload.php';

$cookie = "YOUR_COOKIE_HERE"; //@TODO change

if ($cookie == 'YOUR_COOKIE_HERE') {
    echo 'Please add your _U cookie to chat.php (line 4)' . PHP_EOL;
    exit(1);
}

date_default_timezone_set("Europe/Paris");

\MaximeRenou\BingAI\Tools::$debug = false; // Set true for verbose

$ai = new \MaximeRenou\BingAI\BingAI($cookie);

$conversation = $ai->createChatConversation();

echo 'Type "q" to quit' . PHP_EOL;

while (true) {
    echo PHP_EOL . "> ";
    $text = rtrim(fgets(STDIN));

    if ($text == 'q')
        break;

    if (strpos($text, '$image') !== false) {
        $text = str_replace('$image', '', $text);
        $prompt = new \MaximeRenou\BingAI\Chat\Prompt($text);
        echo PHP_EOL . "Image path: ";
        $image = rtrim(fgets(STDIN));
        $prompt->withImage($image);
    } else {
        $prompt = new \MaximeRenou\BingAI\Chat\Prompt($text);
    }

    $padding = 0;

    list($text, $cards) = $conversation->ask($prompt, function ($text, $cards) use (&$padding) {
        // Erase last line
        echo str_repeat(chr(8), $padding);

        // Print partial answer
        echo "- $text";
        $padding = mb_strlen($text) + 2;
    });

    // Erase last line
    echo str_repeat(chr(8), $padding);

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
