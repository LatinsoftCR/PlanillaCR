<?php
require_once __DIR__ . '/../config/conexion.php';
try {
    $pdo->exec("IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('Empresa') AND name = 'Logo') ALTER TABLE Empresa ADD Logo VARCHAR(300) NULL");
    echo "SUCCESS: Columna Logo agregada con éxito o ya existía en la tabla Empresa.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
