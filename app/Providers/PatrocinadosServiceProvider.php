<?php

namespace App\Providers;

use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;
use App\Domain\Patrocinados\Contracts\HistorialUbicacionRepositoryInterface;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;
use App\Domain\Visitas\Contracts\AsignacionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\CategoriaObservacionRepositoryInterface;
use App\Domain\Visitas\Contracts\FotoVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\HabilitacionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\ObservacionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\PlanVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\UbicacionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use App\Infrastructure\AccesoPatrocinados\Repositories\EloquentPermisoRepository;
use App\Infrastructure\AccesoPatrocinados\Repositories\EloquentRolRepository;
use App\Infrastructure\AccesoPatrocinados\Repositories\EloquentUsuarioRepository;
use App\Infrastructure\Auditoria\Repositories\EloquentRegistroAuditoriaRepository;
use App\Infrastructure\Dispositivos\Repositories\EloquentDispositivoRepository;
use App\Infrastructure\Geografia\Repositories\EloquentComunidadRepository;
use App\Infrastructure\Geografia\Repositories\EloquentDepartamentoRepository;
use App\Infrastructure\Geografia\Repositories\EloquentMunicipioRepository;
use App\Infrastructure\Geografia\Repositories\EloquentUbicacionRepository;
use App\Infrastructure\Patrocinados\Repositories\EloquentEstadoPatrocinadoRepository;
use App\Infrastructure\Patrocinados\Repositories\EloquentHistorialUbicacionRepository;
use App\Infrastructure\Patrocinados\Repositories\EloquentPatrocinadoRepository;
use App\Infrastructure\Patrocinados\Repositories\EloquentTipoParentescoRepository;
use App\Infrastructure\Patrocinados\Repositories\EloquentTutorRepository;
use App\Infrastructure\Sincronizacion\Repositories\EloquentElementoSincronizacionRepository;
use App\Infrastructure\Sincronizacion\Repositories\EloquentLoteSincronizacionRepository;
use App\Infrastructure\Visitas\Repositories\EloquentAsignacionVisitaRepository;
use App\Infrastructure\Visitas\Repositories\EloquentCategoriaObservacionRepository;
use App\Infrastructure\Visitas\Repositories\EloquentFotoVisitaRepository;
use App\Infrastructure\Visitas\Repositories\EloquentHabilitacionVisitaRepository;
use App\Infrastructure\Visitas\Repositories\EloquentMotivoVisitaRepository;
use App\Infrastructure\Visitas\Repositories\EloquentObservacionVisitaRepository;
use App\Infrastructure\Visitas\Repositories\EloquentPlanVisitaRepository;
use App\Infrastructure\Visitas\Repositories\EloquentRevisionVisitaRepository;
use App\Infrastructure\Visitas\Repositories\EloquentUbicacionVisitaRepository;
use App\Infrastructure\Visitas\Repositories\EloquentVisitaRepository;
use Illuminate\Support\ServiceProvider;

class PatrocinadosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Etapa 2 — AccesoPatrocinados
        $this->app->bind(UsuarioRepositoryInterface::class, EloquentUsuarioRepository::class);
        $this->app->bind(RolRepositoryInterface::class, EloquentRolRepository::class);
        $this->app->bind(PermisoRepositoryInterface::class, EloquentPermisoRepository::class);

        // Etapa 3 — Geografia
        $this->app->bind(DepartamentoRepositoryInterface::class, EloquentDepartamentoRepository::class);
        $this->app->bind(MunicipioRepositoryInterface::class, EloquentMunicipioRepository::class);
        $this->app->bind(ComunidadRepositoryInterface::class, EloquentComunidadRepository::class);
        $this->app->bind(UbicacionRepositoryInterface::class, EloquentUbicacionRepository::class);

        // Etapa 4 — Dispositivos
        $this->app->bind(DispositivoRepositoryInterface::class, EloquentDispositivoRepository::class);

        // Etapa 5 — Patrocinados
        $this->app->bind(PatrocinadoRepositoryInterface::class, EloquentPatrocinadoRepository::class);
        $this->app->bind(TutorRepositoryInterface::class, EloquentTutorRepository::class);
        $this->app->bind(EstadoPatrocinadoRepositoryInterface::class, EloquentEstadoPatrocinadoRepository::class);
        $this->app->bind(TipoParentescoRepositoryInterface::class, EloquentTipoParentescoRepository::class);
        $this->app->bind(HistorialUbicacionRepositoryInterface::class, EloquentHistorialUbicacionRepository::class);

        // Etapa 6 — Visitas
        $this->app->bind(MotivoVisitaRepositoryInterface::class, EloquentMotivoVisitaRepository::class);
        $this->app->bind(CategoriaObservacionRepositoryInterface::class, EloquentCategoriaObservacionRepository::class);
        $this->app->bind(PlanVisitaRepositoryInterface::class, EloquentPlanVisitaRepository::class);
        $this->app->bind(VisitaRepositoryInterface::class, EloquentVisitaRepository::class);
        $this->app->bind(AsignacionVisitaRepositoryInterface::class, EloquentAsignacionVisitaRepository::class);
        $this->app->bind(HabilitacionVisitaRepositoryInterface::class, EloquentHabilitacionVisitaRepository::class);
        $this->app->bind(UbicacionVisitaRepositoryInterface::class, EloquentUbicacionVisitaRepository::class);
        $this->app->bind(ObservacionVisitaRepositoryInterface::class, EloquentObservacionVisitaRepository::class);
        $this->app->bind(FotoVisitaRepositoryInterface::class, EloquentFotoVisitaRepository::class);
        $this->app->bind(RevisionVisitaRepositoryInterface::class, EloquentRevisionVisitaRepository::class);

        // Etapa 7 — Sincronizacion
        $this->app->bind(LoteSincronizacionRepositoryInterface::class, EloquentLoteSincronizacionRepository::class);
        $this->app->bind(ElementoSincronizacionRepositoryInterface::class, EloquentElementoSincronizacionRepository::class);
        // SincronizacionAdapterInterface no se bindea aquí: cada adapter concreto (VisitaSyncAdapter, ...)
        // se resuelve directamente por el SincronizacionRouterService cuando exista (ver docs/patrocinados/07-sincronizacion.md).

        // Etapa 8 — Auditoria
        $this->app->bind(RegistroAuditoriaRepositoryInterface::class, EloquentRegistroAuditoriaRepository::class);
    }
}
