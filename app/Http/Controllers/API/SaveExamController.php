<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\DTO\SaveExamDTO;
use App\Http\Controllers\Controller;
use App\Services\ExamSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SaveExamController extends Controller
{
    public function __construct(
        private ExamSubmissionService $submissionService
    ) {}

    /**
     * Save the editor iframe progress into the submissions table.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Validate incoming data
        $dto = SaveExamDTO::fromRequest($request->all());

        // 2. Persist using the logged-in user's instance
        $submission = $this->submissionService->saveProgress(
            user: $request->user(),
            dto: $dto
        );

        return response()->json([
            'success' => true,
            'message' => 'Exam progress saved successfully.',
            'submission_id' => $submission->id,
        ]);
    }
}