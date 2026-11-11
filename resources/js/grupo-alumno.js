import Alpine from 'alpinejs';

// Campo «Grupo» del registro y de «Mi perfil». El alumno escribe solo el número
// y aquí se muestra el código que se guardará: prefijo_grupo_cuatrimestre
// (sftw_01_09). El servidor lo arma igual en App\Support\Grupo::codigo().
Alpine.data('grupoAlumno', (carrera, cuatrimestre, grupo, prefijos) => ({
    carrera,
    cuatrimestre,
    grupo,

    // «Irregular» y «Otro» no capturan grupo: el alumno queda en el de su tutor.
    // Mientras no elige cuatrimestre el campo sigue activo.
    get delTutor() {
        return this.cuatrimestre !== '' && !/^[0-9]+$/.test(this.cuatrimestre);
    },

    get codigo() {
        const prefijo = prefijos[this.carrera];
        const numero = String(this.grupo ?? '').trim();

        if (!prefijo || !/^[0-9]+$/.test(this.cuatrimestre) || !/^(0?[1-9]|[1-9][0-9])$/.test(numero)) {
            return '';
        }

        const dosDigitos = (valor) => String(parseInt(valor, 10)).padStart(2, '0');

        return `${prefijo}_${dosDigitos(numero)}_${dosDigitos(this.cuatrimestre)}`;
    },
}));
