<?php

namespace App\Application\Geografia\Commands;

final readonly class DeleteDepartamentoCommand
{
    public function __construct(public string $id) {}
}
