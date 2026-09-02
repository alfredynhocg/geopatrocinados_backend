<?php

namespace Database\Seeders\Patrocinados;

use Illuminate\Database\Seeder;

/**
 * Orquesta el seed completo del módulo, en el orden de dependencias:
 * Acceso (usuarios) -> Geografia (comunidades/ubicaciones) -> Patrocinados
 * (niños/tutores) -> Visitas (motivos/categorias/plan/visitas demo).
 *
 * Uso: php artisan db:seed --class="Database\Seeders\Patrocinados\PatrocinadosDatabaseSeeder"
 */
class PatrocinadosDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccesoPatrocinadosSeeder::class,
            GeografiaSeeder::class,
            PatrocinadosSeeder::class,
            VisitasSeeder::class,
        ]);
    }
}
