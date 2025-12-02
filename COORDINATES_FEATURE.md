# BeautyGo Coordinate Storage & Nearby Businesses Feature

## Overview
This implementation enables BeautyGo to capture and store business coordinates during registration, allowing users to find businesses near them with accuracy.

## Features Implemented

### 1. **Business Registration with Map Coordinates**
- Location: `register-business.php`
- Users pin their business location on an interactive Leaflet map
- Coordinates are automatically captured (latitude & longitude)
- Address is auto-filled using reverse geocoding (OpenStreetMap Nominatim)
- Coordinates are validated and stored in the database

#### Map Features:
- Click anywhere on the map to set location
- Drag the marker to adjust position
- Real-time coordinate display
- Visual feedback when location is set
- Auto-fills street address and city based on map selection

### 2. **Database Storage**
- **Table**: `businesses`
- **Column**: `location` (POINT geometry)
- **Storage Format**: POINT(longitude latitude)
- **Function**: `createBusiness()` in `backend/function_businesses.php`

**Example:**
```sql
POINT(120.6328 14.0697) -- Longitude, Latitude format
```

### 3. **Coordinate Retrieval & Distance Calculation**
- **Function**: `getBusinessesByCoordinates()` in `backend/function_businesses.php`
- Uses Haversine formula to calculate distances between points
- Returns businesses within specified radius (default: 10km)
- Results sorted by distance (nearest first)

**Formula Used:**
```
distance = 6371 * acos(
    cos(radians(user_lat)) * cos(radians(business_lat)) * 
    cos(radians(business_lng) - radians(user_lng)) + 
    sin(radians(user_lat)) * sin(radians(business_lat))
)
```

### 4. **API Endpoint for Nearby Businesses**
- **URL**: `/ajax/get_nearby_businesses.php`
- **Method**: GET
- **Parameters**:
  - `latitude` (required): User's latitude
  - `longitude` (required): User's longitude
  - `radius` (optional): Search radius in kilometers (default: 10)
  - `limit` (optional): Maximum results (default: 8)

**Example Request:**
```
GET /ajax/get_nearby_businesses.php?latitude=14.0697&longitude=120.6328&radius=15&limit=10
```

**Response Format:**
```json
{
  "status": "success",
  "count": 5,
  "radius": 15,
  "businesses": [
    {
      "business_id": 1,
      "business_name": "Glam Beauty Salon",
      "business_type": "Hair Salon",
      "business_desc": "Premier beauty salon offering hair, nails, and makeup services...",
      "business_email": "glam.salon@email.com",
      "business_num": "0968736411",
      "business_address": "Brgy. 1, Nasugbu",
      "city": "Nasugbu",
      "opening_hour": "07:00:00",
      "closing_hour": "20:00:00",
      "latitude": 14.0697,
      "longitude": 120.6328,
      "distance": "0.0 km"
    },
    ...
  ]
}
```

## Technical Details

### Files Modified
1. **register-business.php**
   - Enhanced map UI with better styling
   - Improved coordinate display with status badge
   - Better validation feedback
   - Added map height styling

2. **css/register-business.css**
   - Already contains all necessary styling
   - Map container with shadow effects
   - Coordinate display styling

### Files Created
1. **ajax/get_nearby_businesses.php**
   - New AJAX endpoint for fetching nearby businesses
   - Returns JSON-formatted results
   - Includes distance calculations

### Existing Functions Used
1. **`getBusinessesByCoordinates()`** - Backend function that performs distance calculations
2. **`createBusiness()`** - Stores coordinates as POINT geometry
3. **`ST_GeomFromText()`** - MySQL function to convert text to geometry

## How It Works

### Step 1: Business Registration
1. User navigates to `/register-business.php`
2. Fills in business details
3. Clicks on the map or drags the marker to set location
4. Reverse geocoding auto-fills address
5. Coordinates are captured in hidden input fields:
   - `#latitude`
   - `#longitude`

### Step 2: Coordinate Storage
1. Form is submitted to `backend/auth.php` with action `register_business`
2. `registerBusiness()` function extracts coordinates
3. Calls `createBusiness()` which:
   - Formats coordinates as POINT(longitude latitude)
   - Stores in database using `ST_GeomFromText()`
4. Business is now searchable by location

