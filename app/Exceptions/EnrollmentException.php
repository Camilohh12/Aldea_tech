<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Regla de negocio no cumplida al inscribirse o registrar asistencia.
 * El mensaje se muestra tal cual al alumno.
 */
class EnrollmentException extends RuntimeException
{
    public const CONFLICTO = 'conflicto';
    public const CUPO = 'cupo';
    public const DUPLICADO = 'duplicado';
    public const CERRADA = 'cerrada';
    public const GENERAL = 'general';

    public function __construct(string $message, public readonly string $kind = self::GENERAL)
    {
        parent::__construct($message);
    }

    /** Título corto para la alerta en pantalla. */
    public function title(): string
    {
        return match ($this->kind) {
            self::CONFLICTO => 'Conflicto de horario detectado',
            self::CUPO => 'Cupo agotado',
            self::DUPLICADO => 'Ya estás inscrito',
            self::CERRADA => 'Inscripción cerrada',
            default => 'No se pudo completar la acción',
        };
    }
}
