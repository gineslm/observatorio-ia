<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/sources_page.php';

sources_page('sources.title', 'fuentes.php', 'sources.lead',
    fn($s) => ($s['kind'] ?? 'content_source') !== 'infrastructure');
