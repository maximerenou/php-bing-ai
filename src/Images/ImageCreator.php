<?php

namespace MaximeRenou\BingAI\Images;

use MaximeRenou\BingAI\Tools;

class ImageCreator
{
    private $cookie;
    private $generation_id;
    private $prompt;
    private $failed = false;
    private $generating = false;
    private $images = [];

    public function __construct($cookie)
    {
        $this->cookie = $cookie;
    }

    public function resume($generation_id, $prompt)
    {
        $this->generation_id = $generation_id;
        $this->prompt = $prompt;
        $this->generating = true;

        return $this;
    }

    public function create($prompt)
    {
        $this->prompt = $prompt;

        $prompt_encoded = urlencode($prompt);
        $url = "https://www.bing.com/images/create?q=$prompt_encoded&rt=4&FORM=GENCRE";
        $headers = [
            'cookie: _U=' . $this->cookie,
            'method: POST',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            "accept-language: en;q=0.9",
            'content-type: application/x-www-form-urlencoded',
            'referer: https://www.bing.com/images/create?FORM=GENILP'
        ];

        list($data, $request, $url) = Tools::request($url, $headers, "q=$prompt_encoded&qs=ds", true);

        $query = parse_url($url, PHP_URL_QUERY);
        $params = [];

        Tools::debug("Query $query");

        if (! empty($query)) {
            parse_str($query, $params);

            if (isset($params['id'])) {
                Tools::debug("ID {$params['id']}");

                $this->generation_id = $params['id'];
                $this->generating = true;

                return $this;
            }
        }

        $this->failed = true;

        return $this;
    }

    public function hasFailed()
    {
        return $this->failed;
    }

    public function isGenerating()
    {
        if ($this->hasFailed())
            return false;

        $prompt_encoded = urlencode($this->prompt);
        $url = "https://www.bing.com/images/create/async/results/{$this->generation_id}?q={$prompt_encoded}";

        $data = Tools::request($url, [
            'cookie: _U=' . $this->cookie,
            'method: GET',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            "accept-language: en;q=0.9",
            'content-type: application/x-www-form-urlencoded',
            'referer: https://www.bing.com/images/create?FORM=GENILP'
        ]);

        if (! is_string($data))
            return $this->generating;

        if (preg_match_all('/(https:\/\/th.bing\.com\/th\/id\/[A-Za-z0-9._-]+)\?/', $data, $matches)) {
            $this->images = $matches[1];
            $this->generating = false;
            Tools::debug("Images: " . implode(", ", $this->images));
        }
        elseif ($data = json_decode($data, true)) {
            if (! empty($data["errorMessage"])) {
                $this->failed = true;
                Tools::debug("Error: {$data["errorMessage"]}");
            }
        }

        return $this->generating;
    }

    public function getRemainingBoosts()
    {
        $data = Tools::request("https://www.bing.com/images/create", [
            'cookie: _U=' . $this->cookie,
            'method: GET',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            "accept-language: en;q=0.9",
        ]);

        if (! is_string($data) || ! preg_match('/<div id="token_bal" aria-label="[^"]+">([0-9]+)<\/div>/', $data, $matches))
            return 0;

        return intval($matches[1]);
    }

    public function wait()
    {
        while (! $this->hasFailed()) {
            Tools::debug("Waiting for images generation...");
            sleep(1);

            if (! $this->isGenerating())
                break;
        }
    }

    public function getImages()
    {
        return $this->images;
    }

    public function getGenerationId()
    {
        return $this->generation_id;
    }
}
