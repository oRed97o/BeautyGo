<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';

$pageTitle = 'Nearby Businesses - BeautyGo';
include 'includes/header.php';
?>

<!-- Leaflet CSS for map display -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="css/styles.css">

<style>
    .nearby-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    #mapContainer {
        height: 500px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .location-input-group {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .location-input-group .input-group {
        margin-bottom: 15px;
    }
    
    .location-input-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--color-burgundy);
    }
    
    .location-input-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.95rem;
    }
    
    .location-input-group button {
        background: var(--color-burgundy);
        color: white;
        padding: 10px 30px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .location-input-group button:hover {
        background: var(--color-rose);
        transform: translateY(-2px);
    }
    
    .business-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }
    
    .business-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .business-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }
    
    .business-card-header {
        background: linear-gradient(135deg, #850E35 0%, #c82333 100%);
        color: white;
        padding: 15px;
    }
    
    .business-card-header h4 {
        margin: 0 0 5px 0;
        font-size: 1.1rem;
    }
    
    .business-type {
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
    }
    
    .business-card-body {
        padding: 15px;
    }
    
    .business-info-row {
        display: flex;
        margin-bottom: 12px;
        font-size: 0.9rem;
    }
    
    .business-info-label {
        font-weight: 600;
        width: 100px;
        color: var(--color-burgundy);
    }
    
    .business-info-value {
        flex: 1;
        color: #555;
    }
    
    .distance-badge {
        display: inline-block;
        background: #d4edda;
        color: #155724;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .loading {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .loading-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--color-burgundy);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .no-results {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }
    
    .no-results i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    .business-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .business-actions button {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-view {
        background: var(--color-burgundy);
        color: white;
    }
    
    .btn-view:hover {
        background: var(--color-rose);
    }
    
    .btn-map {
        background: #007bff;
        color: white;
    }
    
    .btn-map:hover {
        background: #0056b3;
    }
</style>

<main>
    <div class="nearby-container">
        <h1 style="color: var(--color-burgundy); margin-bottom: 10px;">
            <i class="bi bi-geo-alt"></i> Find Businesses Near You
        </h1>
        <p style="color: #666; margin-bottom: 30px;">
            Discover beauty salons, spas, and services in your area
        </p>
        
        <!-- Location Input Section -->
        <div class="location-input-group">
            <h4 style="color: var(--color-burgundy); margin-top: 0;">Search by Location</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: flex-end;">
                <div>
                    <label for="latitude">Latitude</label>
                    <input type="number" id="latitude" placeholder="e.g., 14.0697" value="14.0697" step="0.0001">
                </div>
                <div>
                    <label for="longitude">Longitude</label>
                    <input type="number" id="longitude" placeholder="e.g., 120.6328" value="120.6328" step="0.0001">
                </div>
                <div>
                    <label for="radius">Radius (km)</label>
                    <input type="number" id="radius" placeholder="10" value="10" min="1" max="100">
                </div>
                <button type="button" onclick="searchNearby()">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
            <small style="color: #999; margin-top: 10px;">
                <i class="bi bi-info-circle"></i> Or use the map below to pin your location
            </small>
        </div>
        
        <!-- Map Container -->
        <div id="mapContainer"></div>
        
        <!-- Results Section -->
        <div id="resultsSection" style="display: none;">
            <h3 style="color: var(--color-burgundy); margin-bottom: 20px;">
                <i class="bi bi-star"></i> Nearby Businesses
                <span id="resultCount" style="font-size: 0.8rem; color: #999;"></span>
            </h3>
            <div id="businessesContainer" class="business-grid"></div>
        </div>
        
        <div id="loadingSection" class="loading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Finding nearby businesses...</p>
        </div>
        
        <div id="noResultsSection" class="no-results" style="display: none;">
            <i class="bi bi-inbox"></i>
            <h4>No businesses found</h4>
            <p>Try searching with a larger radius or different coordinates</p>
        </div>
    </div>
</main>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map;
let marker;

function initMap() {
    const defaultLat = 14.0697;
    const defaultLng = 120.6328;
    
    const mapContainer = document.getElementById('mapContainer');
    mapContainer.style.height = '500px';
    
    map = L.map('mapContainer').setView([defaultLat, defaultLng], 14);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    const customIcon = L.divIcon({
        html: '<i class="bi bi-geo-alt-fill" style="font-size: 2.5rem; color: #850E35;"></i>',
        className: 'custom-marker',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
    
    marker = L.marker([defaultLat, defaultLng], { 
        icon: customIcon,
        draggable: true 
    }).addTo(map);
    
    marker.bindPopup('Your search location');
    
    map.on('click', function(e) {
        updateSearchLocation(e.latlng.lat, e.latlng.lng);
    });
    
    marker.on('dragend', function(e) {
        const lat = e.target.getLatLng().lat;
        const lng = e.target.getLatLng().lng;
        updateSearchLocation(lat, lng);
    });
    
    setTimeout(function() {
        if (map) map.invalidateSize();
    }, 100);
}

function updateSearchLocation(lat, lng) {
    document.getElementById('latitude').value = lat.toFixed(4);
    document.getElementById('longitude').value = lng.toFixed(4);
    marker.setLatLng([lat, lng]);
    searchNearby();
}

function searchNearby() {
    const latitude = parseFloat(document.getElementById('latitude').value);
    const longitude = parseFloat(document.getElementById('longitude').value);
    const radius = parseInt(document.getElementById('radius').value) || 10;
    
    if (isNaN(latitude) || isNaN(longitude)) {
        alert('Please enter valid coordinates');
        return;
    }
    
    // Show loading state
    document.getElementById('loadingSection').style.display = 'block';
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('noResultsSection').style.display = 'none';
    
    // Update marker on map
    marker.setLatLng([latitude, longitude]);
    map.setView([latitude, longitude], 14);
    
    // Fetch nearby businesses
    fetch(`/ajax/get_nearby_businesses.php?latitude=${latitude}&longitude=${longitude}&radius=${radius}&limit=12`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            document.getElementById('loadingSection').style.display = 'none';
            
            if (data.status === 'success' && data.count > 0) {
                displayBusinesses(data.businesses);
                document.getElementById('resultsSection').style.display = 'block';
                document.getElementById('resultCount').textContent = `(${data.count} found within ${radius}km)`;
            } else {
                document.getElementById('noResultsSection').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('loadingSection').style.display = 'none';
            document.getElementById('noResultsSection').style.display = 'block';
        });
}

function displayBusinesses(businesses) {
    const container = document.getElementById('businessesContainer');
    container.innerHTML = '';
    
    businesses.forEach(business => {
        const card = document.createElement('div');
        card.className = 'business-card';
        card.innerHTML = `
            <div class="business-card-header">
                <h4>${escapeHtml(business.business_name)}</h4>
                <span class="business-type">${escapeHtml(business.business_type)}</span>
            </div>
            <div class="business-card-body">
                <div class="business-info-row">
                    <span class="business-info-label">Distance:</span>
                    <span class="distance-badge">${escapeHtml(business.distance)}</span>
                </div>
                <div class="business-info-row">
                    <span class="business-info-label">Address:</span>
                    <span class="business-info-value">${escapeHtml(business.business_address)}, ${escapeHtml(business.city)}</span>
                </div>
                <div class="business-info-row">
                    <span class="business-info-label">Phone:</span>
                    <span class="business-info-value"><a href="tel:${business.business_num}">${escapeHtml(business.business_num)}</a></span>
                </div>
                <div class="business-info-row">
                    <span class="business-info-label">Hours:</span>
                    <span class="business-info-value">${escapeHtml(business.opening_hour.substring(0, 5))} - ${escapeHtml(business.closing_hour.substring(0, 5))}</span>
                </div>
                <div class="business-info-row" style="margin-bottom: 0;">
                    <span class="business-info-label">Email:</span>
                    <span class="business-info-value"><a href="mailto:${business.business_email}">${escapeHtml(business.business_email)}</a></span>
                </div>
                <div class="business-actions">
                    <button class="btn-view" onclick="viewBusiness(${business.business_id})">
                        <i class="bi bi-eye"></i> View
                    </button>
                    <button class="btn-map" onclick="focusBusinessMap(${business.latitude}, ${business.longitude})">
                        <i class="bi bi-map"></i> Map
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function viewBusiness(businessId) {
    window.location.href = `/business-detail.php?id=${businessId}`;
}

function focusBusinessMap(lat, lng) {
    map.setView([lat, lng], 16);
    const customIcon = L.divIcon({
        html: '<i class="bi bi-shop" style="font-size: 2.5rem; color: #FFD700;"></i>',
        className: 'business-marker',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
    const businessMarker = L.marker([lat, lng], { icon: customIcon });
    businessMarker.addTo(map);
    setTimeout(() => map.removeLayer(businessMarker), 3000);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
</script>

<?php include 'includes/footer.php'; ?>
