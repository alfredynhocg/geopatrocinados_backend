<?php

namespace Database\Seeders\Patrocinados;

use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use App\Infrastructure\Geografia\Models\Comunidad;
use App\Infrastructure\Geografia\Models\Departamento;
use App\Infrastructure\Geografia\Models\Municipio;
use App\Infrastructure\Geografia\Repositories\EloquentUbicacionRepository;
use Illuminate\Database\Seeder;

class GeografiaSeeder extends Seeder
{
    /** Los 9 departamentos de Bolivia. */
    private const DEPARTAMENTOS = [
        ['codigo' => 'LP', 'departamento' => 'La Paz'],
        ['codigo' => 'CB', 'departamento' => 'Cochabamba'],
        ['codigo' => 'SC', 'departamento' => 'Santa Cruz'],
        ['codigo' => 'OR', 'departamento' => 'Oruro'],
        ['codigo' => 'PT', 'departamento' => 'Potosí'],
        ['codigo' => 'CH', 'departamento' => 'Chuquisaca'],
        ['codigo' => 'TJ', 'departamento' => 'Tarija'],
        ['codigo' => 'BE', 'departamento' => 'Beni'],
        ['codigo' => 'PD', 'departamento' => 'Pando'],
    ];

    public function run(): void
    {
        $admin = Usuario::where('username', 'superadmin')->first();

        $departamentos = collect(self::DEPARTAMENTOS)->mapWithKeys(function (array $d) use ($admin) {
            $dep = Departamento::firstOrCreate(
                ['codigo' => $d['codigo']],
                ['departamento' => $d['departamento'], 'estado' => true, 'updated_by' => $admin?->id],
            );

            return [$d['codigo'] => $dep->id];
        });

        $cochabamba = $departamentos['CB'];

        $cercado = Municipio::firstOrCreate(
            ['codigo' => 'CB-01'],
            ['departamento_id' => $cochabamba, 'municipio' => 'Cercado (Cochabamba)', 'estado' => true, 'updated_by' => $admin?->id],
        );
        $quillacollo = Municipio::firstOrCreate(
            ['codigo' => 'CB-02'],
            ['departamento_id' => $cochabamba, 'municipio' => 'Quillacollo', 'estado' => true, 'updated_by' => $admin?->id],
        );
        $sacaba = Municipio::firstOrCreate(
            ['codigo' => 'CB-03'],
            ['departamento_id' => $cochabamba, 'municipio' => 'Sacaba', 'estado' => true, 'updated_by' => $admin?->id],
        );

        $comunidadCercado = Comunidad::firstOrCreate(
            ['municipio_id' => $cercado->id, 'comunidad' => 'Zona Central'],
            ['codigo' => 'COM-001', 'estado' => true, 'updated_by' => $admin?->id],
        );
        $comunidadQuillacollo = Comunidad::firstOrCreate(
            ['municipio_id' => $quillacollo->id, 'comunidad' => 'Zona Norte'],
            ['codigo' => 'COM-002', 'estado' => true, 'updated_by' => $admin?->id],
        );
        $comunidadSacaba = Comunidad::firstOrCreate(
            ['municipio_id' => $sacaba->id, 'comunidad' => 'Zona Villa Sebastián Pagador'],
            ['codigo' => 'COM-003', 'estado' => true, 'updated_by' => $admin?->id],
        );

        // Se usa el Repository (no el Model directo) para que punto_geografico
        // se derive de latitude/longitude igual que en producción.
        $ubicacionRepository = new EloquentUbicacionRepository();

        if ($comunidadCercado->ubicaciones()->count() === 0) {
            $ubicacionRepository->create([
                'comunidad_id' => $comunidadCercado->id,
                'nombre' => 'Domicilio familia Mamani (demo)',
                'tipo' => 'DOMICILIO',
                'direccion' => 'Av. Blanco Galindo km 4',
                'latitude' => -17.3895,
                'longitude' => -66.1568,
                'precision_metros' => 15.0,
                'estado' => true,
                'updated_by' => $admin?->id,
            ]);
        }

        if ($comunidadQuillacollo->ubicaciones()->count() === 0) {
            $ubicacionRepository->create([
                'comunidad_id' => $comunidadQuillacollo->id,
                'nombre' => 'Unidad Educativa Simón Bolívar (demo)',
                'tipo' => 'ESCUELA',
                'direccion' => 'Calle Bolívar s/n, Quillacollo',
                'latitude' => -17.3975,
                'longitude' => -66.2792,
                'precision_metros' => 20.0,
                'estado' => true,
                'updated_by' => $admin?->id,
            ]);
        }

        if ($comunidadSacaba->ubicaciones()->count() === 0) {
            $ubicacionRepository->create([
                'comunidad_id' => $comunidadSacaba->id,
                'nombre' => 'Domicilio familia Condori (demo)',
                'tipo' => 'DOMICILIO',
                'direccion' => 'Barrio Villa Sebastián Pagador',
                'latitude' => -17.3997,
                'longitude' => -66.0396,
                'precision_metros' => 12.0,
                'estado' => true,
                'updated_by' => $admin?->id,
            ]);
        }
    }
}
