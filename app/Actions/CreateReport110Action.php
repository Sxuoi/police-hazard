<?php

namespace App\Actions;

use App\Repositories\Contracts\Report110RepositoryInterface;
use Illuminate\Support\Str;
use App\Models\Report110;

class CreateReport110Action
{
    public function __construct(
        protected Report110RepositoryInterface $reportRepository
    ) {}

    public function execute(array $data): Report110
    {
        // Generate a secure random token (40 chars)
        $data['token'] = Str::random(40);
        
        // Set default status as requested
        $data['status'] = 'Butuh penanganan';

        return $this->reportRepository->create($data);
    }
}
