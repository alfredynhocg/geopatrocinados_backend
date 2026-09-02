<?php

namespace App\Infrastructure\Patrocinados\Concerns;

trait UsaConexionPatrocinados
{
    public function getConnectionName(): string
    {
        return 'pgsql_patrocinados';
    }
}
