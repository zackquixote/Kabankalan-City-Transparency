<?php

namespace App\Services;

use App\Models\FeedbackModel;
use App\Models\ProjectModel;
use CodeIgniter\I18n\Time;

/**
 * Citizen feedback submission and staff moderation with input sanitization.
 */
class FeedbackService
{
    public const MIN_BODY_LENGTH = 10;
    public const MAX_BODY_LENGTH = 5000;

    /** @var array<string, string> */
    private array $errors = [];

    public function __construct(
        private ?FeedbackModel $feedbackModel = null,
        private ?ProjectModel $projectModel = null,
    ) {
        $this->feedbackModel = $feedbackModel ?? model(FeedbackModel::class);
        $this->projectModel  = $projectModel ?? model(ProjectModel::class);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array<string, mixed> $input author_name, author_email?, body, project_id, user_id?
     *
     * @return array{id: int}|false
     */
    public function submit(array $input): array|false
    {
        $this->errors = [];
        $errors = $this->validateSubmission($input);

        if ($errors !== []) {
            $this->errors = $errors;

            return false;
        }

        $projectId = (int) $input['project_id'];
        $project   = $this->projectModel->find($projectId);

        if ($project === null) {
            $this->errors = ['project_id' => 'Project not found.'];

            return false;
        }

        if (! in_array($project['status'], ['published', 'completed'], true)) {
            $this->errors = ['project_id' => 'Feedback is only accepted on published or completed projects.'];

            return false;
        }

        $body = $this->sanitizeBody((string) $input['body']);

        if (strlen($body) < self::MIN_BODY_LENGTH) {
            $this->errors = [
                'body' => 'Message must be at least ' . self::MIN_BODY_LENGTH . ' characters after sanitization.',
            ];

            return false;
        }

        $payload = [
            'project_id'   => $projectId,
            'user_id'      => isset($input['user_id']) && is_numeric($input['user_id']) ? (int) $input['user_id'] : null,
            'author_name'  => trim((string) $input['author_name']),
            'author_email' => isset($input['author_email']) ? trim((string) $input['author_email']) : null,
            'body'         => $body,
            'status'       => 'pending',
        ];

        $id = $this->feedbackModel->insert($payload, true);

        return $id !== false ? ['id' => (int) $id] : false;
    }

    /**
     * @param array<string, mixed> $input admin_response, status, responded_by
     *
     * @return true|false
     */
    public function moderate(int $feedbackId, array $input): bool
    {
        $this->errors = [];
        $feedback = $this->feedbackModel->find($feedbackId);

        if ($feedback === null) {
            $this->errors = ['id' => 'Feedback not found.'];

            return false;
        }

        $status = $input['status'] ?? null;

        if (! is_string($status) || ! in_array($status, FeedbackModel::STATUSES, true)) {
            $this->errors = ['status' => 'Invalid feedback status.'];

            return false;
        }

        $response = isset($input['admin_response']) ? $this->sanitizeBody((string) $input['admin_response']) : null;

        if ($response !== null && strlen($response) > self::MAX_BODY_LENGTH) {
            $this->errors = ['admin_response' => 'Response is too long.'];

            return false;
        }

        $respondedBy = isset($input['responded_by']) && is_numeric($input['responded_by'])
            ? (int) $input['responded_by']
            : null;

        return $this->feedbackModel->update($feedbackId, [
            'status'         => $status,
            'admin_response' => $response,
            'responded_by'   => $respondedBy,
            'responded_at'   => Time::now()->toDateTimeString(),
        ]);
    }

    /**
     * Strip script tags and other HTML before storage.
     */
    public function sanitizeBody(string $body): string
    {
        $body = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $body) ?? $body;
        $body = preg_replace('/<\/?script\b[^>]*>/i', '', $body) ?? $body;
        $body = strip_tags($body);
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace('/\s+/u', ' ', $body) ?? $body;

        return trim($body);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, string>
     */
    private function validateSubmission(array $input): array
    {
        $errors = [];

        if (! isset($input['project_id']) || ! is_numeric($input['project_id']) || (int) $input['project_id'] < 1) {
            $errors['project_id'] = 'A valid project is required.';
        }

        $authorName = trim((string) ($input['author_name'] ?? ''));

        if ($authorName === '') {
            $errors['author_name'] = 'Your name is required.';
        } elseif (strlen($authorName) > 150) {
            $errors['author_name'] = 'Name is too long.';
        }

        if (isset($input['author_email']) && $input['author_email'] !== '') {
            if (! filter_var($input['author_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['author_email'] = 'Please provide a valid email address.';
            } elseif (strlen((string) $input['author_email']) > 255) {
                $errors['author_email'] = 'Email is too long.';
            }
        }

        $rawBody = (string) ($input['body'] ?? '');

        if ($rawBody === '') {
            $errors['body'] = 'Message is required.';
        } elseif (strlen($rawBody) > self::MAX_BODY_LENGTH) {
            $errors['body'] = 'Message must not exceed ' . self::MAX_BODY_LENGTH . ' characters.';
        }

        return $errors;
    }
}
