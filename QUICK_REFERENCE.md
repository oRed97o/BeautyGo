# BeautyGo Coordinates Feature - Quick Reference

## 🎯 What This Does
Enables BeautyGo to store business coordinates during registration and help customers find businesses near them.

## 📍 For Business Owners

### How to Register with Coordinates
1. Go to `/register-business.php`
2. Fill in business information
3. Scroll to "Pin Your Location on Map"
4. **Click on the map** OR **drag the marker** to your business location
5. Watch the address auto-fill and coordinates appear below
6. Click "Register Business" - your coordinates are saved!

### What Gets Saved
- **Latitude** - Your business's north-south position (e.g., 14.0697)
- **Longitude** - Your business's east-west position (e.g., 120.6328)
- **Distance** - Calculated from any customer searching near you

---

## 🔍 For Customers

### How to Find Nearby Businesses
1. Go to `/nearby-businesses.php`
2. Method A: **Click on the map** to set your location
3. Method B: **Enter coordinates manually** and click Search
4. Adjust the **search radius** (10-100 km)
5. View businesses sorted by distance
6. Click **"View"** to see full details
7. Click **"Map"** to zoom to that business

---

## 🗄️ Database Details

### What's Stored
```
Table: businesses
Column: location (POINT geometry)
Format: POINT(longitude, latitude)
Example: POINT(120.6328, 14.0697)
```

### How to Query Manually
```sql
-- See all businesses with their coordinates
SELECT business_name, ST_X(location) as lng, ST_Y(location) as lat 
FROM businesses;

-- Find businesses within 10km of a location
SELECT business_name,
  (6371 * acos(cos(radians(14.0697)) * cos(radians(ST_Y(location))) * 
   cos(radians(ST_X(location)) - radians(120.6328)) + 
   sin(radians(14.0697)) * sin(radians(ST_Y(location))))) AS distance
FROM businesses
HAVING distance <= 10
ORDER BY distance;
```

---

## 🔗 API Endpoints

### Get Nearby Businesses (AJAX)
**URL:** `/ajax/get_nearby_businesses.php`

**Parameters:**
```
latitude=14.0697          (required)
longitude=120.6328        (required)
radius=15                 (optional, default: 10)
limit=20                  (optional, default: 8)
```

**Example:**
```
GET /ajax/get_nearby_businesses.php?latitude=14.0697&longitude=120.6328&radius=15&limit=10
```

**Response:**
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
      "latitude": 14.0697,
      "longitude": 120.6328,
      "distance": "0.0 km",
      ...
    }
  ]
}
```

---

## 🛠️ Integration Examples

### JavaScript - Show Businesses Near User
```javascript
const lat = 14.0697;
const lng = 120.6328;
const radius = 15;

fetch(`/ajax/get_nearby_businesses.php?latitude=${lat}&longitude=${lng}&radius=${radius}`)
    .then(r => r.json())
    .then(data => {
        console.log(`Found ${data.count} businesses within ${radius}km`);
        data.businesses.forEach(b => {
            console.log(`${b.business_name} - ${b.distance}`);
        });
    });
```

### PHP - Get Nearby Businesses Directly
```php
<?php
require_once 'backend/function_businesses.php';

$businesses = getBusinessesByCoordinates(14.0697, 120.6328, 10);

foreach ($businesses as $b) {
    echo $b['business_name'] . ' is ' . $b['distance'] . ' km away';
}
?>
```

---

## 📋 Files Reference

| File | Purpose | Status |
|------|---------|--------|
| `register-business.php` | Business registration with map | Enhanced ✅ |
| `nearby-businesses.php` | Find businesses near you | New ✅ |
| `ajax/get_nearby_businesses.php` | API endpoint | New ✅ |
| `backend/function_businesses.php` | Distance calculation | Existing ✅ |
| `backend/auth.php` | Save coordinates | Existing ✅ |
| `COORDINATES_FEATURE.md` | Full documentation | New ✅ |
| `IMPLEMENTATION_SUMMARY.md` | Implementation details | New ✅ |

---

## ✅ Testing Checklist

### Business Registration
- [ ] Map displays correctly
- [ ] Click on map updates coordinates
- [ ] Drag marker moves coordinates
- [ ] Address auto-fills from reverse geocoding
- [ ] Form submits successfully
- [ ] Coordinates appear in database

### Nearby Businesses Search
- [ ] Map displays on page load
- [ ] Click on map updates search coordinates
- [ ] Drag marker updates coordinates
- [ ] Search button fetches results
- [ ] Results display with correct distances
- [ ] Distance sorted from nearest to farthest
- [ ] "View" button links to business detail
- [ ] "Map" button zooms to business

### Database
- [ ] Run: `SELECT ST_X(location), ST_Y(location) FROM businesses;`
- [ ] Verify coordinates are NOT NULL
- [ ] Verify coordinates are valid (lat -90 to 90, lng -180 to 180)

---

## 🔧 Troubleshooting

| Problem | Solution |
|---------|----------|
| Map not showing | Clear browser cache, check Leaflet CSS/JS loaded |
| Coordinates are 0,0 | Use map click, don't rely on field defaults |
| No businesses found | Increase search radius or verify businesses have coordinates |
| Wrong distances | Ensure coordinates format is longitude, latitude (not reversed) |
| Distance calculation slow | Check spatial index on location column |

---

## 📞 Support

### Documentation
- Full technical docs: See `COORDINATES_FEATURE.md`
- Implementation details: See `IMPLEMENTATION_SUMMARY.md`

### Database Commands
```sql
-- Check coordinates exist
SELECT COUNT(*) FROM businesses WHERE location IS NOT NULL;

-- See business with worst coordinates (if any)
SELECT * FROM businesses WHERE ST_X(location) = 0 OR ST_Y(location) = 0;

-- Create spatial index for performance
CREATE SPATIAL INDEX idx_business_location ON businesses(location);
```

---

## 🚀 Next Steps

1. ✅ Register a test business using the map
2. ✅ Navigate to `/nearby-businesses.php`
3. ✅ Search for businesses near the registered business
4. ✅ Verify distance calculations are correct
5. 📋 Connect nearby-businesses page to main navigation (optional)
6. 📋 Add filters for business type, rating (optional)
7. 📋 Add save favorite nearby businesses feature (optional)

---

**Status**: Ready to Use
**Last Updated**: December 2, 2025
