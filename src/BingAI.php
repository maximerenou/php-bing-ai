<?php

namespace MaximeRenou\BingAI;

use MaximeRenou\BingAI\Chat\Conversation;
use MaximeRenou\BingAI\Images\ImageCreator;

class BingAI
{
    public function __construct()
    {
        //
    }

    public function createChatConversation($cookie, $data = null, $invocations = 0)
    {
        return new Conversation($cookie, $data, $invocations);
    }

    public function createImages($cookie, $prompt)
    {
        $creator = new ImageCreator($cookie);

        return $creator->create($prompt);
    }
}
