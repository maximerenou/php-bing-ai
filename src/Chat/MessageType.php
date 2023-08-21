<?php

namespace MaximeRenou\BingAI\Chat;

class MessageType
{
    const Answer = "answer";
    const Prompt = "prompt";
    const InternalSearchQuery = "internal_search_query";
    const SearchResult = "search_result";
    const Loader = "loader";
    const RenderRequest = "render_request";
    const SemanticSerp = "semantic_serp";
    const Disengaged = "disengaged";
    const AdsQuery = "ads_query";
    const ActionRequest = "action_request";
    const SearchQuery = "search_query";
    const GenerateQuery = "generate_query";
    const Context = "context";
    const Progress = "progress";
}
