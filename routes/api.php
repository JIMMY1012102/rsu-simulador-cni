<?php

/*
 * Rutas de la API (prefijo /api).
 * Cada módulo define sus rutas en routes/modules/<modulo>.php y se cargan aquí,
 * para que ninguna rama tenga que editar este archivo.
 */
foreach (glob(__DIR__.'/modules/*.php') as $archivo) {
    require $archivo;
}
