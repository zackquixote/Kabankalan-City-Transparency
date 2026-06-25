<?php

namespace App\Controllers;

use App\Libraries\Openmodel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Chatbot Controller
 *
 * Handles the chatbot UI (index) and the AJAX message endpoint (send).
 * Conversation history is preserved across page loads using CI4's session service.
 */
class Chatbot extends BaseController
{
    /**
     * @var \CodeIgniter\Session\Session CI4 session instance.
     */
    protected $session;

    /**
     * @var Openmodel Openmodel library instance.
     */
    protected Openmodel $openmodel;

    /**
     * Maximum number of history turns (user + assistant pairs) to keep in
     * session and send with each request.  Keeps token usage reasonable while
     * still providing enough context for a useful conversation.
     */
    protected int $maxHistoryTurns = 10;

    // ----------------------------------------------------------------

    /**
     * initController()
     *
     * Called automatically by CI4 before any action method.
     * Loads the session service and the Openmodel library.
     *
     * {@inheritDoc}
     */
    public function initController(
        \CodeIgniter\HTTP\RequestInterface  $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface            $logger
    ) {
        parent::initController($request, $response, $logger);

        // Load CI4's session service so history can be stored server-side.
        $this->session   = \Config\Services::session();

        // Instantiate the Openmodel library directly.
        // Using `new` is preferred over service() for custom libraries not
        // registered as CI4 services.
        $this->openmodel = new Openmodel();
    }

    // ----------------------------------------------------------------
    // Actions
    // ----------------------------------------------------------------

    /**
     * index()
     *
     * Renders the chat UI.  Passes the current conversation history to the
     * view so existing messages are displayed when the user reloads the page.
     *
     * Route: GET /chatbot
     *
     * @return string Rendered HTML.
     */
    public function index(): string
    {
        // Retrieve any existing history from the session (default: empty array).
        $history = $this->session->get('chat_history') ?? [];

        return view('chatbot_view', [
            'history' => $history,
        ]);
    }

    // ----------------------------------------------------------------

    /**
     * send()
     *
     * Receives a POST request from the chat UI, forwards the message to the
     * Openmodel API with prior context, stores the new turn in the session,
     * and returns the assistant's reply as JSON.
     *
     * Route: POST /chatbot/send
     *
     * @return ResponseInterface JSON response: { "reply": "..." }
     *                            or { "error": "..." } on validation failure.
     */
    public function send(): ResponseInterface
    {
        // ── 1. Read and validate the incoming message ──────────────────
        $message = trim((string) $this->request->getPost('message'));

        if ($message === '') {
            // Return a 400 so the front-end can distinguish validation errors
            // from successful (but empty) bot replies.
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => 'Message cannot be empty.']);
        }

        // ── 2. Load conversation history from the session ──────────────
        $history = $this->session->get('chat_history') ?? [];

        // ── 3. Call the Openmodel library ──────────────────────────────
        // $history contains previous [role, content] pairs; the library
        // appends the current $message before sending.
        $reply = $this->openmodel->ask($message, $history);

        // ── 4. Append the new turn to the history ──────────────────────
        $history[] = ['role' => 'user',      'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $reply];

        // Trim to the configured maximum to avoid unbounded session growth.
        // Each "turn" is 2 entries (user + assistant), so multiply by 2.
        if (count($history) > $this->maxHistoryTurns * 2) {
            // Drop the oldest turns from the front of the array.
            $history = array_slice($history, -($this->maxHistoryTurns * 2));
        }

        // ── 5. Persist updated history ─────────────────────────────────
        $this->session->set('chat_history', $history);

        // ── 6. Return JSON reply to the front-end ──────────────────────
        return $this->response->setJSON(['reply' => $reply]);
    }

    // ----------------------------------------------------------------

    /**
     * clearHistory()
     *
     * Optional action that removes the stored conversation from the session,
     * allowing the user to start a fresh chat.
     *
     * Route: POST /chatbot/clear  (add to Routes.php as needed)
     *
     * @return ResponseInterface JSON confirmation.
     */
    public function clearHistory(): ResponseInterface
    {
        // Remove only the chatbot history key, not the entire session.
        $this->session->remove('chat_history');

        return $this->response->setJSON(['status' => 'History cleared.']);
    }
}
