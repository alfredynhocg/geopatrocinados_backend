<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebServicioSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $servicios = [
            [
                'titulo'            => 'Mentoría en Proyecto de Grado',
                'categoria'         => 'mentoria',
                'descripcion_corta' => 'Acompañamiento personalizado de MENTABIT para estudiantes en la elaboración de su proyecto de grado, tesis o trabajo dirigido.',
                'descripcion'       => 'El equipo de mentores de MENTABIT ofrece sesiones individuales o grupales de asesoramiento metodológico, revisión de avances y preparación para la defensa oral. Ideal para estudiantes de pregrado y postgrado que necesitan orientación en la última etapa de su formación.',
                'icono'             => 'fa-solid fa-graduation-cap',
                'precio_desde'      => 350.00,
                'moneda'            => 'BOB',
                'modalidad'         => 'hibrido',
                'destacado'         => true,
                'orden'             => 1,
                'estado'            => 'publicado',
                'meta_titulo'       => 'Mentoría en Proyecto de Grado | MENTABIT',
                'meta_descripcion'  => 'Acompañamiento personalizado de MENTABIT para tu proyecto de grado, tesis o trabajo dirigido.',
            ],
            [
                'titulo'            => 'Instalación y Configuración de Servidores',
                'categoria'         => 'infraestructura',
                'descripcion_corta' => 'Instalación, configuración y puesta en marcha de servidores físicos o en la nube, a cargo del equipo técnico de MENTABIT.',
                'descripcion'       => 'MENTABIT se encarga de la instalación de sistema operativo, hardening de seguridad, configuración de servicios (web, base de datos, correo), y documentación técnica de la infraestructura entregada para tu institución o empresa.',
                'icono'             => 'fa-solid fa-server',
                'precio_desde'      => 800.00,
                'moneda'            => 'BOB',
                'modalidad'         => 'presencial',
                'destacado'         => true,
                'orden'             => 2,
                'estado'            => 'publicado',
                'meta_titulo'       => 'Instalación y Configuración de Servidores | MENTABIT',
                'meta_descripcion'  => 'Instalación y configuración profesional de servidores físicos o en la nube con MENTABIT.',
            ],
            [
                'titulo'            => 'Desarrollo de Sistemas a Medida',
                'categoria'         => 'desarrollo',
                'descripcion_corta' => 'Diseño y desarrollo de sistemas de información y aplicaciones web de MENTABIT, adaptados a las necesidades de tu organización.',
                'descripcion'       => 'El equipo de desarrollo de MENTABIT realiza el análisis de requerimientos, diseño de arquitectura, desarrollo e implementación de sistemas web y móviles. Incluye acompañamiento post-entrega y capacitación al equipo de usuarios finales.',
                'icono'             => 'fa-solid fa-code',
                'precio_desde'      => null,
                'moneda'            => 'BOB',
                'modalidad'         => 'hibrido',
                'destacado'         => true,
                'orden'             => 3,
                'estado'            => 'publicado',
                'meta_titulo'       => 'Desarrollo de Sistemas a Medida | MENTABIT',
                'meta_descripcion'  => 'Desarrollo de sistemas y aplicaciones web a medida con el equipo de MENTABIT.',
            ],
        ];

        foreach ($servicios as $servicio) {
            $slug = Str::slug($servicio['titulo']);

            DB::table('web_servicio')->updateOrInsert(
                ['slug' => $slug],
                array_merge($servicio, [
                    'slug'       => $slug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
