<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

requireMethod('GET');

rows('SELECT 1');

respond(['data' => ['status' => 'ok']]);
