// js/app.js

let map = null;
let currentMarkers = [];
let currentDay = null;
let newStopMarker = null;

// Variables for Today's Route
let mapToday = null;
let todayMarkers = [];
let todayStopsData = [];

// Init on Load
document.addEventListener('DOMContentLoaded', () => {
    // Detect Day and Show "Ruta del Día"
    const days = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
    const now = new Date();
    const dayName = days[now.getDay()];

    // Format Date: DD/MM/YYYY
    const dateStr = now.toLocaleDateString('es-ES');
    document.getElementById('today-date-display').textContent = `${dayName} - ${dateStr}`;

    // Initialize View
    // By default, show "ruta_hoy"
    const link = document.querySelector('a[onclick*="ruta_hoy"]');
    if (link) {
        showSection('ruta_hoy', link);
        // Load data if it's a valid delivery day
        if (dayName !== 'Domingo') {
            loadTodayRoute(dayName);
        } else {
            alert('Hoy es Domingo, no hay rutas programadas.');
        }
    }
});

function initMap(mapId = 'map') {
    let m = null;
    if (mapId === 'map') {
        if (!map) {
            map = L.map('map').setView([5.5353, -73.3678], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

            // Edit Map Click Handler
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                const latInput = document.getElementById('lat_input');
                const lngInput = document.getElementById('lng_input');
                if(latInput && lngInput) {
                    latInput.value = lat.toFixed(6);
                    lngInput.value = lng.toFixed(6);
                }
                if (newStopMarker) map.removeLayer(newStopMarker);
                newStopMarker = L.marker([lat, lng], { opacity: 0.7, title: 'Ubicación seleccionada' }).addTo(map);
            });
        }
        m = map;
    } else if (mapId === 'map-today') {
        if (!mapToday) {
            mapToday = L.map('map-today').setView([5.5353, -73.3678], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(mapToday);
        }
        m = mapToday;
    }
    return m;
}

