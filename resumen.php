<?php
// INICIAMOS LA SESSION y comparamos
session_start();

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    // si no esta logueado, vamos de nuevo a ingreso.html
    header("Location: ingreso.html");
    exit();
}

// rescatamos variables de la SESION
$documento = $_SESSION['documento'];
$nombre_completo = $_SESSION['nombre_completo'] ?? 'Cliente';

// CREDENCIALES BASE 
$host = 'localhost';
$db   = 'mi_banco_db';
$user = 'root'; 
$pass = 'root'; 
$port = 3307; // 3307 porque tengo otra base en 3306

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Error crítico de conexión: " . $conn->connect_error);
}

// 3. LA CONSULTA SQL (JOIN)
// Traemos datos de la tarjeta y sus liquidaciones, filtrando por el DNI del usuario logueado
// El ORDER BY periodo DESC es la clave para que la más nueva quede primera
$sql = "SELECT t.numero_tarjeta, t.banco_emisor, l.periodo, l.fecha_vencimiento, l.total_a_pagar, l.pago_minimo 
        FROM tarjetas t
        INNER JOIN liquidaciones l ON t.num_cuenta = l.num_cuenta
        WHERE t.dni_titular = ?
        ORDER BY l.periodo DESC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $documento);
$stmt->execute();
$resultado = $stmt->get_result();

// 4. AISLAR LA LIQUIDACIÓN ACTUAL
// Al hacer un solo fetch_assoc(), sacamos la primera fila de la caja de resultados
$liquidacion_actual = $resultado->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tarjetas - Panel del Cliente</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

    <header class="bg-[#004691] text-white py-4 shadow-md px-6 flex justify-between items-center">
        <h1 class="text-xl font-semibold">Mis <span class="font-bold">Tarjetas</span></h1>
        <div class="flex items-center gap-4">
            <span class="text-sm">Hola, <strong><?php echo htmlspecialchars($nombre_completo); ?></strong></span>
            <!-- Botón para cerrar sesión (A implementardespués si lo desean) -->
            <a href="cerrar_sesion.php" class="text-xs bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded">Cerrar Sesión</a>
        </div>
    </header>

    <main class="flex-grow p-6 md:p-12 max-w-6xl mx-auto w-full space-y-8">
        
        <?php if ($liquidacion_actual): ?>
            <!-- SECCIÓN: LIQUIDACIÓN ACTUAL DESTACADA -->
            <section>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Estado de Cuenta Actual</h2>
                <div class="bg-white rounded-lg shadow-lg border-l-4 border-[#004691] p-6 flex flex-col md:flex-row justify-between items-center gap-6">
                    
                    <div class="space-y-2 flex-grow">
                        <p class="text-sm text-gray-500 uppercase font-semibold">Tarjeta finalizada en: <span class="text-gray-800"><?php echo substr($liquidacion_actual['numero_tarjeta'], -4); ?></span></p>
                        <p class="text-sm text-gray-500 uppercase font-semibold">Banco Emisor: <span class="text-gray-800"><?php echo $liquidacion_actual['banco_emisor']; ?></span></p>
                        <h3 class="text-3xl font-bold text-[#004691]">Período: <?php echo $liquidacion_actual['periodo']; ?></h3>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 text-right min-w-[250px]">
                        <p class="text-sm text-gray-500 uppercase font-semibold mb-1">Vencimiento: <span class="text-red-600"><?php echo date("d/m/Y", strtotime($liquidacion_actual['fecha_vencimiento'])); ?></span></p>
                        <p class="text-xl text-gray-800">Total a pagar: <strong class="text-2xl">$<?php echo number_format($liquidacion_actual['total_a_pagar'], 2, ',', '.'); ?></strong></p>
                        <p class="text-sm text-gray-500 mt-2">Pago Mínimo: $<?php echo number_format($liquidacion_actual['pago_minimo'], 2, ',', '.'); ?></p>
                    </div>

                </div>
            </section>

            <!-- SECCIÓN: HISTORIAL DE LIQUIDACIONES -->
            <section>
                <h2 class="text-xl font-bold text-gray-800 mb-4">Historial de Resúmenes</h2>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-semibold">
                            <tr>
                                <th class="px-6 py-4">Período</th>
                                <th class="px-6 py-4">Vencimiento</th>
                                <th class="px-6 py-4">Total Pagado</th>
                                <th class="px-6 py-4">Pago Mínimo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php 
                            // Verificamos si quedaron más filas en el resultado para armar el historial
                            if ($resultado->num_rows > 1): 
                                // El while arranca desde el segundo registro porque el primero ya lo sacamos
                                while ($fila = $resultado->fetch_assoc()): 
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo $fila['periodo']; ?></td>
                                    <td class="px-6 py-4"><?php echo date("d/m/Y", strtotime($fila['fecha_vencimiento'])); ?></td>
                                    <td class="px-6 py-4">$<?php echo number_format($fila['total_a_pagar'], 2, ',', '.'); ?></td>
                                    <td class="px-6 py-4 text-gray-500">$<?php echo number_format($fila['pago_minimo'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">No hay liquidaciones anteriores para mostrar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-8 text-center border border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Sin liquidaciones</h3>
                <p class="text-gray-500">Aún no se han emitido resúmenes para tu tarjeta de crédito.</p>
            </div>
        <?php endif; ?>

    </main>

    <footer class="bg-gray-50 text-[10px] text-gray-500 text-center p-4 border-t border-gray-200 mt-auto">
        Portal Oficial de Consultas de Liquidaciones Progra3card.
    </footer>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>