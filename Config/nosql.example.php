<?php

// Copy as Config/nosql.local.php (ignored by Git). Never place credentials in Public/.
return [
    'database_url' => 'https://YOUR-DATABASE-default-rtdb.europe-west1.firebasedatabase.app',
    'service_account_file' => '/absolute/private/path/firebase-service-account.json',
    // Prefer OAuth above. A legacy database secret can be used only when no account file is set.
    'database_secret' => '',
    'path' => 'vite_gourmand/statistics_v1',
    'timeout' => 10,
    'force_ipv4' => false, // Activer seulement si la route IPv6 de l'hébergeur échoue.
];
