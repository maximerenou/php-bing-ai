<?php

namespace MaximeRenou\BingAI\Chat;

class Message implements \JsonSerializable
{
    public static function fromPrompt(Prompt $prompt)
    {
        $message = new self(true);
        $message->text = $prompt->text;
        $message->type = MessageType::Prompt;
        return $message;
    }

    public static function fromData($data)
    {
        $message = new self($data['author'] == 'user', $data);
        $message->id = $data['messageId'];

        switch ($data['messageType'] ?? '') {
            case 'InternalSearchQuery':
                $message->type = MessageType::SearchQuery;
                break;
            case 'InternalSearchResult':
                $message->type = MessageType::SearchResult;
                break;
            case 'InternalLoaderMessage':
                $message->type = MessageType::Loader;
                break;
            case 'RenderCardRequest':
                $message->type = MessageType::RenderRequest;
                break;
            default:
                $message->type = MessageType::Answer;
        }

        if (! empty($data['text']))
            $message->text = $data['text'];
        elseif (! empty($data['hiddenText']))
            $message->text = $data['hiddenText'];

        return $message;
    }

    public $id;
    public $text;
    public $type;

    public function __construct(
        public $local = false,
        public $data = null
    ) {}

    public function toText()
    {
        if (! empty($this->text)) {
            return preg_replace('/\[\^[0-9]+\^]/', '', $this->text);
        }

        return false;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'formatted_text' => $this->toText(),
            'type' => $this->type,
            'local' => $this->local,
            'data' => $this->data
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}