function showSection(id, element) {
    document.querySelectorAll('.section').forEach(el => el.classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');

    document.querySelectorAll('.sidebar-menu a').forEach(el => el.classList.remove('active'));
    if(element) element.classList.add('active');

    if(id === 'rutas') {
        // Only load if day selected, otherwise wait for click
        setTimeout(() => { if(map) map.invalidateSize(); }, 100);
    } else if (id === 'estado_cuentas') {
        loadAccountStatus();
    } else if (id === 'ruta_hoy') {
        setTimeout(() => { if(mapToday) mapToday.invalidateSize(); }, 100);
    }

    // Close sidebar on mobile when a link is clicked
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.remove('active');
        document.querySelector('.mobile-overlay').classList.remove('active');
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// --- TODAY'S ROUTE LOGIC ---

async function loadTodayRoute(day) {
    initMap('map-today');
    try {
        const response = await fetch(`api/routes.php?day=${day}`);
        todayStopsData = await response.json();
        renderTodayStops(todayStopsData);
    } catch (error) {
        console.error('Error loading today route:', error);
    }
}

function renderTodayStops(stops) {
    const tbody = document.querySelector('#today-table tbody');
    tbody.innerHTML = '';

    // Clear markers
    todayMarkers.forEach(m => mapToday.removeLayer(m));
    todayMarkers = [];

    if (stops.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">No hay ruta para hoy.</td></tr>';
        return;
    }

    stops.forEach(stop => {
        // Waze/Google Maps Links
        const wazeUrl = `https://waze.com/ul?ll=${stop.lat},${stop.lng}&navigate=yes`;
        const googleUrl = `https://www.google.com/maps/search/?api=1&query=${stop.lat},${stop.lng}`;

        const navButtons = (stop.lat && stop.lng)
            ? `<a href="${googleUrl}" target="_blank" class="btn btn-primary" style="padding:2px 5px; font-size:12px; margin-right:5px;">Maps</a>
               <a href="${wazeUrl}" target="_blank" class="btn btn-info" style="padding:2px 5px; font-size:12px; background-color:#33ccff; border:none;">Waze</a>`
            : '<span style="color:gray;">Sin Coord.</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${stop.order_index}</td>
            <td>${stop.city || '-'}</td>
            <td><strong>${stop.name}</strong></td>
            <td>${stop.owner || '-'}</td>
            <td>${stop.address}</td>
            <td>${stop.phone || ''}</td>
            <td>${stop.notes || ''}</td>
            <td>${navButtons}</td>
        `;
        tbody.appendChild(tr);

        // Map Marker
        if (stop.lat && stop.lng) {
            const numberedIcon = L.divIcon({
                className: 'number-icon',
                html: stop.order_index,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            const marker = L.marker([stop.lat, stop.lng], { icon: numberedIcon })
                .addTo(mapToday)
                .bindPopup(`<b>${stop.order_index}. ${stop.name}</b><br>${stop.address}<br>${navButtons}`);
            todayMarkers.push(marker);
        }
    });

    if (todayMarkers.length > 0) {
        const group = new L.featureGroup(todayMarkers);
        mapToday.fitBounds(group.getBounds());
    }
}

function searchTodayRoute() {
    const query = document.getElementById('search-today').value.toLowerCase();
    const filtered = todayStopsData.filter(stop =>
        stop.name.toLowerCase().includes(query) ||
        (stop.owner && stop.owner.toLowerCase().includes(query))
    );
    renderTodayStops(filtered);
}

// --- ADMIN ROUTE LOGIC ---

let adminStopsData = [];

function showAddStopForm() {
    document.getElementById('add-stop-form').classList.remove('hidden');
    document.getElementById('newStopForm').reset();
    document.getElementById('stop_id').value = '';
    document.getElementById('form-title').textContent = 'Nuevo Comercio';
    if(newStopMarker) { map.removeLayer(newStopMarker); newStopMarker = null; }
}

function hideAddStopForm() {
    document.getElementById('add-stop-form').classList.add('hidden');
}

async function loadRoute(day) {
    currentDay = day;
    document.getElementById('route-content').classList.remove('hidden');
    document.getElementById('current-day-title').textContent = `Ruta del ${day}`;

    document.querySelectorAll('.day-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent === day) btn.classList.add('active');
    });

    setTimeout(() => {
        initMap('map');
        map.invalidateSize();
    }, 100);

    try {
        const response = await fetch(`api/routes.php?day=${day}`);
        adminStopsData = await response.json();
        renderStops(adminStopsData);
    } catch (error) {
        console.error('Error loading route:', error);
    }
}

function renderStops(stops) {
    const tbody = document.querySelector('#stops-table tbody');
    tbody.innerHTML = '';

    currentMarkers.forEach(m => map.removeLayer(m));
    currentMarkers = [];

    stops.forEach(stop => {
        const tr = document.createElement('tr');
        // Serialize stop object to pass to edit function safely
        const stopData = JSON.stringify(stop).replace(/"/g, '&quot;');

        tr.innerHTML = `
            <td>${stop.order_index}</td>
            <td>${stop.city || '-'}</td>
            <td>${stop.name}</td>
            <td>${stop.owner || '-'}</td>
            <td>${stop.address}</td>
            <td>${stop.phone || ''}</td>
            <td>${stop.notes || ''}</td>
            <td>
                <button class="btn btn-primary" style="padding: 2px 5px; font-size: 12px;" onclick="editStop(${stopData})">Editar</button>
            </td>
        `;
        tbody.appendChild(tr);

        if (stop.lat && stop.lng) {
            const numberedIcon = L.divIcon({
                className: 'number-icon',
                html: stop.order_index,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
            const marker = L.marker([stop.lat, stop.lng], { icon: numberedIcon })
                .addTo(map)
                .bindPopup(`<b>${stop.order_index}. ${stop.name}</b><br>${stop.address}`);
            currentMarkers.push(marker);
        }
    });

    if (currentMarkers.length > 0) {
        const group = new L.featureGroup(currentMarkers);
        map.fitBounds(group.getBounds());
    }
}

function searchAdminRoute() {
    const query = document.getElementById('search-admin').value.toLowerCase();
    const filtered = adminStopsData.filter(stop =>
        stop.name.toLowerCase().includes(query) ||
        (stop.owner && stop.owner.toLowerCase().includes(query))
    );
    renderStops(filtered);
}

function editStop(stop) {
    showAddStopForm();
    document.getElementById('form-title').textContent = 'Editar Comercio';
    document.getElementById('stop_id').value = stop.id;
    document.getElementById('order_index').value = stop.order_index;
    document.getElementById('city_input').value = stop.city || '';
    document.getElementById('name_input').value = stop.name;
    document.getElementById('owner_input').value = stop.owner || '';
    document.getElementById('address_input').value = stop.address;
    document.getElementById('phone_input').value = stop.phone || '';
    document.getElementById('notes_input').value = stop.notes || '';

    if (stop.lat && stop.lng) {
        document.getElementById('lat_input').value = stop.lat;
        document.getElementById('lng_input').value = stop.lng;
        // Show marker
        if (newStopMarker) map.removeLayer(newStopMarker);
        newStopMarker = L.marker([stop.lat, stop.lng], { opacity: 0.7, title: 'Ubicación actual' }).addTo(map);
        map.setView([stop.lat, stop.lng], 15);
    }

    // Scroll to form
    document.getElementById('add-stop-form').scrollIntoView({ behavior: 'smooth' });
}

document.getElementById('newStopForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!currentDay) {
        alert('Por favor seleccione un día primero.');
        return;
    }

    const formData = new FormData(this);
    formData.append('day', currentDay);

    try {
        const response = await fetch('api/routes.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            alert('Guardado exitosamente.');
            this.reset();
            if (newStopMarker) {
                map.removeLayer(newStopMarker);
                newStopMarker = null;
            }
            hideAddStopForm();
            loadRoute(currentDay);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving stop:', error);
    }
});

// Accounts Logic (Unchanged but included)
function calculateAccounts() {
    const val = (id) => parseFloat(document.getElementById(id).value) || 0;
    const totalPlanilla = val('total_planilla');
    const totalDevoluciones = val('total_devoluciones');
    const parciales = val('parciales');
    const totalConsignar = totalPlanilla - (totalDevoluciones + parciales);
    document.getElementById('total_consignar').value = totalConsignar.toFixed(2);
    const totalConsignado = val('total_consignado');
    const totalQr = val('total_qr');
    const totalEntregaQuala = totalConsignado + totalQr;
    document.getElementById('total_entrega_quala').value = totalEntregaQuala.toFixed(2);
    const descuadre = totalEntregaQuala - totalConsignar;
    const descuadreEl = document.getElementById('total_descuadre');
    const formattedDescuadre = (descuadre > 0 ? '+' : '') + descuadre.toFixed(2);
    descuadreEl.value = formattedDescuadre;
    if (descuadre >= 0) {
        descuadreEl.style.color = 'green';
    } else {
        descuadreEl.style.color = 'red';
    }
}
function confirmSaveAccounts() {
    if (confirm("¿Está seguro que desea cerrar las cuentas de este día?")) {
        saveAccounts();
    }
}
async function saveAccounts() {
    const formData = new FormData(document.getElementById('accountsForm'));
    const data = Object.fromEntries(formData.entries());
    try {
        const response = await fetch('api/accounts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            alert('Cuentas guardadas correctamente.');
            document.getElementById('accountsForm').reset();
            document.getElementById('total_descuadre').style.color = 'black';
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving accounts:', error);
    }
}
async function loadAccountStatus() {
    try {
        const response = await fetch('api/accounts.php');
        const data = await response.json();
        const tbody = document.querySelector('#accounts-history-table tbody');
        tbody.innerHTML = '';
        let weeklyBalance = 0;
        data.forEach(acc => {
            const descuadre = parseFloat(acc.total_descuadre);
            weeklyBalance += descuadre;
            const tr = document.createElement('tr');
            const descuadreColor = descuadre >= 0 ? 'green' : 'red';
            const formattedDescuadre = (descuadre > 0 ? '+' : '') + descuadre.toFixed(2);
            tr.innerHTML = `
                <td>${acc.delivery_date}</td>
                <td>${acc.delivery_day}</td>
                <td>${acc.planilla_number}</td>
                <td>${parseFloat(acc.total_consignar).toFixed(2)}</td>
                <td>${parseFloat(acc.total_entrega_quala).toFixed(2)}</td>
                <td style="color: ${descuadreColor}; font-weight: bold;">${formattedDescuadre}</td>
            `;
            tbody.appendChild(tr);
        });
        const balanceEl = document.getElementById('weekly-balance');
        const formattedBalance = (weeklyBalance > 0 ? '+' : '') + weeklyBalance.toFixed(2);
        balanceEl.textContent = formattedBalance;
        balanceEl.className = weeklyBalance >= 0 ? 'balance-positive' : 'balance-negative';
    } catch (error) {
        console.error('Error loading account status:', error);
    }
}
