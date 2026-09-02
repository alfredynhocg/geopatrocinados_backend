<?php

namespace Database\Seeders\Patrocinados;

use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use App\Infrastructure\Geografia\Models\Comunidad;
use App\Infrastructure\Patrocinados\Models\EstadoPatrocinado;
use App\Infrastructure\Patrocinados\Models\Patrocinado;
use App\Infrastructure\Patrocinados\Models\TipoParentesco;
use App\Infrastructure\Patrocinados\Models\Tutor;
use Illuminate\Database\Seeder;

class PatrocinadosSeeder extends Seeder
{
    /** Enum cerrado por el CHECK constraint de la migración de estados_patrocinados. */
    private const ESTADOS = ['ACTIVO', 'NO_ENCONTRADO', 'INACTIVO_NO_UBICADO', 'MAYOR_DE_EDAD'];

    private const TIPOS_PARENTESCO = ['MADRE', 'PADRE', 'ABUELO/A', 'TIO/A', 'HERMANO/A MAYOR', 'TUTOR LEGAL', 'OTRO'];

    public function run(): void
    {
        $admin = Usuario::where('username', 'superadmin')->first();

        $estados = collect(self::ESTADOS)->mapWithKeys(function (string $estado) use ($admin) {
            $model = EstadoPatrocinado::firstOrCreate(
                ['estado' => $estado],
                ['updated_by' => $admin?->id],
            );

            return [$estado => $model->id];
        });

        $parentescos = collect(self::TIPOS_PARENTESCO)->mapWithKeys(function (string $parentesco) use ($admin) {
            $model = TipoParentesco::firstOrCreate(
                ['parentesco' => $parentesco],
                ['estado' => true, 'updated_by' => $admin?->id],
            );

            return [$parentesco => $model->id];
        });

        $comunidadCercado = Comunidad::where('comunidad', 'Zona Central')->first();
        $comunidadQuillacollo = Comunidad::where('comunidad', 'Zona Norte')->first();

        if (! $comunidadCercado || ! $comunidadQuillacollo) {
            // GeografiaSeeder no corrió todavía — sin comunidad_id no se puede crear un patrocinado.
            return;
        }

        $ninos = [
            [
                'codigo' => 'PAT-0001',
                'nombres' => 'Ana Lucía',
                'apellidos' => 'Mamani Quispe',
                'fecha_nacimiento' => '2015-03-12',
                'sexo' => 'FEMENINO',
                'comunidad_id' => $comunidadCercado->id,
                'unidad_educativa' => 'Unidad Educativa Simón Bolívar',
                'nivel_educativo' => 'PRIMARIA',
                'estado_id' => $estados['ACTIVO'],
                'tutor' => ['nombres' => 'Rosa', 'apellidos' => 'Quispe Choque', 'tipo' => 'MADRE', 'telefono' => '70011122', 'direccion' => 'Av. Blanco Galindo km 4'],
            ],
            [
                'codigo' => 'PAT-0002',
                'nombres' => 'Marco Antonio',
                'apellidos' => 'Condori Flores',
                'fecha_nacimiento' => '2012-08-25',
                'sexo' => 'MASCULINO',
                'comunidad_id' => $comunidadQuillacollo->id,
                'unidad_educativa' => 'Unidad Educativa Simón Bolívar',
                'nivel_educativo' => 'SECUNDARIA',
                'estado_id' => $estados['ACTIVO'],
                'tutor' => ['nombres' => 'Pedro', 'apellidos' => 'Condori Mamani', 'tipo' => 'PADRE', 'telefono' => '70033445', 'direccion' => 'Barrio Villa Sebastián Pagador'],
            ],
        ];

        foreach ($ninos as $data) {
            $tutorData = $data['tutor'];
            unset($data['tutor']);

            $patrocinado = Patrocinado::firstOrCreate(
                ['codigo' => $data['codigo']],
                $data + ['updated_by' => $admin?->id],
            );

            if ($patrocinado->tutores()->count() === 0) {
                Tutor::create([
                    'patrocinado_id' => $patrocinado->id,
                    'nombres' => $tutorData['nombres'],
                    'apellidos' => $tutorData['apellidos'],
                    'tipo_parentesco_id' => $parentescos[$tutorData['tipo']],
                    'telefono' => $tutorData['telefono'],
                    'direccion' => $tutorData['direccion'],
                    'estado' => true,
                    'updated_by' => $admin?->id,
                ]);
            }
        }
    }
}
