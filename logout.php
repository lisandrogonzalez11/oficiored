<?php
// =============================================================
// logout.php — Cierre de sesion. Sin output HTML.
// Proyecto: OficioRed
// =============================================================

require_once 'includes/funciones.php';

iniciar_sesion();

$_SESSION = array();
session_destroy();

header('Location: index.php');
exit;
