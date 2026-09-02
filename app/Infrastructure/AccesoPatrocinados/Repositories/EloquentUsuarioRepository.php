<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\UsuarioNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentUsuarioRepository implements UsuarioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = Usuario::query()->with('roles')->whereNull('deleted_at');

        if ($pagination->query !== '') {
            $q->where(fn ($sub) => $sub
                ->where('username', 'ilike', "%{$pagination->query}%")
                ->orWhere('email', 'ilike', "%{$pagination->query}%")
                ->orWhere('nombres', 'ilike', "%{$pagination->query}%")
                ->orWhere('apellidos', 'ilike', "%{$pagination->query}%"));
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $usuario = Usuario::whereNull('deleted_at')->find($id);

        if (! $usuario) {
            throw new UsuarioNotFoundException($id);
        }

        return $usuario;
    }

    public function findByUsernameOrEmail(string $login): mixed
    {
        return Usuario::whereNull('deleted_at')
            ->where(fn ($q) => $q->where('username', $login)->orWhere('email', $login))
            ->first();
    }

    public function create(array $data): mixed
    {
        return Usuario::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $usuario = $this->findById($id);
        $usuario->update($data);

        return $usuario->fresh('roles');
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Usuario::whereIn('id', (array) $ids)->delete();
    }
}
