<?php

namespace App\Repositories\Contracts;

use App\Models\AssistanceSubmission;

interface DisbursementReceiptRepositoryInterface
{
    public function findBySubmissionId(string $submissionId, string $citizenId): ?AssistanceSubmission;
}