### Step 3: Finding Nearby Businesses
1. Frontend makes AJAX request to `/ajax/get_nearby_businesses.php`
2. Endpoint calls `getBusinessesByCoordinates()` function
3. Function queries database using Haversine formula
4. Results returned with distance calculations
5. Displayed to user

## Integration Examples

### JavaScript - Fetch Nearby Businesses
```javascript
// Get user's location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        // Fetch nearby businesses
        fetch(`/ajax/get_nearby_businesses.php?latitude=${lat}&longitude=${lng}&radius=15`)
            .then(response => response.json())
            .then(data => {
                console.log('Found ' + data.count + ' businesses nearby');
                displayBusinesses(data.businesses);
            });
    });
}
```

### PHP - Direct Function Call
```php
<?php
require_once 'backend/function_businesses.php';

$userLat = 14.0697;
$userLng = 120.6328;
$radius = 10; // kilometers

$nearbyBusinesses = getBusinessesByCoordinates($userLat, $userLng, $radius);

foreach ($nearbyBusinesses as $business) {
    echo $business['business_name'] . ' - ' . $business['distance'] . ' km away';
}
?>
```

## Data Validation

### Coordinate Validation
- Latitude: -90 to 90 degrees
- Longitude: -180 to 180 degrees
- Both values required (not zero)
- Stored with 6 decimal places precision (~0.11 meters accuracy)

### Distance Calculation
- Based on Haversine formula (great-circle distance)
- Accounts for Earth's curvature
- Accurate for distances > 1 meter
- Results in kilometers

## Testing Checklist

1. **Business Registration**
   - [ ] Pin location on map successfully
   - [ ] Coordinates display correctly
   - [ ] Address auto-fills from map selection
   - [ ] Form submits with coordinates
   - [ ] Coordinates saved in database

2. **Database Storage**
   - [ ] Query: `SELECT business_id, ST_X(location) AS lng, ST_Y(location) AS lat FROM businesses;`
   - [ ] Verify coordinates match selected location

3. **Nearby Businesses API**
   - [ ] Test with sample coordinates
   - [ ] Verify results are within specified radius
   - [ ] Check distance calculations
   - [ ] Test with different radius values

4. **Integration**
   - [ ] Create feature to show businesses near customer's location
   - [ ] Verify map displays all nearby businesses
   - [ ] Test search functionality with different locations

## Database Schema

```sql
-- Businesses table with location column
CREATE TABLE `businesses` (
  `business_id` int(11) NOT NULL,
  `business_name` varchar(255),
  `business_type` varchar(255),
  `business_desc` text,
  `business_num` varchar(255),
  `business_email` varchar(255),
  `business_password` varchar(255),
  `business_address` varchar(255),
  `opening_hour` time NOT NULL,
  `closing_hour` time NOT NULL,
  `city` varchar(50) NOT NULL,
  `location` point DEFAULT NULL  -- POINT(longitude, latitude)
) ENGINE=InnoDB;

-- Create spatial index for better performance
CREATE SPATIAL INDEX idx_location ON businesses(location);
```

## Performance Optimization

- Spatial index on `location` column for faster queries
- Haversine formula is CPU-efficient
- Limits result count (default 8) to reduce payload
- Caches reverse geocoding results to minimize API calls

## Troubleshooting

### Coordinates Not Saving
1. Check if map click is updating hidden inputs
2. Verify coordinates are not zero/null
3. Check database for POINT geometry support (MariaDB 10.1.2+)

### Distance Calculations Incorrect
1. Verify coordinates format: POINT(longitude, latitude) - not reversed
2. Check Haversine formula in function
3. Ensure both coordinates are valid numbers

### Map Not Displaying
1. Verify Leaflet CSS/JS is loaded
2. Check map container has height set
3. Call `map.invalidateSize()` after DOM ready

## Future Enhancements

1. **Clustering** - Group nearby businesses on map
2. **Filters** - Filter by business type, rating, hours
3. **Real-time Updates** - Live location tracking
4. **Favorites by Location** - Save preferred nearby businesses
5. **Route Optimization** - Multiple business routing
6. **Heatmaps** - Visualize business density areas

## Security Considerations

- Coordinates are public (business location is public information)
- Validate input coordinates before database operations
- Use prepared statements (already implemented)
- Rate limit AJAX endpoint for nearby businesses
- Validate JSON responses before processing

---

**Implementation Date**: December 2, 2025
**Status**: Complete and Ready for Testing
**Last Updated**: December 2, 2025
