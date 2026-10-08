<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI writing assistant
    |--------------------------------------------------------------------------
    | Uses the Anthropic (Claude) API. The key stays on the server in .env and is
    | never sent to the browser. With no key set, the assistant simply switches
    | itself off and the rest of AutoMail works as before.
    |
    |   ANTHROPIC_API_KEY=sk-ant-...        (create one at console.anthropic.com)
    |   ANTHROPIC_MODEL=claude-sonnet-5-5   (optional; a smaller model is cheaper)
    */

    // Which provider writes the text: 'anthropic' (default) or 'gemini'.
    //   AI_PROVIDER=gemini
    //   GEMINI_API_KEY=...        (create one at aistudio.google.com/apikey)
    //   GEMINI_MODEL=gemini-2.5-flash   (optional; check Google's current model names)
    'provider' => env('AI_PROVIDER', 'anthropic'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
    ],

    'api_key' => env('ANTHROPIC_API_KEY', ''),

    'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5-5'),

    'endpoint' => 'https://api.anthropic.com/v1/messages',

    'version' => '2023-06-01',

    'timeout' => 45, // seconds

    /*
    | AI requests each organization may make per calendar month, by plan. This is the
    | brake that stops a mistake or abuse from running up your API bill. Adjust freely.
    */
    'limits' => [
        'free' => 20,
        'starter' => 200,
        'business' => 1000,
        'pro' => 3000,
    ],

];
