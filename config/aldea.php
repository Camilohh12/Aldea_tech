<?php

return [
    // Horas de actividades con asistencia necesarias para la liberación
    // curricular (créditos extracurriculares). Confirmar el valor con la coordinación.
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
