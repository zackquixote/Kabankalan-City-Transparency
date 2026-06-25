<?php

namespace App\Libraries;

/**
 * Openmodel Library
 *
 * Integrates with the OpenModel API gateway (https://api.openmodel.ai).
 * Supports both OpenAI-style (/v1/responses) and Anthropic-style (/v1/messages)
 * request formats, auto-detected from the active model name.
 *
 * Load via:
 *   $openmodel = new \App\Libraries\Openmodel();
 *   $reply = $openmodel->ask('Hello!', $history);
 */
class Openmodel
{
    /**
     * @var string OpenModel API key loaded from environment variable OPENMODEL_API_KEY.
     */
    protected string $apiKey;

    /**
     * @var string Model identifier loaded from environment variable OPENMODEL_MODEL.
     *             Examples: "gpt-4o", "claude-opus-4-5", "mistral-large-latest"
     */
    protected string $model;

    /**
     * @var string Base URL for the OpenModel API gateway.
     */
    protected string $baseUrl = 'https://api.openmodel.ai';

    // ----------------------------------------------------------------

    /**
     * Constructor
     *
     * Reads the API key and model name from environment variables so that
     * credentials are never hard-coded in source files.
     *
     * Fallback placeholders are used when the variables are absent, which
     * lets the class load without crashing but will produce API errors –
     * an intentional reminder to configure the .env file.
     */
    public function __construct()
    {
        // env() is CodeIgniter's helper; getenv() is the PHP fallback.
        $this->apiKey = env('OPENMODEL_API_KEY', getenv('OPENMODEL_API_KEY') ?: 'YOUR_OPENMODEL_API_KEY');
        $this->model  = env('OPENMODEL_MODEL',   getenv('OPENMODEL_MODEL')   ?: 'gpt-4o');
    }

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    /**
     * ask()
     *
     * Sends the user's message (plus optional prior conversation history)
     * to the OpenModel API and returns the assistant's text reply.
     *
     * The request format (OpenAI vs Anthropic) is chosen automatically
     * based on the model name — see detectFormat().
     *
     * @param  string $message The latest user message.
     * @param  array  $history Array of prior turns in the form:
     *                         [['role' => 'user'|'assistant', 'content' => '...'], ...]
     * @return string          The assistant's reply, or a human-readable error string.
     */
    public function ask(string $message, array $history = []): string
    {
        // Choose the correct endpoint and body builder for the active model.
        $format = $this->detectFormat();

        try {
            if ($format === 'anthropic') {
                return $this->sendAnthropic($message, $history);
            }

            if ($format === 'openai') {
                return $this->sendOpenAI($message, $history);
            }

            // Default: /v1/chat/completions (DeepSeek, Mistral, LLaMA, etc.)
            return $this->sendChat($message, $history);

        } catch (\Exception $e) {
            // Surface a readable message instead of a fatal error.
            return '[Chatbot error] ' . $e->getMessage();
        }
    }

    // ----------------------------------------------------------------
    // Format detection
    // ----------------------------------------------------------------

    /**
     * detectFormat()
     *
     * Determines which API request format to use by inspecting the model name.
     *
     * - Models beginning with "claude"      → Anthropic /v1/messages
     * - Models beginning with "o1", "o3",
     *   "o4", or "gpt" that use the Responses
     *   API (OpenAI-native)                 → OpenAI    /v1/responses
     * - Everything else (deepseek, mistral,
     *   llama, qwen, etc.)                  → Chat      /v1/chat/completions
     *
     * Extend this list as OpenModel adds new model families.
     *
     * @return string 'anthropic' | 'openai' | 'chat'
     */
    protected function detectFormat(): string
    {
        // Anthropic family + DeepSeek (confirmed via API probe to use /v1/messages).
        if (stripos($this->model, 'claude') === 0 || stripos($this->model, 'deepseek') === 0) {
            return 'anthropic';
        }

        // OpenAI Responses-API models (o-series and GPT-4o variants).
        // These are the only models confirmed to support /v1/responses.
        $responsesModels = ['o1', 'o3', 'o4', 'gpt-4o', 'gpt-4-turbo', 'gpt-4'];
        foreach ($responsesModels as $prefix) {
            if (stripos($this->model, $prefix) === 0) {
                return 'openai';
            }
        }

        // Default: standard chat-completions format supported by virtually
        // every other provider (Mistral, LLaMA, Qwen, etc.).
        return 'chat';
    }

    // ----------------------------------------------------------------
    // OpenAI-style request  (/v1/responses)
    // ----------------------------------------------------------------

    /**
     * sendOpenAI()
     *
     * Builds and dispatches an OpenAI-compatible request to /v1/responses.
     *
     * The "input" field accepts an array of message objects when history is
     * provided, or a plain string for single-turn interactions.
     *
     * Response shape expected:
     *   { "output": [{ "content": [{ "text": "..." }] }] }
     *
     * @param  string $message Latest user message.
     * @param  array  $history Previous conversation turns.
     * @return string          Assistant's reply text.
     * @throws \RuntimeException On HTTP or API-level errors.
     */
    protected function sendOpenAI(string $message, array $history): string
    {
        $endpoint = $this->baseUrl . '/v1/responses';

        // Build the "input" value – a plain string when there is no history,
        // or a structured messages array when context must be preserved.
        if (empty($history)) {
            $input = $message;
        } else {
            // Combine history with the latest user turn.
            $input = array_merge($history, [
                ['role' => 'user', 'content' => $message],
            ]);
        }

        $body = [
            'model' => $this->model,
            'input' => $input,
        ];

        $rawResponse = $this->postJson($endpoint, $body);
        $data        = json_decode($rawResponse, true);

        // Validate the expected response shape before accessing nested keys.
        if (
            isset($data['output'][0]['content'][0]['text'])
        ) {
            return $data['output'][0]['content'][0]['text'];
        }

        // Propagate any error message returned by the API.
        if (isset($data['error']['message'])) {
            throw new \RuntimeException('API error: ' . $data['error']['message']);
        }

        throw new \RuntimeException('Unexpected API response shape: ' . $rawResponse);
    }

