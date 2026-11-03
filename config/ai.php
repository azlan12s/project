<?php
/**
 * AI Chatbot Configuration for Fan Hub Plus
 *
 * You can paste your own API key below (Gemini, Groq, or OpenAI), or set it
 * via environment variables. When no key is set, the chatbot automatically
 * connects to the built-in OpenAI-compatible AI endpoint + local fandom engine.
 */
return array(
    // Google Gemini API Key (https://aistudio.google.com/app/apikey)
    'gemini_api_key' => getenv('GEMINI_API_KEY') ? getenv('GEMINI_API_KEY') : '',
    'gemini_model'   => 'gemini-2.5-flash',

    // Groq API Key (https://console.groq.com/keys)
    'groq_api_key'   => getenv('GROQ_API_KEY') ? getenv('GROQ_API_KEY') : '',
    'groq_model'     => 'llama-3.3-70b-versatile',

    // OpenAI API Key (https://platform.openai.com/api-keys)
    'openai_api_key' => getenv('OPENAI_API_KEY') ? getenv('OPENAI_API_KEY') : '',
    'openai_model'   => 'gpt-4o-mini',

    // Built-in keyless AI endpoint fallback (works out-of-the-box with internet)
    'free_ai_enabled' => true,
);
