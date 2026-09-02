<?php

namespace Database\Seeders\Patrocinados;

use App\Infrastructure\AccesoPatrocinados\Models\Permiso;
use App\Infrastructure\AccesoPatrocinados\Models\Rol;
use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccesoPatrocinadosSeeder extends Seeder
{
    /** Permisos declarados por los middlewares `permiso-patrocinados:*` en routes/api/patrocinados.php. */
    private const PERMISOS = [
        'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
        'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar',
        'permisos.ver', 'permisos.crear', 'permisos.editar', 'permisos.eliminar',
        'geografia.ver', 'geografia.crear', 'geografia.editar', 'geografia.eliminar',
        'dispositivos.ver', 'dispositivos.editar', 'dispositivos.aprobar', 'dispositivos.revocar',
        'patrocinados.ver', 'patrocinados.crear', 'patrocinados.editar', 'patrocinados.eliminar',
        'visitas.ver', 'visitas.crear', 'visitas.editar', 'visitas.eliminar', 'visitas.revisar',
        'auditoria.ver',
    ];

    private const PERMISOS_TECNICO_CAMPO = [
        'visitas.ver', 'visitas.crear', 'visitas.editar',
        'patrocinados.ver',
        'geografia.ver',
    ];

    private const PERMISOS_SUPERVISOR = [
        'visitas.ver', 'visitas.revisar',
        'patrocinados.ver',
        'dispositivos.ver', 'dispositivos.aprobar', 'dispositivos.revocar',
        'geografia.ver',
        'auditoria.ver',
    ];

    public function run(): void
    {
        $permisos = collect(self::PERMISOS)->mapWithKeys(function (string $nombre) {
            [$modulo, $accion] = explode('.', $nombre, 2);

            $permiso = Permiso::firstOrCreate(
                ['nombre' => $nombre],
                ['modulo' => $modulo, 'accion' => $accion, 'descripcion' => null],
            );

            return [$nombre => $permiso->id];
        });

        $superadmin = Rol::firstOrCreate(
            ['nombre' => 'SUPERADMIN'],
            ['descripcion' => 'Acceso total al módulo Patrocinados.', 'estado' => true],
        );
        $superadmin->permisos()->syncWithoutDetaching($permisos->values()->all());

        $tecnicoCampo = Rol::firstOrCreate(
            ['nombre' => 'TECNICO_CAMPO'],
            ['descripcion' => 'Técnico de campo: crea y edita visitas propias, captura evidencia.', 'estado' => true],
        );
        $tecnicoCampo->permisos()->syncWithoutDetaching(
            $permisos->only(self::PERMISOS_TECNICO_CAMPO)->values()->all()
        );

        $supervisor = Rol::firstOrCreate(
            ['nombre' => 'SUPERVISOR'],
            ['descripcion' => 'Revisa visitas y administra dispositivos.', 'estado' => true],
        );
        $supervisor->permisos()->syncWithoutDetaching(
            $permisos->only(self::PERMISOS_SUPERVISOR)->values()->all()
        );

        // updated_by = null es el único caso legítimo: no existe un usuario previo que registre este alta.
        $admin = Usuario::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'email'         => 'superadmin@patrocinados.local',
                'password_hash' => Hash::make('changeme123'),
                'nombres'       => 'Super',
                'apellidos'     => 'Admin',
                'telefono'      => null,
                'estado'        => 'ACTIVO',
                'updated_by'    => null,
            ],
        );
        $admin->roles()->syncWithoutDetaching([$superadmin->id]);

        // Usuario demo con rol TECNICO_CAMPO — usado por los seeders de Visitas
        // para tener a quién asignar las visitas de prueba.
        $tecnico = Usuario::firstOrCreate(
            ['username' => 'tecnico1'],
            [
                'email'         => 'tecnico1@patrocinados.local',
                'password_hash' => Hash::make('changeme123'),
                'nombres'       => 'Juan',
                'apellidos'     => 'Pérez (técnico demo)',
                'telefono'      => '70099887',
                'estado'        => 'ACTIVO',
                'updated_by'    => $admin->id,
            ],
        );
        $tecnico->roles()->syncWithoutDetaching([$tecnicoCampo->id]);
    }
}
