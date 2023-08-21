<?php

namespace MaximeRenou\BingAI\Chat;

class Message implements \JsonSerializable
{
    public static function fromPrompt(Prompt $prompt)
    {
        $message = new self();
        $message->text = $prompt->text;
        $message->type = MessageType::Prompt;
        return $message;
    }

    public static function fromData($data)
    {
        $message = new self($data);
        $message->id = $data['messageId'];

        switch ($data['messageType'] ?? '') {
            case 'InternalSearchQuery':
                $message->type = MessageType::InternalSearchQuery;
                break;
            case 'InternalSearchResult':
                $message->type = MessageType::SearchResult;
                break;
            case 'InternalLoaderMessage':
                $message->type = MessageType::Loader;
                break;
            case 'SemanticSerp':
                $message->type = MessageType::SemanticSerp;
                break;
            case 'Disengaged':
                $message->type = MessageType::Disengaged;
                break;
            case 'AdsQuery':
                $message->type = MessageType::AdsQuery;
                break;
            case 'ActionRequest':
                $message->type = MessageType::ActionRequest;
                break;
            case 'RenderCardRequest':
                $message->type = MessageType::RenderRequest;
                break;
            case 'SearchQuery':
                $message->type = MessageType::SearchQuery;
                break;
            case 'GenerateContentQuery':
                $message->type = MessageType::GenerateQuery;
                break;
                break;
            case 'Context':
                $message->type = MessageType::Context;
                break;
                break;
            case 'Progress':
                $message->type = MessageType::Progress;
                break;
            default:
                $message->type = $data['author'] == 'user' ? MessageType::Prompt : MessageType::Answer;
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
    public $data;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

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
            'data' => $this->data
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
