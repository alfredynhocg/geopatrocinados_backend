<?php

namespace Database\Seeders\Patrocinados;

use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use App\Infrastructure\Patrocinados\Models\Patrocinado;
use App\Infrastructure\Visitas\Models\CategoriaObservacion;
use App\Infrastructure\Visitas\Models\MotivoVisita;
use App\Infrastructure\Visitas\Models\PlanVisita;
use App\Infrastructure\Visitas\Models\Visita;
use Illuminate\Database\Seeder;

class VisitasSeeder extends Seeder
{
    private const MOTIVOS = [
        ['motivo_visita' => 'Visita de seguimiento regular', 'descripcion' => 'Visita periódica programada según el plan de visitas.'],
        ['motivo_visita' => 'Seguimiento especial', 'descripcion' => 'Visita fuera de calendario por alerta o solicitud de tutor.'],
        ['motivo_visita' => 'Verificación de datos', 'descripcion' => 'Actualización de datos socioeconómicos o educativos.'],
    ];

    private const CATEGORIAS_OBSERVACIONES = [
        ['codigo' => 'SALUD', 'categoria_observaciones' => 'Salud', 'descripcion' => 'Estado de salud general del niño/a.'],
        ['codigo' => 'EDUCACION', 'categoria_observaciones' => 'Educación', 'descripcion' => 'Situación escolar y rendimiento académico.'],
        ['codigo' => 'VIVIENDA', 'categoria_observaciones' => 'Vivienda', 'descripcion' => 'Condiciones de la vivienda familiar.'],
        ['codigo' => 'FAMILIA', 'categoria_observaciones' => 'Situación familiar', 'descripcion' => 'Dinámica y composición del hogar.'],
    ];

    public function run(): void
    {
        $admin = Usuario::where('username', 'superadmin')->first();
        $tecnico = Usuario::where('username', 'tecnico1')->first();

        $motivos = collect(self::MOTIVOS)->mapWithKeys(function (array $m) use ($admin) {
            $model = MotivoVisita::firstOrCreate(
                ['motivo_visita' => $m['motivo_visita']],
                ['descripcion' => $m['descripcion'], 'estado' => true, 'updated_by' => $admin?->id],
            );

            return [$m['motivo_visita'] => $model->id];
        });

        foreach (self::CATEGORIAS_OBSERVACIONES as $c) {
            CategoriaObservacion::firstOrCreate(
                ['codigo' => $c['codigo']],
                ['categoria_observaciones' => $c['categoria_observaciones'], 'descripcion' => $c['descripcion'], 'estado' => true, 'updated_by' => $admin?->id],
            );
        }

        if (! $tecnico) {
            // AccesoPatrocinadosSeeder no corrió todavía — sin técnico no se pueden crear visitas demo.
            return;
        }

        $plan = PlanVisita::firstOrCreate(
            ['plan' => 'Plan de visitas ' . now()->year, 'anio' => (int) now()->year],
            [
                'fecha_inicio' => now()->startOfYear()->toDateString(),
                'fecha_fin' => now()->endOfYear()->toDateString(),
                'estado' => 'ACTIVO',
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        Patrocinado::query()->each(function (Patrocinado $patrocinado) use ($plan, $motivos, $tecnico) {
            if (Visita::where('patrocinado_id', $patrocinado->id)->exists()) {
                return;
            }

            Visita::create([
                'plan_visita_id' => $plan->id,
                'patrocinado_id' => $patrocinado->id,
                'user_id' => $tecnico->id,
                'motivo_visita_id' => $motivos->first(),
                'fecha_programada' => now()->addDays(7)->toDateString(),
                'created_by' => $tecnico->id,
            ]);
        });
    }
}
