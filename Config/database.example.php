<?php

// Copier vers database.local.php, exclu de Git. Variables DB_* prioritaires.
return [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'vite_gourmand',
    'DB_USER' => 'vite_gourmand_app',
    'DB_PASSWORD' => 'remplacer-localement',
    // 'DB_SOCKET' => '/chemin/mysql.sock',
    // 'DB_TIMEZONE' => 'Europe/Paris', // nécessite les tables de fuseaux MySQL
];
