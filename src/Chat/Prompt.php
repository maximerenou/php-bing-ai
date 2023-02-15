<?php

namespace MaximeRenou\BingAI\Chat;

class Prompt
{
    public $cache = true;

    public $locale;

    public $market;

    public $region;

    public function __construct(
        public $text
    ) {}

    public function withPreferences($locale = 'en-US', $market = 'en-US', $region = 'US')
    {
        $this->locale = $locale;
        $this->market = $market;
        $this->region = $region;
        return $this;
    }

    public function withoutCache()
    {
        $this->cache = false;
        return $this;
    }
}