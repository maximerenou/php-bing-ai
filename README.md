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

<details>
  <summary>How to get this cookie?</summary>

1. Navigate to bing.com
2. Sign in using your Microsoft account
3. Back on bing.com, right-click and select "Inspect" - the browser console appears
4. Go to "Application" tab
5. Select "Cookies" > "https://www.bing.com" in the sidebar
6. Search for "_U" cookie
7. Copy its content

</details> 

---------------------------------------

### Chat AI

Edit and run `examples/chat.php` to test it.

```php
use MaximeRenou\BingAI\BingAI;
use MaximeRenou\BingAI\Chat\Prompt;

// $cookie - your "_U" cookie from bing.com
$ai = new BingAI($cookie);

$conversation = $ai->createChatConversation();

// $text - Text-only version of Bing's answer
// $messages - Message objects array
list($text, $messages) = $conversation->ask(new Prompt("Hello World"));
```

_Every "card" from Bing AI is fetched. Check `Message.php` to learn more about its format._

<details>
  <summary>Real-time / progressive answer</summary>

You may pass a function as second argument to get real-time progression:

```php
// $text - Incomplete text version
// $messages - Incomplete messages fleet
list($final_text, $final_messages) = $conversation->ask($prompt, function ($text, $messages) {
    echo $text;
});
```

</details> 

<details>
  <summary>Locale and location preferences</summary>

```php
$conversation = $ai->createChatConversation()
    ->withLocation($latitude, $longitude, $radius) // Optional
    ->withPreferences('fr-FR', 'fr-FR', 'FR'); // Optional
```

</details> 

<details>
  <summary>Resume a conversation</summary>  

If you want to resume a previous conversation, you can retrieve its identifiers:

```php
// Get current identifiers
$identifiers = $conversation->getIdentifiers();

// ...
// Resume conversation with $identifiers parameter, and number of previous questions asked
$conversation = $ai->resumeChatConversation($identifiers, 1);
```

</details> 

<details>
  <summary>Text generation</summary>

```php
$subject = "Internet memes";
$tone = 'funny';
$type = 'blog post';
$length = 'short';

$prompt = new Prompt("Please write a *$length* *$type* in a *$tone* style about `$subject`. Please wrap the $type in a markdown codeblock.");

$conversation->ask($prompt->withoutCache(), ...)
```

> To prevent answers like "I have already written \[...]", you can disable cache for your prompt with `withoutCache()`.

</details>

<details>
  <summary>Throttling</summary>

Bing is limiting messages count per conversations. You can monitor it by calling `getRemainingMessages()` after every interaction.

```php
$remaining = $conversation->getRemainingMessages();

if ($remaining === 0) {
    // You reached the limit
}
```

</details>

---------------------------------------

### Image Creator

Edit and run `examples/images.php` to test it.

```php
use MaximeRenou\BingAI\BingAI;

// $cookie - your "_U" cookie from bing.com
$ai = new BingAI($cookie);

$creator = $ai->createImages("A 3D teddy bear");

$creator->wait();

// Finally, get images URLs
if (! $creator->hasFailed()) {
    $images = $creator->getImages();
}
```

> Image generation can become slower after consuming all of your "boosts". Check the section below to stay aware of your remaining boosts.

<details>
  <summary>Check remaining boosts</summary>

```php
$creator = $ai->getImageCreator();

$remaining_boosts = $creator->getRemainingBoosts();
```

</details>

<details>
  <summary>Asynchronous generation</summary>
You may quit after calling `createImages()` and check generation later using its ID:

```php
$prompt = "A 3D teddy bear";
$creator = $ai->createImages($prompt);
$generation_id = $creator->getGenerationId();

// ...

$creator = $ai->getImageCreator();
$creator->resume($generation_id, $prompt);
```

</details>

<details>
  <summary>Manually wait</summary>
Instead of calling `$creator->wait();` you can loop by yourself:

```php
do {
    sleep(1);
} while ($creator->isGenerating());
```

</details>

---------------------------------------

#### Disclaimer

Using Bing AI outside bing.com may violate Bing terms. Use it at your own risk.
Bing is a trademark of Microsoft.
