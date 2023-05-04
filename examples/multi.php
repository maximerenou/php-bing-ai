<?php
require __DIR__ . '/../vendor/autoload.php';

$cookie = "YOUR_COOKIE_HERE"; //@TODO change

if ($cookie == 'YOUR_COOKIE_HERE') {
    echo 'Please add your _U cookie to multi.php (line 4)' . PHP_EOL;
    exit(1);
}

\MaximeRenou\BingAI\Tools::$debug = false; // Set true for verbose

$ai = new \MaximeRenou\BingAI\BingAI($cookie);

$conversation = $ai->createChatConversation()
    ->withTone(\MaximeRenou\BingAI\Chat\Tone::Creative);

echo 'Type "q" to quit' . PHP_EOL;

while (true) {
    echo PHP_EOL . "> ";
    $text = rtrim(fgets(STDIN));

    if ($text == 'q')
        break;

    $prompt = new \MaximeRenou\BingAI\Chat\Prompt($text);
    $padding = 0;

    list($text, $cards) = $conversation->ask($prompt, function ($text, $cards) use (&$padding) {
        // Erase last line
        echo str_repeat(chr(8), $padding);

        $text = trim($text);

        // Print partial answer
        echo "- $text";
        $padding = mb_strlen($text) + 2;
    });

    // Erase last line
    echo str_repeat(chr(8), $padding);

    // Print final answer
    echo "- $text" . PHP_EOL;

    // Generative cards
    foreach ($cards as $card) {
        if ($card->type == \MaximeRenou\BingAI\Chat\MessageType::GenerateQuery && $card->data['contentType'] == 'IMAGE') {
            $loader = "Generating: {$card->text}...";
            echo $loader;

            // Create the image
            $creator = $ai->createImages($card->text);
            $creator->wait();

            echo str_repeat(chr(8), strlen($loader));

            if ($creator->hasFailed()) {
                echo "[Image generation failed]" . PHP_EOL;
            }
            else {
                foreach ($creator->getImages() as $image) {
                    echo "* $image" . PHP_EOL;
                }

                $remaining = $creator->getRemainingBoosts();
                echo "[$remaining remaining boosts]" . PHP_EOL;
            }
        }
    }

    if ($conversation->ended()) {
        echo "[Conversation ended]" . PHP_EOL;
        break;
    }
}

exit(0);