    // ----------------------------------------------------------------
    // Anthropic-style request  (/v1/messages)
    // ----------------------------------------------------------------

    /**
     * sendAnthropic()
     *
     * Builds and dispatches an Anthropic-compatible request to /v1/messages.
     *
     * The history array is passed directly as the "messages" field; the latest
     * user turn is appended at the end.
     *
     * Response shape expected:
     *   { "content": [{ "type": "text", "text": "..." }] }
     *
     * @param  string $message Latest user message.
     * @param  array  $history Previous conversation turns.
     * @return string          Assistant's reply text.
     * @throws \RuntimeException On HTTP or API-level errors.
     */
    protected function sendAnthropic(string $message, array $history): string
    {
        $endpoint = $this->baseUrl . '/v1/messages';

        // Append the current user turn to the history.
        $messages = array_merge($history, [
            ['role' => 'user', 'content' => $message],
        ]);

        $body = [
            'model'      => $this->model,
            'max_tokens' => 1024,   // Required field for Anthropic-style requests.
            'messages'   => $messages,
        ];

        $rawResponse = $this->postJson($endpoint, $body);
        $data        = json_decode($rawResponse, true);

        // Walk the Anthropic response structure to extract the first text block.
        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                if (isset($block['type'], $block['text']) && $block['type'] === 'text') {
                    return $block['text'];
                }
            }
        }

        // Surface API-level errors.
        if (isset($data['error']['message'])) {
            throw new \RuntimeException('API error: ' . $data['error']['message']);
        }

        throw new \RuntimeException('Unexpected API response shape: ' . $rawResponse);
    }

    // ----------------------------------------------------------------
    // Chat-completions format  (/v1/chat/completions)
    // ----------------------------------------------------------------

    /**
     * sendChat()
     *
     * Builds and dispatches a standard OpenAI-compatible chat-completions
     * request to /v1/chat/completions.
     *
     * This format is supported by the vast majority of open-weight models
     * available through OpenModel (DeepSeek, Mistral, LLaMA, Qwen, etc.).
     *
     * Response shape expected:
     *   { "choices": [{ "message": { "content": "..." } }] }
     *
     * @param  string $message Latest user message.
     * @param  array  $history Previous conversation turns.
     * @return string          Assistant's reply text.
     * @throws \RuntimeException On HTTP or API-level errors.
     */
    protected function sendChat(string $message, array $history): string
    {
        $endpoint = $this->baseUrl . '/v1/chat/completions';

        // Combine history with the latest user turn.
        $messages = array_merge($history, [
            ['role' => 'user', 'content' => $message],
        ]);

        $body = [
            'model'    => $this->model,
            'messages' => $messages,
        ];

        $rawResponse = $this->postJson($endpoint, $body);
        $data        = json_decode($rawResponse, true);

        // Standard chat-completions response shape.
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        // Surface API-level errors.
        if (isset($data['error']['message'])) {
            throw new \RuntimeException('API error: ' . $data['error']['message']);
        }

        throw new \RuntimeException('Unexpected API response shape: ' . $rawResponse);
    }

    // ----------------------------------------------------------------
    // HTTP transport
    // ----------------------------------------------------------------

    /**
     * postJson()
     *
     * Low-level helper that posts a JSON body to any endpoint using
     * CodeIgniter's built-in CURLRequest service.
     *
     * This keeps all HTTP concerns in one place and avoids duplicating
     * header / option setup in every format-specific method.
     *
     * @param  string $url  Full endpoint URL.
     * @param  array  $body Associative array to be JSON-encoded as the request body.
     * @return string       Raw response body string.
     * @throws \RuntimeException On cURL / network failures or non-2xx HTTP status codes.
     */
    protected function postJson(string $url, array $body): string
    {
        // Instantiate CI's CURLRequest service.
        // Setting 'verify' => false is acceptable for development on localhost;
        // set it to true (or provide a CA bundle path) in production.
        $client = \Config\Services::curlrequest([
            'verify' => false, // Set true in production with a valid CA bundle.
        ]);

        // Send the POST request with the required headers.
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            // 'json' option automatically encodes the array and sets Content-Type.
            'json' => $body,
        ]);

        $statusCode = $response->getStatusCode();
        $body       = $response->getBody();

        // Treat any non-2xx status as an error.
        if ($statusCode < 200 || $statusCode >= 300) {
            // Try to extract a message from the JSON error body first.
            $errorData = json_decode($body, true);
            $errorMsg  = $errorData['error']['message'] ?? $body;
            throw new \RuntimeException("HTTP {$statusCode}: {$errorMsg}");
        }

        return $body;
    }
}
