<?php

namespace MaximeRenou\BingAI\Chat;

class MessageType
{
    const Answer = "answer";
    const Prompt = "prompt";
    const SearchQuery = "search_query";
    const SearchResult = "search_result";
    const Loader = "loader";
    const RenderRequest = "render_request";
}