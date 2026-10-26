<?php

session_start();
$_SESSION = [];          // vacía los datos
session_destroy();       // borra la sesión del servidor

header('Location: login.php?salio=1');
exit;
