<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function summarizeTodo(array $todo): string
    {
        $prompt = "Given the following todo item, summarize it and provide a meaningful objective:\n\n";
        $prompt .= "Title: {$todo['title']}\n";
        $prompt .= "Description: {$todo['description']}\n\n";
        $prompt .= "Return format:\nSummary: ...\nObjective: ...";

        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

        return $response->json('choices')[0]['message']['content'] ?? 'No response from OpenAI';
    }
}
