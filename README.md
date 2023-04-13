![Bing + PHP](logo.png)

# Bing AI client

This is an unofficial Composer package for using **Bing AI**, including **Chat (GPT-4)** and **Image Creator (DALL-E)**.

_It comes with no warranty of continuous stability._

## Installation

    composer require maximerenou/bing-ai

## Usage

- [Chat AI](#chat-ai)
- [Image Creator AI](#image-creator)

First, you need to sign in on bing.com and get your `_U` cookie.

---------------------------------------

### Chat AI

Edit and run `examples/chat.php` to test it.

```php
use MaximeRenou\BingAI\BingAI;
use MaximeRenou\BingAI\Chat\Prompt;

$ai = new BingAI;
// $cookie - your "_U" cookie from bing.com
$conversation = $ai->createChatConversation($cookie)
    ->withLocation($latitude, $longitude, $radius) // Optional
    ->withPreferences('fr-FR', 'fr-FR', 'FR'); // Optional

// Example 1: sync
// $text - Text-only version of Bing's answer
// $messages - Message objects array
list($text, $messages) = $conversation->ask(new Prompt("Hello World"));

// Example 2: async
// $text - Incomplete text version
// $messages - Incomplete messages fleet
list($final_text, $final_messages) = $conversation->ask($prompt, function ($text, $messages) {
    echo $text;
});

```

Every "card" from Bing AI is fetched. Check `Message.php` to learn more about its format.

If you want to resume a previous conversation, you can retrieve its identifiers:
```php
// Get current identifiers
$identifiers = $conversation->getIdentifiers();

// ...
// Resume conversation with $identifiers parameter, and number of previous questions
$conversation = $ai->createChatConversation($cookie, $identifiers, 1);
```

#### Throttling

Bing is limiting messages count per conversations. You can monitor it by calling `getRemainingMessages()` after every interaction.

```php
$remaining = $conversation->getRemainingMessages();

if ($remaining === 0) {
    // You reached the limit
}
```

#### Text generation

Note: to prevent answers like "I have already written \[...]", you can disable cache for your prompt with `withoutCache()`.

```php
$subject = "Internet memes";
$tone = 'funny';
$type = 'blog post';
$length = 'short';

$prompt = new Prompt("Please write a *$length* *$type* in a *$tone* style about `$subject`. Please wrap the $type in a markdown codeblock.");

$conversation->ask($prompt->withoutCache(), ...)
```

---------------------------------------

### Image Creator

Edit and run `examples/images.php` to test it.

> Image generation becomes slower after using it a few times. Bing limits the number of images generated fast per user.

```php
use MaximeRenou\BingAI\BingAI;

$ai = new BingAI;
// $cookie - your "_U" cookie from bing.com
$creator = $ai->createImages($cookie, "A 3D teddy bear");

// Example 1: automatically wait while generating
$creator->wait();

// Example 2: manually wait while generating
do {
    sleep(1);
} while ($creator->isGenerating());

// Finally, get images URLs
if (! $creator->hasFailed()) {
    $images = $creator->getImages();
}
```

You may quit after calling `createImages()` and check generation later using its ID:

```php
$prompt = "A 3D teddy bear";
$creator = $ai->createImages($cookie, $prompt);
$generation_id = $creator->getGenerationId();

// ...

$creator = new ImageCreator($cookie);
$creator->resume($generation_id, $prompt);
```

---------------------------------------

#### Disclaimer

Using Bing AI outside bing.com may violate Bing terms. Use it at your own risk.
Bing is a trademark of Microsoft.
