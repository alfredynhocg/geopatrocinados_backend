<?php

namespace App\Application\Patrocinados\Commands;

final readonly class DeleteTutorCommand
{
    public function __construct(public string $id) {}
}
