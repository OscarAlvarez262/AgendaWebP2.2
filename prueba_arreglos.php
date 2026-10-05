<?php

$categorias = ['trabajo', 'personal', 'estudio', 'ocio'];
echo $categorias[0] . '<br>';        // ?
echo count($categorias) . '<br>';   // ?

// Asociativo: cada valor tiene una CLAVE con nombre
$evento = ['titulo' => 'Examen de Cálculo', 'fecha' => '2026-10-08'];
echo $evento['titulo'];               // ?