<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreateUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class CreateUsuarioHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(CreateUsuarioCommand $command): UsuarioDTO
    {
        $model = $this->repository->create([
            'username'      => $command->username,
            'email'         => $command->email,
            'password_hash' => Hash::make($command->password),
            'nombres'       => $command->nombres,
            'apellidos'     => $command->apellidos,
            'telefono'      => $command->telefono,
            'estado'        => $command->estado,
        ]);

        return UsuarioDTO::fromModel($model);
    }
}
