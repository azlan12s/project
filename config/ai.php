<?php


return array(

    'gemini_api_key' => getenv('GEMINI_API_KEY') ? getenv('GEMINI_API_KEY') : '',
    'gemini_model'   => 'gemini-2.5-flash',


    'groq_api_key'   => getenv('GROQ_API_KEY') ? getenv('GROQ_API_KEY') : '',
    'groq_model'     => 'llama-3.3-70b-versatile',


    'openai_api_key' => getenv('OPENAI_API_KEY') ? getenv('OPENAI_API_KEY') : '',
    'openai_model'   => 'gpt-4o-mini',


    'free_ai_enabled' => true,
);

