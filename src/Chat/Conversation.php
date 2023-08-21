<?php

namespace MaximeRenou\BingAI\Chat;

use MaximeRenou\BingAI\Tools;

class Conversation
{
    const END_CHAR = '';

    protected $cookie;

    // Conversation IDs
    public $id;
    public $client_id;
    public $signature;

    // User preferences
    public $tone = Tone::Balanced;
    public $locale = 'en-US';
    public $market = 'en-US';
    public $region = 'US';
    public $geolocation = null;

    // Conversation data
    protected $invocations = -1;
    protected $current_started;
    protected $current_text;
    protected $current_messages;
    protected $user_messages_count;
    protected $max_messages_count;
    protected $kicked = false;

    public function __construct($cookie, $identifiers = null, $invocations = 0)
    {
        $this->cookie = $cookie;

        if (! is_array($identifiers))
            $identifiers = $this->createIdentifiers();

        $this->id = $identifiers['conversationId'];
        $this->client_id = $identifiers['clientId'];
        $this->signature = $identifiers['conversationSignature'];
        $this->invocations = $invocations - 1;
    }

    public function getIdentifiers()
    {
        return [
            'conversationId' => $this->id,
            'clientId' => $this->client_id,
            'conversationSignature' => $this->signature
        ];
    }

    public function withTone($tone)
    {
        $this->tone = $tone;
        return $this;
    }

    public function withPreferences($locale = 'en-US', $market = 'en-US', $region = 'US')
    {
        $this->locale = $locale;
        $this->market = $market;
        $this->region = $region;
        return $this;
    }

    public function withLocation($latitude, $longitude, $radius = 1000)
    {
        $this->geolocation = [$latitude, $longitude, $radius];
        return $this;
    }

    public function getRemainingMessages()
    {
        if (is_null($this->max_messages_count))
            return 1;

        return $this->max_messages_count - $this->user_messages_count;
    }

    public function kicked()
    {
        return $this->kicked;
    }

    public function ended()
    {
        return $this->kicked || $this->getRemainingMessages() <= 0;
    }

    public function createIdentifiers()
    {
        $data = Tools::request("https://www.bing.com/turing/conversation/create", [
            'cookie: _U=' . $this->cookie,
            'method: GET',
            'accept: application/json',
            "accept-language: {$this->region},{$this->locale};q=0.9",
            'content-type: application/json',
            'x-ms-client-request-id' => Tools::generateUUID(),
        ]);

        $data = json_decode($data, true);

        if (! is_array($data) || ! isset($data['result']) || ! isset($data['result']['value']) || $data['result']['value'] != 'Success')
            throw new \Exception("Failed to init conversation");

        return $data;
    }

    public function uploadImage($image_data)
    {
        $image_encoded = base64_encode($image_data);

        $form = [
            'knowledgeRequest' => json_encode([
                "imageInfo" => (object) [],
                "knowledgeRequest" => [
                    "invokedSkills" => ["ImageById"],
                    "subscriptionId" => "Bing.Chat.Multimodal",
                    "invokedSkillsRequestData" => [
                        "enableFaceBlur" => true
                    ],
                    "convoData" => [
                        "convoid" => '', // $this->id ?
                        "convotone" => 'Precise' // $this->tone ?
                    ]
                ]
            ]),
            'imageBase64' => $image_encoded
        ];

        Tools::debug("Image upload request: " . print_r($form, true));

        $data = Tools::request("https://www.bing.com/images/kblob", [
            'cookie: _U=' . $this->cookie,
            'method: POST',
            'Content-Type: multipart/form-data',
            'referer: https://www.bing.com',
        ], $form);

        Tools::debug("Image upload response: $data");

        $data = json_decode($data, true);

        if (! is_array($data) || empty($data['blobId']) || empty($data['processedBlobId']))
            throw new \Exception("Failed to upload image");

        return [
            'originalImageUrl' => 'https://www.bing.com/images/blob?bcid=' . $data['blobId'],
            'imageUrl' => 'https://www.bing.com/images/blob?bcid=' . $data['processedBlobId'],
        ];
    }

