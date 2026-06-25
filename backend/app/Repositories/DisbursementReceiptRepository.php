<?php

namespace App\Repositories;

use App\Models\AssistanceSubmission;
use App\Repositories\Contracts\DisbursementReceiptRepositoryInterface;

class DisbursementReceiptRepository implements DisbursementReceiptRepositoryInterface
{
    public function findBySubmissionId(string $submissionId, string $citizenId): ?AssistanceSubmission
    {
        return AssistanceSubmission::with(['disbursement.officer', 'program', 'citizen'])
            ->where('id', $submissionId)
            ->where('citizen_id', $citizenId)
            ->first();
    }
}