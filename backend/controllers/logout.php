<?php
session_start();
session_destroy();
header('Location: ../../frontend/HTML/cliente/inicio_de_sesion.php');
exit;