    public function ask(Prompt $message, $callback = null)
    {
        $this->invocations++;
        $this->current_started = false;
        $this->current_text = '';

        $this->current_messages = [
            Message::fromPrompt($message)
        ];

        $headers = [
            'accept-language' => $this->locale . ',' . $this->region . ';q=0.9',
            'cache-control' => 'no-cache',
            'pragma' => 'no-cache'
        ];

        Tools::debug("Creating loop");

        \React\EventLoop\Loop::set(\React\EventLoop\Factory::create());

        $loop = \React\EventLoop\Loop::get();

        \Ratchet\Client\connect('wss://sydney.bing.com/sydney/ChatHub', [], $headers, $loop)->then(function ($connection) use ($message, $callback) {
            Tools::debug("Connection open");

            $connection->on('message', function ($raw) use ($connection, $message, $callback) {
                Tools::debug("Packet received");
                Tools::debug($raw);
                $this->handlePacket($raw, $connection, $message, $callback);
            });

            $connection->on('close', function () {
                Tools::debug("Connection closed");
            });

            Tools::debug("Sending first packet");
            $connection->send(json_encode(['protocol' => 'json', 'version' => 1]) . self::END_CHAR);
        }, function ($error) {
            throw new \Exception($error->getMessage());
        });

        $loop->run();

        return [$this->current_text, $this->current_messages];
    }

    public function handlePacket($raw, $connection, $message, $callback)
    {
        $objects = explode(self::END_CHAR, $raw);

        $objects = array_map(function ($object) {
            return json_decode($object, true);
        }, $objects);

        $objects = array_filter($objects, function ($value) {
            return is_array($value);
        });

        if (count($objects) === 0) {
            return;
        }

        if (! $this->current_started) {
            Tools::debug("Sending start ping");
            $connection->send(json_encode(['type' => 6]) . self::END_CHAR);

            $trace_id = bin2hex(random_bytes(16));
            $location = null;

            if (is_array($this->geolocation)) {
                $location = "lat:{$this->geolocation[0]};long={$this->geolocation[1]};re={$this->geolocation[2]}m;";
                // Example format: "lat:47.639557;long:-122.128159;re=1000m;";
            }

            $locationHints = [
                // Example hint
                /*[
                    "country" => "France",
                    "timezoneoffset" => 1,
                    "countryConfidence" => 9,
                    "Center" => [
                        "Latitude" => 48,
                        "Longitude" => 1
                    ],
                    "RegionType" => 2,
                    "SourceType" => 1
                ]*/
            ];

            $options = [
                'nlu_direct_response_filter',
                'deepleo',
                'enable_debug_commands',
                'disable_emoji_spoken_text',
                'responsible_ai_policy_235',
                'enablemm',

                // V2 options
                'enbfpr',
                'jb095',
                'jbfv1',
                'nojbfedge',
                'weasgv2',
                'dv3sugg',
                'inputlanguage',
                'rediscluster',

                // V3 options
                "iyxapbing",
                "iycapbing",
                "enpcktrk",
                "logosv1",
                "iyolojb",
            ];


            if ($this->tone === Tone::Creative) {
                $options = array_merge($options, [
                    'h3imaginative',
                    'clgalileo',
                    'gencontentv3',
                    'gencontentv5'
                ]);
            }
            elseif ($this->tone === Tone::Precise) {
                $options = array_merge($options, [
                    'h3precise',
                    'clgalileo',
                    'gencontentv5',

                    // V3 options
                    'gencontentv3',
                ]);
            }
            else {
                $options = array_merge($options, [
                    'galileo',
                    'visualcreative',

                    // V3 options
                    'harmonyv3',
                    'saharagenconv5',
                ]);
            }

            if (! $message->cache) {
                $options[] = "nocache";
            }

            $message_data = [
                'locale' => $message->locale ?? $this->locale,
                'market' => $message->market ?? $this->market,
                'region' => $message->region ?? $this->region,
                'location' => $location,
                'locationHints' => $locationHints,
                'timestamp' => date('Y-m-d') . 'T' . date('H:i:sP'),
                'author' => 'user',
                'inputMethod' => 'Keyboard',
                'text' => $message->text,
                'messageType' => 'Chat',
            ];

            if (! empty($message->image)) {
                $message_data = array_merge($message_data, $this->uploadImage($message->image));
                $message->image = null;
            }

            $params = [
                'arguments' => [
                    [
                        'source' => 'cib',
                        'verbosity' => 'verbose',
                        'scenario' => 'SERP',
                        'optionsSets' => $options,
                        'allowedMessageTypes' => [
                            'Chat',
                            'InternalSearchQuery',
                            'InternalSearchResult',
                            'InternalLoaderMessage',
                            'RenderCardRequest',
                            'AdsQuery',
                            'SemanticSerp',
                            'Disengaged',
                            'ActionRequest',
                            'GenerateContentQuery',
                            'SearchQuery',

                            // V3
                            'Context',
                            'Progress',
                        ],
                        'sliceIds' => [],
                        'traceId' => $trace_id,
                        'isStartOfSession' => $this->invocations == 0,
                        'message' => $message_data,
                        'conversationSignature' => $this->signature,
                        'participant' => ['id' => $this->client_id],
                        'conversationId' => $this->id,
                        'spokenTextMode' => 'None',
                    ]
                ],
                'invocationId' => "{$this->invocations}",
                'target' => 'chat',
                'type' => 4
            ];

            $connection->send(json_encode($params) . self::END_CHAR);

            $this->current_started = true;

            return;
        }

        foreach ($objects as $object) {
            if ($this->handleObject($object, $callback))
                $connection->close();
        }
    }

