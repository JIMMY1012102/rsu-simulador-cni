<?php

/*
 | Una sola plantilla de panel para las cinco áreas de postulación.
 | Los códigos A–E deben coincidir con areas_postulacion.codigo en MariaDB.
 | La carrera se consulta del perfil; nunca se asigna a partir de este mapa.
 */
return [
    'areas' => [
        'A' => [
            'icono' => '✚',
            'descripcion' => 'Cada respuesta te acerca a tu vocación de cuidar vidas.',
            'lema' => 'Aprende hoy para transformar vidas mañana.',
            'mascota_png' => 'images/mascotas/cni-mascota-salud.png',
        ],
        'B' => [
            'icono' => '⚛',
            'descripcion' => 'Experimenta, descubre y fortalece tus conocimientos científicos.',
            'lema' => 'La curiosidad es el comienzo de todo descubrimiento.',
            'mascota_png' => 'images/mascotas/cni-mascota-basicas.png',
        ],
        'C' => [
            'icono' => '⌘',
            'descripcion' => 'Convierte tus conocimientos en soluciones que cambien el mundo.',
            'lema' => 'Cada desafío es una oportunidad para innovar.',
            'mascota_png' => 'images/mascotas/cni-mascota-ingenierias.png',
        ],
        'D' => [
            'icono' => '▥',
            'descripcion' => 'Desarrolla tus habilidades para liderar y emprender.',
            'lema' => 'Las grandes decisiones empiezan con una buena preparación.',
            'mascota_png' => 'images/mascotas/cni-mascota-economicas.png',
        ],
        'E' => [
            'icono' => '✦',
            'descripcion' => 'Comprende las ideas, la sociedad y los retos de nuestro tiempo.',
            'lema' => 'El conocimiento abre caminos para transformar la sociedad.',
            'mascota_png' => 'images/mascotas/cni-mascota-humanidades.png',
        ],
    ],
    // El ID del canal empieza con UC (24 caracteres); se obtiene en «Más información sobre este canal → Compartir canal → Copiar ID del canal».
    'academias' => [
        ['nombre' => 'Grupo Ciencias San Marcos', 'canal_id' => 'UC2t2Wzp4cnLGW31aBWSb9MA', 'url' => 'https://www.youtube.com/@GrupoCienciasSanMarcos'],
        ['nombre' => 'Academia Vonex', 'canal_id' => 'UCcRvqu5Z-8xcYT8rZ05KEXg', 'url' => 'https://www.youtube.com/@AcademiaVonex/videos'],
        ['nombre' => 'Alberto Cruz', 'canal_id' => 'UCfXlr6-4saqmE8U9mtw3WPQ', 'url' => 'https://www.youtube.com/@AlbertoCruz11111', 'descripcion' => 'Videos pregrabados de academias'],
        ['nombre' => 'David ERRE', 'canal_id' => 'UCLvutyuve6_JeMk2fL2QmIw', 'url' => 'https://www.youtube.com/@DAVIDERRE'],
        ['nombre' => 'Física PRE', 'canal_id' => 'UCc4RghPUscsFM5X6exrTZ5w', 'url' => 'https://www.youtube.com/@F%C3%ADsicaPRE'],
        ['nombre' => 'Jonathan Cachimbo', 'canal_id' => 'UCjEwR5G0LtxCSh__51k0Iog', 'url' => 'https://www.youtube.com/@jonathancachimbo'],
        ['nombre' => 'Juan Meneses', 'canal_id' => 'UC9OAoS3avLDNj9vB4T-9xuw', 'url' => 'https://www.youtube.com/@juanmenesesrm'],
    ],
];
