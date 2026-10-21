<?php
$pageTitle  = 'Paramètres généraux';
$activeMenu = 'settings';
require_once __DIR__ . '/layout.php';

// Redirige vers content.php qui gère les paramètres
redirect('/admin/content.php');
