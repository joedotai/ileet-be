<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Facades\Validator;

final readonly class SaveExamDTO
{
    public function __construct(
        public int $examId,
        public array $contents,
    ) {}

    /**
     * Validate and create the DTO from the request payload.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function fromRequest(array $input): self
    {
        $validated = Validator::make($input, [
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'contents' => ['required', 'array'], // JSON editor payload
        ])->validate();

        return new self(
            examId: (int) $validated['exam_id'],
            contents: $validated['contents'],
        );
    }
}