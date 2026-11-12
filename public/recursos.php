<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/sources_page.php';

sources_page(
    'Recursos',
    'recursos.php',
    'Herramientas, bases de datos, repositorios e índices útiles para investigar la IA.',
    fn($s) => ($s['kind'] ?? null) === 'infrastructure'
);
