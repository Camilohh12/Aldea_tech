import Alpine from 'alpinejs';

// Campo «Grupo» del registro y de «Mi perfil»: el alumno elige su grupo de la
// lista de la coordinación (TIID_01_02…) o «Irregular» / «Otro». En el registro,
// al elegir un grupo se propone al tutor que lo atiende (tutorPorGrupo).
Alpine.data('grupoAlumno', (grupo, cuatrimestre, tutorPorGrupo) => ({
    grupo,
    cuatrimestre,
    tutorPropuesto: false,

    // «Irregular» y «Otro» (en el grupo o en el cuatrimestre) cuentan en el grupo de su tutor
    get delTutor() {
        return ['Irregular', 'Otro'].includes(this.grupo)
            || (this.grupo === '' && this.cuatrimestre !== '' && !/^[0-9]+$/.test(this.cuatrimestre));
    },

    // Con cuatrimestre «Irregular» u «Otro» el grupo puede quedar vacío
    get grupoObligatorio() {
        return this.cuatrimestre === '' || /^[0-9]+$/.test(this.cuatrimestre);
    },

    proponerTutor() {
        const tutor = tutorPorGrupo[this.grupo];
        const select = document.getElementById('tutor_id');

        this.tutorPropuesto = Boolean(tutor && select);

        if (this.tutorPropuesto) {
            select.value = String(tutor);
        }
    },
}));
