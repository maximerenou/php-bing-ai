<?php

namespace MaximeRenou\BingAI;

use MaximeRenou\BingAI\Chat\Conversation;

class BingAI
{
    public function __construct() 
    {
        //
    }

    public function createChatConversation($cookie, $data = null)
    {
        return new Conversation($cookie, $data);
    }
}