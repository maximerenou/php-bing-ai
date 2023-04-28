<?php

namespace MaximeRenou\BingAI;

use MaximeRenou\BingAI\Chat\Conversation;
use MaximeRenou\BingAI\Images\ImageCreator;

class BingAI
{
    protected $cookie;

    public function __construct($cookie)
    {
        $this->cookie = $cookie;
    }

    public function createChatConversation($data = null)
    {
        return new Conversation($this->cookie, $data, 0);
    }

    public function resumeChatConversation($data = null, $invocations = 1)
    {
        return new Conversation($this->cookie, $data, $invocations);
    }

    public function getImageCreator()
    {
        return new ImageCreator($this->cookie);
    }

    public function createImages($prompt)
    {
        return $this->getImageCreator()->create($prompt);
    }

    public function checkCookie()
    {
        $html = Tools::request('https://www.bing.com', [
            'cookie: _U=' . $this->cookie,
            'method: GET',
            'accept: text/html'
        ]);

        return strpos($html, '<span id="id_n" style="display:none" aria-hidden="true"></span>') === false;
    }
}
