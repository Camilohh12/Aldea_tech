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

    // Dominio del correo institucional para el registro de alumnos
    // (se aceptan también subdominios, p. ej. alumno@micorreo.upp.edu.mx).
    'dominio_correo' => env('ALDEA_DOMINIO_CORREO', 'upp.edu.mx'),

    // Programas que aparecen en el registro.
    'carreras' => [
        'Ingeniería en Tecnologías de la Información e Innovación Digital',
        'Ingeniería en Redes y Telecomunicaciones',
        'Ingeniería en Software',
    ],

    // Opciones de cuatrimestre en el registro; "Irregular" marca al alumno como irregular.
    'cuatrimestres' => [...array_map('strval', range(1, 10)), 'Irregular'],

];
