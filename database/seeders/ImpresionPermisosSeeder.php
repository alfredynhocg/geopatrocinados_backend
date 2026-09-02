<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImpresionPermisosSeeder extends Seeder
{
    public function run(): void
    {
        $codigos = [
            ['codigo' => 'impresion.imprimir', 'descripcion' => 'Imprimir tickets/comprobantes en la impresora térmica', 'modulo' => 'impresion'],
        ];

        foreach ($codigos as $permiso) {
            DB::table('permisos')->updateOrInsert(['codigo' => $permiso['codigo']], $permiso);
        }

        $permisoIds = DB::table('permisos')
            ->whereIn('codigo', array_column($codigos, 'codigo'))
            ->pluck('id');

        $rolesConAcceso = ['vendedor', 'coordinador'];

        foreach ($rolesConAcceso as $rolNombre) {
            $rolId = DB::table('roles')->where('nombre', $rolNombre)->value('id');
            if (! $rolId) {
                continue;
            }

            foreach ($permisoIds as $permisoId) {
                DB::table('roles_permisos')->updateOrInsert(['rol_id' => $rolId, 'permiso_id' => $permisoId]);
            }
        }

        $this->command?->info('✓ ImpresionPermisosSeeder: permiso impresion.imprimir creado y asignado a vendedor y coordinador.');
    }
}
