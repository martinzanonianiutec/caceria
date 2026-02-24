<?php
// check_env.php
// Script para verificar permisos y configuración del servidor

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE SERVIDOR ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . PHP_OS . "\n";

$base_dir = __DIR__;
$upload_dir = $base_dir . '/uploads';

echo "\n--- CARPETAS ---\n";
echo "Directorio Base: " . $base_dir . "\n";
echo "Directorio Uploads: " . $upload_dir . "\n";

if (file_exists($upload_dir)) {
    echo "[OK] La carpeta 'uploads' existe.\n";

    if (is_writable($upload_dir)) {
        echo "[OK] La carpeta 'uploads' tiene permisos de escritura.\n";
    }
    else {
        echo "[ERROR] La carpeta 'uploads' NO tiene permisos de escritura.\n";
        echo "Permisos actuales: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "\n";
        echo "Intente ejecutar: chmod 755 uploads (o 777 si es necesario)\n";
    }
}
else {
    echo "[ERROR] La carpeta 'uploads' NO existe.\n";
    echo "Intentando crearla...\n";
    if (mkdir($upload_dir, 0755, true)) {
        echo "[OK] Carpeta creada exitosamente.\n";
    }
    else {
        echo "[FALLO] No se pudo crear la carpeta. Verifique permisos del directorio padre.\n";
    }
}

echo "\n--- CONFIGURACIÓN PHP ---\n";
echo "file_uploads: " . ini_get('file_uploads') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";

echo "\n--- PRUEBA DE ESCRITURA ---\n";
$test_file = $upload_dir . '/test_write.txt';
if (file_put_contents($test_file, 'test')) {
    echo "[OK] Se pudo escribir un archivo de prueba en 'uploads'.\n";
    unlink($test_file);
}
else {
    echo "[ERROR] No se pudo escribir en 'uploads'.\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>
