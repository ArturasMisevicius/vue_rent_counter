<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gpt_researcher_mcp' => [
        'repository' => env('GPT_RESEARCHER_MCP_REPOSITORY', 'https://github.com/assafelovic/gptr-mcp.git'),
        'branch' => env('GPT_RESEARCHER_MCP_BRANCH', 'master'),
        'path' => env('GPT_RESEARCHER_MCP_PATH', storage_path('app/mcp/gptr-mcp')),
        'python' => env('GPT_RESEARCHER_MCP_PYTHON', 'python3'),
        'transport' => env('GPT_RESEARCHER_MCP_TRANSPORT', 'stdio'),
        'openai_api_key' => env('OPENAI_API_KEY'),
        'tavily_api_key' => env('TAVILY_API_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
