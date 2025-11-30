// js/app.js

let map = null;
let currentMarkers = [];
let currentDay = null;

function initMap() {
    if (!map) {
        // Center on Boyacá, Colombia approx.
        map = L.map('map').setView([5.5353, -73.3678], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
    }
}

function showAddStopForm() {
    document.getElementById('add-stop-form').classList.remove('hidden');
}

function hideAddStopForm() {
    document.getElementById('add-stop-form').classList.add('hidden');
}

async function loadRoute(day) {
    currentDay = day;
    document.getElementById('route-content').classList.remove('hidden');
    document.getElementById('current-day-title').textContent = `Ruta del ${day}`;

    // Highlight active day
    document.querySelectorAll('.day-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent === day) btn.classList.add('active');
    });

    // Make sure map is initialized after making div visible
    setTimeout(() => {
        initMap();
        map.invalidateSize();
    }, 100);

    // Fetch stops
    try {
        const response = await fetch(`api/routes.php?day=${day}`);
        const stops = await response.json();

        renderStops(stops);
    } catch (error) {
        console.error('Error loading route:', error);
    }
}

function renderStops(stops) {
    const tbody = document.querySelector('#stops-table tbody');
    tbody.innerHTML = '';

    // Clear map markers
    currentMarkers.forEach(m => map.removeLayer(m));
    currentMarkers = [];

    stops.forEach(stop => {
        // Table row
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${stop.order_index}</td>
            <td>${stop.city || '-'}</td>
            <td>${stop.name}</td>
            <td>${stop.address}</td>
            <td>${stop.phone}</td>
            <td>${stop.notes}</td>
        `;
        tbody.appendChild(tr);

        // Map marker
        if (stop.lat && stop.lng) {
            const marker = L.marker([stop.lat, stop.lng])
                .addTo(map)
                .bindPopup(`<b>${stop.name}</b><br><i>${stop.city || ''}</i><br>${stop.address}`);
            currentMarkers.push(marker);
        }
    });

    if (currentMarkers.length > 0) {
        const group = new L.featureGroup(currentMarkers);
        map.fitBounds(group.getBounds());
    }
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
            body: formData // Send as FormData for easier handling or convert to JSON
        });
        const result = await response.json();

        if (result.success) {
            alert('Comercio agregado y ruta optimizada.');
            this.reset();
            hideAddStopForm();
            loadRoute(currentDay);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving stop:', error);
    }
});

// Accounts Logic

function calculateAccounts() {
    const val = (id) => parseFloat(document.getElementById(id).value) || 0;

    const totalPlanilla = val('total_planilla');
    const totalDevoluciones = val('total_devoluciones');
    const parciales = val('parciales');

    // A El valor de TOTAL DE PLANILLA se le debe restar la suma de los valores de TOTAL DEVOLUCIONES Y PARCIALES y el resultado de esa operacion es el valor de TOTAL A CONSIGNAR .
    const totalConsignar = totalPlanilla - (totalDevoluciones + parciales);
    document.getElementById('total_consignar').value = totalConsignar.toFixed(2);

    const totalConsignado = val('total_consignado');
    const totalQr = val('total_qr');

    // AHORA debe sumar el valor de TOTAL CONSIGNADO y TOTAL QR ese sera el valor de TOTAL ENTREGA A QUALA .
    const totalEntregaQuala = totalConsignado + totalQr;
    document.getElementById('total_entrega_quala').value = totalEntregaQuala.toFixed(2);

    // AHORA: si el valor de total entregado es mayor al valor de total a consignar debe aparecer el valor de total descuadre o descuadre en verde con el simbolo + al principio
    // en cambio si el valor de total entregado es menor a la cantidad de total a consignar debe aparecer en - antes del valor y en color rojo

    // Formula: Delivered - Expected
    const descuadre = totalEntregaQuala - totalConsignar;
    const descuadreEl = document.getElementById('total_descuadre');

    // Format value with sign
    const formattedDescuadre = (descuadre > 0 ? '+' : '') + descuadre.toFixed(2);
    descuadreEl.value = formattedDescuadre;

    // Color Logic: Positive (Surplus) -> Green, Negative (Deficit) -> Red
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
    // Include calculated values as well just in case, or recalculate on server.
    // Server should recalculate for safety.

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
            weeklyBalance += descuadre; // Assuming simple sum for weekly balance

            const tr = document.createElement('tr');

            // Color Logic: Positive (Surplus) -> Green, Negative (Deficit) -> Red
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
