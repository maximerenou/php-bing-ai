<?php

namespace MaximeRenou\BingAI\Chat;

enum MessageType: string
{
    case Answer = "answer";
    case Prompt = "prompt";
    case SearchQuery = "search_query";
    case SearchResult = "search_result";
    case Loader = "loader";
    case RenderRequest = "render_request";
}