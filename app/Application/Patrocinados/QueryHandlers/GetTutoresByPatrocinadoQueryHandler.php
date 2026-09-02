<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\TutorDTO;
use App\Application\Patrocinados\Queries\GetTutoresByPatrocinadoQuery;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class GetTutoresByPatrocinadoQueryHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(GetTutoresByPatrocinadoQuery $query): array
    {
        $paginated = $this->repository->paginateByPatrocinado($query->patrocinado_id, $query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => TutorDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
