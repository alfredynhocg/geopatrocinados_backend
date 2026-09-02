<?php

namespace App\Application\Auditoria\Services;

use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Único punto de entrada de escritura, inyectado en los Handlers de escritura
 * de los demás módulos. Ejemplo de uso (referencia, no un archivo real):
 *
 *   public function handle(RevocarDispositivoCommand $command): DispositivoDTO
 *   {
 *       return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
 *           $anterior = $this->repository->findById($command->dispositivoId);
 *           $model = $this->repository->revocar($command->dispositivoId, $command->revokedBy);
 *
 *           $this->auditoria->registrar(
 *               userId: $command->revokedBy,
 *               dispositivoId: $command->dispositivoId,
 *               accion: 'revocar',
 *               modulo: 'Dispositivos',
 *               tipoEntidad: 'dispositivo',
 *               entidadId: $command->dispositivoId,
 *               valoresAnteriores: ['estado' => $anterior->estado],
 *               valoresNuevos: ['estado' => 'REVOCADO'],
 *           );
 *
 *           return DispositivoDTO::fromModel($model);
 *       });
 *   }
 */
class AuditoriaService
{
    public function __construct(private readonly RegistroAuditoriaRepositoryInterface $repository) {}

    public function registrar(
        ?string $userId,
        ?string $dispositivoId,
        string $accion,
        string $modulo,
        ?string $tipoEntidad,
        ?string $entidadId,
        ?array $valoresAnteriores,
        ?array $valoresNuevos,
    ): void {
        try {
            $this->repository->create([
                'user_id'             => $userId,
                'dispositivo_id'      => $dispositivoId,
                'accion'              => $accion,
                'modulo'              => $modulo,
                'tipo_entidad'        => $tipoEntidad,
                'entidad_id'          => $entidadId,
                'valores_anteriores'  => $valoresAnteriores,
                'valores_nuevos'      => $valoresNuevos,
                'direccion_ip'        => request()?->ip(),
                'user_agent'          => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Deliberado: un fallo al auditar no debe abortar la operación de
            // negocio que lo disparó. Si negocio exige auditoría obligatoria,
            // cambiar este catch por un re-throw.
            Log::error('AuditoriaService: fallo al registrar auditoría', [
                'accion'  => $accion,
                'modulo'  => $modulo,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
