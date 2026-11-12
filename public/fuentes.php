<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/sources_page.php';

sources_page(
    'Fuentes',
    'fuentes.php',
    'Instituciones, medios y boletines que producen contenido sobre IA. Valoración de calidad epistémica C1–C5 (rúbrica v0).',
    fn($s) => ($s['kind'] ?? 'content_source') !== 'infrastructure'
);
