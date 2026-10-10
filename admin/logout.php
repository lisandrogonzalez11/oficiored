<?php
require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();

unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_nombre'],
    $_SESSION['admin_intentos'],
    $_SESSION['admin_bloqueo_hasta']
);

redirigir('login.php');