    public function handleObject($object, $callback = null)
    {
        $terminate = false;

        switch ($object['type'])
        {
            case 1: // Partial result
                $messages = [];

                foreach ($object['arguments'] as $argument) {
                    if (isset($argument['messages']) && is_array($argument['messages'])) {
                        foreach ($argument['messages'] as $messageData) {
                            $messages[] = Message::fromData($messageData);
                        }
                    }

                    if (isset($argument['throttling']) && is_array($argument['throttling'])) {
                        if (isset($argument['throttling']['maxNumUserMessagesInConversation'])) {
                            $this->max_messages_count = $argument['throttling']['maxNumUserMessagesInConversation'];
                        }

                        if (isset($argument['throttling']['numUserMessagesInConversation'])) {
                            $this->user_messages_count = $argument['throttling']['numUserMessagesInConversation'];
                        }
                    }
                }

                foreach ($this->current_messages as $previous_message) {
                    $older = true;

                    foreach ($messages as $i => $message) {
                        if ($message->id == $previous_message->id) {
                            $older = false;
                            break;
                        }
                    }

                    if ($older) {
                        array_unshift($messages, $previous_message);
                    }
                }

                $this->current_messages = $messages;
                break;
            case 2: // Global result
                $this->current_messages = [];

                if (isset($object['item']['messages']) && is_array($object['item']['messages'])) {
                    foreach ($object['item']['messages'] as $messageData) {
                        $this->current_messages[] = Message::fromData($messageData);
                    }
                }

                if (isset($object['item']['throttling']) && is_array($object['item']['throttling'])) {
                    if (isset($object['item']['throttling']['maxNumUserMessagesInConversation'])) {
                        $this->max_messages_count = $object['item']['throttling']['maxNumUserMessagesInConversation'];
                    }

                    if (isset($object['item']['throttling']['numUserMessagesInConversation'])) {
                        $this->user_messages_count = $object['item']['throttling']['numUserMessagesInConversation'];
                    }
                }
                break;
            case 3: // Answer ended
                // Available: $object['invocationId'];
                $terminate = true;
                break;
            case 6:
                // Ping
                return $terminate;
        }

        $text_parts = [];

        foreach ($this->current_messages as $message) {
            if ($message->type == MessageType::Answer)
                $text_parts[] = $message->toText();
            elseif ($message->type == MessageType::Disengaged)
                $this->kicked = true;
        }

        $this->current_text = trim(implode('. ', array_filter($text_parts)));

        // Callback

        if (! is_null($callback)) {
            call_user_func_array($callback, [$this->current_text, $this->current_messages]);
        }

        return $terminate;
    }
}
