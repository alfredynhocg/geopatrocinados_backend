<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\TutorDTO;
use App\Application\Patrocinados\Queries\GetTutorByIdQuery;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class GetTutorByIdQueryHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(GetTutorByIdQuery $query): TutorDTO
    {
        return TutorDTO::fromModel($this->repository->findById($query->id));
    }
}
