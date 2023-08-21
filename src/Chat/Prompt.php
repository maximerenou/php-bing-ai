<?php

namespace MaximeRenou\BingAI\Chat;

class Prompt
{
    public $cache = true;
    public $locale;
    public $market;
    public $region;
    public $text;
    public $image;

    public function __construct($text)
    {
        $this->text = $text;
    }

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

    public function withImage($image, $is_rawdata = false)
    {
        if (! $is_rawdata) {
            $image = file_get_contents($image);
        }

        $this->image = $image;
        return $this;
    }
}