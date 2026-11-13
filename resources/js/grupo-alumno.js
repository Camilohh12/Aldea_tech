import Alpine from 'alpinejs';

// Campo «Grupo» del registro y de «Mi perfil»: el alumno elige su grupo de la
// lista de la coordinación (TIID_01_02…) o «Irregular» / «Otro». En el registro,
// el tutor sale solo del grupo (tutorPorGrupo: {código: {id, nombre}}).
Alpine.data('grupoAlumno', (grupo, cuatrimestre, tutorPorGrupo) => ({
    grupo,
    cuatrimestre,

    // Tutor que atiende el grupo elegido; null si hay que elegirlo a mano
    get tutorDelGrupo() {
        return tutorPorGrupo[this.grupo] ?? null;
    },

    // «Irregular» y «Otro» (en el grupo o en el cuatrimestre) cuentan en el grupo de su tutor
    get delTutor() {
        return ['Irregular', 'Otro'].includes(this.grupo)
            || (this.grupo === '' && this.cuatrimestre !== '' && !/^[0-9]+$/.test(this.cuatrimestre));
    },

    // Con cuatrimestre «Irregular» u «Otro» el grupo puede quedar vacío
    get grupoObligatorio() {
        return this.cuatrimestre === '' || /^[0-9]+$/.test(this.cuatrimestre);
    },
}));
