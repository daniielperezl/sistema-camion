<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Sistema de Rutas</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar */
        .sidebar { width: 250px; background-color: #2c3e50; color: white; display: flex; flex-direction: column; }
        .sidebar-header { padding: 1rem; background-color: #1a252f; text-align: center; }
        .sidebar-menu { flex: 1; list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { border-bottom: 1px solid #34495e; }
        .sidebar-menu a { display: block; padding: 1rem; color: #ecf0f1; text-decoration: none; transition: background 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #34495e; }

        /* Main Content */
        .main-content { flex: 1; padding: 20px; overflow-y: auto; background-color: #f4f6f9; }
        .hidden { display: none; }

        /* Dashboard Sections */
        .section-header { margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }

        /* Form Styling */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; max-width: 800px; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .full-width { grid-column: span 2; }

        .btn { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-success { background-color: #28a745; }
        .btn-danger { background-color: #dc3545; }

        /* Balance Colors */
        .balance-positive { color: green; font-weight: bold; }
        .balance-negative { color: red; font-weight: bold; }

        /* Map Container */
        #map { height: 400px; width: 100%; margin-top: 20px; border: 1px solid #ccc; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        table th, table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        table th { background-color: #f8f9fa; }

        /* Day Selector */
        .day-selector { margin-bottom: 20px; }
        .day-btn { padding: 8px 12px; margin-right: 5px; cursor: pointer; background: #e9ecef; border: 1px solid #ced4da; }
        .day-btn.active { background: #007bff; color: white; border-color: #007bff; }

    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">
            <h3>Truck Delivery</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#" onclick="showSection('rutas', this)">Organización de Rutas</a></li>
            <li><a href="#" onclick="showSection('cuentas_dia', this)">Generar Cuentas del Día</a></li>
            <li><a href="#" onclick="showSection('estado_cuentas', this)">Estado de Cuentas</a></li>
            <li><a href="logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <!-- Routes Section -->
        <div id="rutas" class="section hidden">
            <h2 class="section-header">Organización de Rutas</h2>
            <div class="day-selector">
                <button class="day-btn" onclick="loadRoute('Lunes')">Lunes</button>
                <button class="day-btn" onclick="loadRoute('Martes')">Martes</button>
                <button class="day-btn" onclick="loadRoute('Miercoles')">Miercoles</button>
                <button class="day-btn" onclick="loadRoute('Jueves')">Jueves</button>
                <button class="day-btn" onclick="loadRoute('Viernes')">Viernes</button>
                <button class="day-btn" onclick="loadRoute('Sabado')">Sabado</button>
            </div>

            <div id="route-content" class="hidden">
                <h3 id="current-day-title">Ruta del Lunes</h3>

                <div class="form-group">
                     <button class="btn btn-success" onclick="showAddStopForm()">Agregar Nuevo Comercio</button>
                </div>

                <!-- Add Stop Form -->
                <div id="add-stop-form" class="hidden" style="background: #fff; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px;">
                    <h4>Nuevo Comercio</h4>
                    <form id="newStopForm">
                        <div class="form-group">
                            <label>Ciudad</label>
                            <select name="city" required>
                                <option value="">Seleccione una ciudad</option>
                                <option value="Sogamoso">Sogamoso</option>
                                <option value="Duitama">Duitama</option>
                                <option value="Paipa">Paipa</option>
                                <option value="Tunja">Tunja</option>
                                <option value="Motavita">Motavita</option>
                                <option value="Tierra Negra">Tierra Negra</option>
                                <option value="Ventaquemada">Ventaquemada</option>
                                <option value="Vélez">Vélez</option>
                                <option value="Paz de Río">Paz de Río</option>
                                <option value="Belén">Belén</option>
                                <option value="Socha">Socha</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nombre del Establecimiento</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="address" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="phone">
                        </div>
                        <div class="form-group">
                            <label>Notas Adicionales</label>
                            <textarea name="notes"></textarea>
                        </div>
                        <!-- Simple Lat/Lng inputs for now, later could be map click -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Latitud (Opcional)</label>
                                <input type="number" step="any" name="lat" placeholder="Ej: 5.5353">
                            </div>
                            <div class="form-group">
                                <label>Longitud (Opcional)</label>
                                <input type="number" step="any" name="lng" placeholder="Ej: -73.3678">
                            </div>
                        </div>
                        <button type="submit" class="btn">Guardar y Optimizar Ruta</button>
                        <button type="button" class="btn btn-danger" onclick="hideAddStopForm()">Cancelar</button>
                    </form>
                </div>

                <div id="map"></div>

                <table id="stops-table">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Ciudad</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Stops loaded via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Generate Daily Accounts Section -->
        <div id="cuentas_dia" class="section hidden">
            <h2 class="section-header">Generar Cuentas del Día</h2>
            <form id="accountsForm" class="form-grid">
                <div class="form-group">
                    <label>Número de Planilla</label>
                    <input type="text" name="planilla_number" required>
                </div>
                <div class="form-group">
                    <label>Día de Entrega</label>
                    <select name="delivery_day" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miercoles">Miercoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sabado">Sabado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha de Entrega</label>
                    <input type="date" name="delivery_date" required>
                </div>

                <div class="full-width"><hr></div>

                <div class="form-group">
                    <label>Total de Planilla</label>
                    <input type="number" step="0.01" name="total_planilla" id="total_planilla" oninput="calculateAccounts()" required>
                </div>
                <div class="form-group">
                    <label>Total Devoluciones</label>
                    <input type="number" step="0.01" name="total_devoluciones" id="total_devoluciones" oninput="calculateAccounts()" required>
                </div>
                <div class="form-group">
                    <label>Parciales</label>
                    <input type="number" step="0.01" name="parciales" id="parciales" oninput="calculateAccounts()" required>
                </div>

                <div class="form-group">
                    <label>Total a Consignar (Calculado)</label>
                    <input type="number" step="0.01" name="total_consignar" id="total_consignar" readonly style="background: #e9ecef;">
                </div>

                <div class="full-width"><hr></div>

                <div class="form-group">
                    <label>Total Consignado</label>
                    <input type="number" step="0.01" name="total_consignado" id="total_consignado" oninput="calculateAccounts()" required>
                </div>
                <div class="form-group">
                    <label>Total QR</label>
                    <input type="number" step="0.01" name="total_qr" id="total_qr" oninput="calculateAccounts()" required>
                </div>

                <div class="form-group">
                    <label>Total Entrega a Quala (Calculado)</label>
                    <input type="number" step="0.01" name="total_entrega_quala" id="total_entrega_quala" readonly style="background: #e9ecef;">
                </div>

                <div class="full-width"><hr></div>

                <div class="form-group full-width">
                    <label>Total Descuadre / Cuadre</label>
                    <input type="number" step="0.01" name="total_descuadre" id="total_descuadre" readonly style="font-size: 1.2rem;">
                </div>

                <div class="full-width" style="text-align: center; margin-top: 20px;">
                    <button type="button" class="btn btn-success" onclick="confirmSaveAccounts()">Guardar Cuentas del Día</button>
                </div>
            </form>
        </div>

        <!-- Account Status Section -->
        <div id="estado_cuentas" class="section hidden">
            <h2 class="section-header">Estado de Cuentas</h2>
            <div id="weekly-balance-container" style="padding: 15px; background: white; margin-bottom: 20px; text-align: center; border: 1px solid #ddd;">
                <h3>Balance Total de Semana: <span id="weekly-balance">Cargando...</span></h3>
            </div>

            <table id="accounts-history-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Día</th>
                        <th># Planilla</th>
                        <th>Total Consignar</th>
                        <th>Total Entregado</th>
                        <th>Descuadre</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- History loaded via JS -->
                </tbody>
            </table>
        </div>
    </main>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="js/app.js"></script>
    <script>
        // Init logic
        function showSection(id, element) {
            document.querySelectorAll('.section').forEach(el => el.classList.add('hidden'));
            document.getElementById(id).classList.remove('hidden');

            // Handle active menu
            document.querySelectorAll('.sidebar-menu a').forEach(el => el.classList.remove('active'));
            if(element) element.classList.add('active');

            if(id === 'rutas') {
                // Load default day or clear
            } else if (id === 'estado_cuentas') {
                loadAccountStatus();
            }
        }

        // Show default section
        showSection('rutas', document.querySelector('.sidebar-menu a'));
    </script>
</body>
</html>
