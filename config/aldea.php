<?php

// Datos generales del evento Aldea Tecnológica UPP 2026.
return [

    // Se usan para validar las fechas de las actividades.
    'event' => [
        'start_date' => '2026-11-24',
        'end_date' => '2026-11-26',
    ],

    // Horas de actividades con asistencia necesarias para la liberación
    // curricular (créditos extracurriculares).
    'horas_liberacion' => (int) env('ALDEA_HORAS_LIBERACION', 20),

    // Dominio del correo institucional de los alumnos (registro y «Mi perfil»).
    // Solo se acepta exactamente este dominio: alumno@micorreo.upp.edu.mx.
    'dominio_correo' => env('ALDEA_DOMINIO_CORREO', 'micorreo.upp.edu.mx'),

    // Ligas oficiales que el alumno acepta al registrarse. Mientras estén vacías,
    // el registro muestra el nombre del documento sin enlace.
    'enlaces' => [
        'reglamento' => env('ALDEA_URL_REGLAMENTO'),
        'aviso_privacidad' => env('ALDEA_URL_AVISO_PRIVACIDAD'),
    ],

    // Programas que aparecen en el registro.
    'carreras' => [
        'Ingeniería en Tecnologías de la Información e Innovación Digital',
        'Ingeniería en Redes y Telecomunicaciones',
        'Ingeniería en Software',
    ],

    // Dígitos que debe tener la matrícula de los alumnos.
    'matricula_digitos' => 10,

    // Opciones de cuatrimestre en el registro, el perfil y los filtros.
    // "Irregular" marca al alumno como irregular; "Otro" es para cuando ninguna opción aplica.
    'cuatrimestres' => [...array_map('strval', range(1, 10)), 'Irregular', 'Otro'],

    // Grupos: el alumno escribe solo el número de su grupo y el sistema arma el
    // código prefijo_grupo_cuatrimestre, p. ej. sftw_01_09 = Software, grupo 1, 9°.
    // Los alumnos «Irregular» u «Otro» no capturan grupo: quedan en el de su tutor.
    'prefijos_grupo' => [
        'Ingeniería en Tecnologías de la Información e Innovación Digital' => 'tiid',
        'Ingeniería en Redes y Telecomunicaciones' => 'redte', // provisional, falta confirmar
        'Ingeniería en Software' => 'sftw',
    ],

    // Color institucional para los PDF: la misma escala "aldea" (50 a 900) de las
    // pantallas, calculada desde resources/css/marca.json. 800 = color del escudo.
    'marca' => \App\Support\Marca::escala(),

];
