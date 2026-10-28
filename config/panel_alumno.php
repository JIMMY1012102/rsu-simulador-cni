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
            'mascota_webp' => 'images/mascotas/cni-mascota-salud.webp',
            'mascota_svg' => 'images/mascotas/mascota-salud.svg',
        ],
        'B' => [
            'icono' => '⚛',
            'descripcion' => 'Experimenta, descubre y fortalece tus conocimientos científicos.',
            'lema' => 'La curiosidad es el comienzo de todo descubrimiento.',
            'mascota_webp' => 'images/mascotas/cni-mascota-basicas.webp',
            'mascota_svg' => 'images/mascotas/mascota-basicas.svg',
        ],
        'C' => [
            'icono' => '⌘',
            'descripcion' => 'Convierte tus conocimientos en soluciones que cambien el mundo.',
            'lema' => 'Cada desafío es una oportunidad para innovar.',
            'mascota_webp' => 'images/mascotas/cni-mascota-ingenierias.webp',
            'mascota_svg' => 'images/mascotas/mascota-ingenierias.svg',
        ],
        'D' => [
            'icono' => '▥',
            'descripcion' => 'Desarrolla tus habilidades para liderar y emprender.',
            'lema' => 'Las grandes decisiones empiezan con una buena preparación.',
            'mascota_webp' => 'images/mascotas/cni-mascota-economicas.webp',
            'mascota_svg' => 'images/mascotas/mascota-economicas.svg',
        ],
        'E' => [
            'icono' => '✦',
            'descripcion' => 'Comprende las ideas, la sociedad y los retos de nuestro tiempo.',
            'lema' => 'El conocimiento abre caminos para transformar la sociedad.',
            'mascota_webp' => 'images/mascotas/cni-mascota-humanidades.webp',
            'mascota_svg' => 'images/mascotas/mascota-humanidades.svg',
        ],
    ],
];
