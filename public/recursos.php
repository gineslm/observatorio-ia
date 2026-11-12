<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/sources_page.php';

sources_page('resources.title', 'recursos.php', 'resources.lead',
    fn($s) => ($s['kind'] ?? null) === 'infrastructure');
