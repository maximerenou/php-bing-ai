<?php

namespace MaximeRenou\BingAI\Chat;

class Conversation
{
    public $id;

    public $client_id;

    public $signature;

    public $locale = 'en-US';

    public $market = 'en-US';

    public $region = 'US';

    public $geolocation = null;

    protected $invocations = 0;

    protected $current_stage;

    protected $current_text;

    protected $current_cards;

    const END_CHAR = '';

    public function __construct($cookie, $identifiers = null)
    {
        if (! is_array($identifiers))
            $identifiers = $this->init($cookie);

        $this->id = $identifiers['conversationId'];
        $this->client_id = $identifiers['clientId'];
        $this->signature = $identifiers['conversationSignature'];
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

    public function getIdentifiers()
    {
        return [
            'conversationId' => $this->id,
            'clientId' => $this->client_id,
            'conversationSignature' => $this->signature
        ];
    }

    public function send(Message $message, $callback = null)
    {
        $this->invocations++;
        $this->current_stage = 0;
        $this->current_text = '';
        $this->current_cards = [];

        $headers = [
            'accept-language' => $this->locale . ',' . $this->region . ';q=0.9',
            'cache-control' => 'no-cache',
            'pragma' => 'no-cache'
        ];

        \React\EventLoop\Loop::set(\React\EventLoop\Factory::create());
        $loop = \React\EventLoop\Loop::get();

        \Ratchet\Client\connect('wss://sydney.bing.com/sydney/ChatHub', [], $headers, $loop)
        ->then(function ($connection) use ($message, $callback) {
            $connection->on('message', function ($raw) use ($connection, $message, $callback) {
                $objects = explode(self::END_CHAR, $raw);
                $objects = array_map(fn ($object) => json_decode($object, true), $objects);
                $objects = array_filter($objects, fn ($value) => is_array($value));

                if (count($objects) === 0) {
                    return;
                }

                if ($this->current_stage === 0) {
                    $connection->send(json_encode(['type' => 6]) . self::END_CHAR);
                    
                    $trace_id = bin2hex(random_bytes(16));
                    $location = "lat:47.639557;long:-122.128159;re=1000m;";

                    if (is_array($this->geolocation)) {
                        //$location = "lat:{$this->geolocation[0]};long={$this->geolocation[1]};re={$this->geolocation[2]}m;";
                    }

                    $locationHints = [
                        [
                            "country" => "France",
                            "timezoneoffset" => 1,
                            "countryConfidence" => 9,
                            "Center" => [
                                "Latitude" => 48,
                                "Longitude" => 1
                            ],
                            "RegionType" => 2,
                            "SourceType" => 1
                        ]
                    ];

                    $params = [
                        'arguments' => [
                            [
                              'source' => 'cib',
                              'optionsSets' => [
                                'nlu_direct_response_filter',
                                'deepleo',
                                'enable_debug_commands',
                                'disable_emoji_spoken_text',
                                'responsible_ai_policy_235',
                                'enablemm'
                              ],
                              'allowedMessageTypes' => [
                                'Chat',
                                'InternalSearchQuery',
                                'InternalSearchResult',
                                'InternalLoaderMessage',
                                'RenderCardRequest',
                                'AdsQuery',
                                'SemanticSerp'
                              ],
                              'sliceIds' => [],
                              'traceId' => $trace_id,
                              'isStartOfSession' => true || $this->invocations === 1,
                              'message' => [
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
                              ],
                              'conversationSignature' => $this->signature,
                              'participant' => ['id' => $this->client_id],
                              'conversationId' => $this->id
                            ]
                          ],
                          'invocationId' => "{$this->invocations}",
                          'target' => 'chat',
                          'type' => 4
                    ];

                    $connection->send(json_encode($params) . self::END_CHAR);

                    $this->current_stage++;
                    return;
                }
                
                foreach ($objects as $object) {
                    $close = $this->handleObject($object, $callback);

                    if ($close)
                        $connection->close();
                }
            });
        
            $connection->send(json_encode(['protocol' => 'json', 'version' => 1]) . self::END_CHAR);
        }, function ($error) {
            throw new \Exception($error->getMessage());
        });

        $loop->run();

        return [$this->current_text, $this->current_cards];
    }

    public function init($cookie)
    {
        $headers = [
            'accept: application/json',
            "accept-language: {$this->region},{$this->locale};q=0.9",
            'content-type: application/json',
            'sec-ch-ua: "Not_A Brand";v="99", "Microsoft Edge";v="109", "Chromium";v="109"',
            'sec-ch-ua-arch: "x86"',
            'sec-ch-ua-bitness: "64"',
            'sec-ch-ua-full-version: "109.0.1518.78"',
            'sec-ch-ua-full-version-list: "Not_A Brand";v="99.0.0.0", "Microsoft Edge";v="109.0.1518.78", "Chromium";v="109.0.5414.120"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-model: ',
            'sec-ch-ua-platform: "macOS"',
            'sec-ch-ua-platform-version: "12.6.0"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'x-edge-shopping-flag: 1',
            'x-ms-client-request-id' => $this->generateUUID(),
            'x-ms-useragent: azsdk-js-api-client-factory/1.0.0-beta.1 core-rest-pipeline/1.10.0 OS/MacIntel',
            'cookie: _U=' . $cookie,
            'referer: https://www.bing.com/search',
            'referrer' => 'https://www.bing.com/search',
            'referrerPolicy: origin-when-cross-origin',
            'method: GET',
            'mode: cors',
            'credentials: include'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.bing.com/turing/conversation/create");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);   
        curl_close ($ch);
        
        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['result']) || ! isset($data['result']['value']) || $data['result']['value'] != 'Success')
            throw new \Exception("Failed to init conversation");

        return $data;
    }

    public function handleObject($object, $callback = null)
    {
        $close = false;

        if ($object['type'] == 1) {
            $this->current_text = trim($object['arguments'][0]['messages'][0]['text']);
        }
        elseif ($object['type'] == 2) {
            $lines = [];

            foreach ($object['item']['messages'] as $message) {
                if ($message['author'] != 'bot' || empty($message['text']))
                    continue;

                $lines[] = $message['text'];
            }

            $this->current_text = trim(implode("\n", $lines));
        }
        elseif ($object['type'] == 3) {
            $close = true;
        }
        else {
            echo "unsupported type: ";
            echo json_encode($object) . PHP_EOL;
        }

        if (! is_null($callback)) {
            call_user_func_array($callback, [$this->current_text, $this->current_cards]);
        }

        return $close;
    }

    protected function generateUUID() 
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}