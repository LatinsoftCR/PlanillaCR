<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CLEANUP DE RUBROS DE PLANILLA (UTF-8)
 */
header('Content-Type: text/plain; charset=utf-8');

require_once '../config/conexion.php';

echo "INICIANDO LIMPIEZA DE DESCRIPCIONES DE RUBROS EN SQL SERVER...\n";
echo "==============================================================\n\n";

try {
    // 1. Mostrar estado actual
    echo "Estado actual en la base de datos:\n";
    $stmt = $pdo->query("SELECT CodRubro, Codigo, Descripcion, Tipo FROM RubroPlanilla");
    $rubros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rubros as $r) {
        echo " - [{$r['Codigo']}] ID {$r['CodRubro']}: '{$r['Descripcion']}' ({$r['Tipo']})\n";
    }
    echo "\n";

    // 2. Corregir descripciones
    echo "Ejecutando actualizaciones de limpieza...\n";
    
    $corrections = [
        'CCSS' => 'Deducción CCSS Obrero',
        'REN' => 'Retención Impuesto Renta',
        'COM' => 'Comisión',
        'BP' => 'Banco Popular Obrero',
        'DED_VAR' => 'Deducciones Varias / Voluntarias'
    ];

    foreach ($corrections as $codigo => $cleanDesc) {
        $stmtUpdate = $pdo->prepare("
            UPDATE RubroPlanilla 
            SET Descripcion = :desc 
            WHERE Codigo = :codigo
        ");
        $stmtUpdate->execute([
            'desc' => $cleanDesc,
            'codigo' => $codigo
        ]);
        echo " -> Rubro [{$codigo}] actualizado a: '{$cleanDesc}'\n";
    }

    echo "\nEstado final de la tabla:\n";
    $stmt = $pdo->query("SELECT CodRubro, Codigo, Descripcion, Tipo FROM RubroPlanilla");
    $rubros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rubros as $r) {
        echo " - [{$r['Codigo']}] ID {$r['CodRubro']}: '{$r['Descripcion']}' ({$r['Tipo']})\n";
    }

    echo "\n==============================================================\n";
    echo "¡LIMPIEZA REALIZADA CON ÉXITO!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
