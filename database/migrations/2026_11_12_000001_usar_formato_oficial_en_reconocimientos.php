<?php

use App\Models\CertificateTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Cambia la plantilla guardada al formato oficial de reconocimientos UPP 2026.
// Solo toca los textos que siguen igual que la primera versión: lo que el
// administrador ya haya editado se respeta.
return new class extends Migration
{
    public function up(): void
    {
        $this->reemplazar(CertificateTemplate::DEFAULTS_ANTERIORES, CertificateTemplate::DEFAULTS);
    }

    public function down(): void
    {
        $this->reemplazar(
            array_intersect_key(CertificateTemplate::DEFAULTS, CertificateTemplate::DEFAULTS_ANTERIORES),
            CertificateTemplate::DEFAULTS_ANTERIORES
        );
    }

    private function reemplazar(array $de, array $a): void
    {
        foreach (DB::table('certificate_templates')->get() as $plantilla) {
            $cambios = [];

            foreach ($de as $campo => $valor) {
                if ($plantilla->{$campo} === $valor) {
                    $cambios[$campo] = $a[$campo];
                }
            }

            if ($cambios) {
                DB::table('certificate_templates')->where('id', $plantilla->id)->update($cambios + ['updated_at' => now()]);
            }
        }
    }
};
