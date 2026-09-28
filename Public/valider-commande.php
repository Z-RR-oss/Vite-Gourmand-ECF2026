<?php
// Compatibility endpoint: same POST, CSRF, permissions and workflow as every transition.
require_once __DIR__ . '/../Config/database.php';
requireAdminOrEmployee();
requirePost();
$_POST['statut'] = 'accepté';
require __DIR__ . '/changer-statut.php';
