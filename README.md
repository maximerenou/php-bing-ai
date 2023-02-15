# Bing AI client

> For now you need to have access to Bing Chat open beta. Or grab the cookie from someone who has access.

This is an unofficial Composer package for using Bing AI technology.

It comes with no warranty of continuous stability.

## Install

    composer require maximerenou/bing-ai

## Chat AI Usage

Edit and run `examples/chat.php` to test it.

```php
use MaximeRenou\BingAI\BingAI;
use MaximeRenou\BingAI\Chat\Prompt;

// $cookie - your "_U" cookie from bing.com
$ai = new BingAI;
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

Using Bing AI API outside bing.com may violate Bing AI terms. Use it at your own risk.
Bing is a trademark of Microsoft.