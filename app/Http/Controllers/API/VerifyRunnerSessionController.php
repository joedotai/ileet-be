<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ExamSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Handle verification requests from the external iframe (exam-runner).
 * * This endpoint performs two vital roles:
 * 1. Validates that the runner has a live authenticated user context via Sanctum.
 * 2. Authenticates, locates, or establishes the correct active exam submission 
 * state for the runner UI.
 */
final class VerifyRunnerSessionController extends Controller
{
    public function __construct(
        private ExamSubmissionService $submissionService
    ) {}

    /**
     * Verify the runner user session and fetch the active exam submission state.
     *
     * @param Request $request Expects a query parameter or payload containing 'exam_id'
     * @throws ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Ensure the iframe specified which exam it is running
        $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
        ]);

        $user = $request->user();
        $examId = (int) $request->input('exam_id');

        // 2. Resolve or create the user's active submission session for this specific exam
        $submission = $this->submissionService->getOrCreateActiveSubmission($user, $examId);

        // 3. Return the user payload alongside their tied exam configuration/state
        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'session' => [
                'submission_id' => $submission->id,
                'status' => $submission->status, // e.g., 'in_progress'
                'started_at' => $submission->created_at,
                'saved_contents' => $submission->contents, // Return last auto-saved state if any
            ],
        ]);
    }
}