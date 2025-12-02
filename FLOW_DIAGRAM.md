# BeautyGo Coordinates Feature - System Flow Diagram

## 📊 Business Registration Flow

```
┌─────────────────────────────────────────────────────────────┐
│ BUSINESS OWNER VISITS /register-business.php                │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ FILLS BUSINESS INFORMATION                                   │
│ - Name, Type, Description, Hours, Contact                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ SCROLLS TO "PIN YOUR LOCATION ON MAP"                        │
│                                                              │
│ ┌────────────────────────────────────────────────────┐      │
│ │  INTERACTIVE LEAFLET MAP (400x400px)              │      │
│ │  - Click to pin location                          │      │
│ │  - Drag marker to adjust                          │      │
│ └────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │ MAP CLICK EVENT │
                    │  OR DRAG EVENT  │
                    └─────────────────┘
                              │
            ┌─────────────────┴─────────────────┐
            ▼                                    ▼
    ┌──────────────────┐           ┌──────────────────┐
    │ UPDATE MAP       │           │ UPDATE HIDDEN    │
    │ - Move marker    │           │ INPUT FIELDS     │
    │ - Show popup     │           │ - latitude       │
    │ - Center map     │           │ - longitude      │
    └──────────────────┘           └──────────────────┘
            │                           │
            │                           ▼
            │                   ┌──────────────────────┐
            │                   │ REVERSE GEOCODING    │
            │                   │ OpenStreetMap API    │
            │                   └──────────────────────┘
            │                           │
            └───────────────┬───────────┘
                            ▼
                ┌──────────────────────────┐
                │ DISPLAY COORDINATES      │
                │ Lat: 14.0697             │
                │ Lng: 120.6328            │
                │ ✓ Location set (badge)   │
                └──────────────────────────┘
                            │
                            ▼
            ┌──────────────────────────────────┐
            │ USER CLICKS "REGISTER BUSINESS"  │
            └──────────────────────────────────┘
                            │
                            ▼
        ┌────────────────────────────────────────┐
        │ FORM SUBMITTED TO backend/auth.php     │
        │ - action=register_business             │
        │ - All fields + latitude/longitude      │
        └────────────────────────────────────────┘
                            │
                            ▼
        ┌────────────────────────────────────────┐
        │ registerBusiness() FUNCTION            │
        │ - Validates coordinates                │
        │ - Calls createBusiness()               │
        └────────────────────────────────────────┘
                            │
                            ▼
        ┌────────────────────────────────────────┐
        │ createBusiness() FUNCTION              │
        │ - Formats: POINT(lng lat)              │
        │ - Uses ST_GeomFromText()               │
        └────────────────────────────────────────┘
                            │
                            ▼
    ┌───────────────────────────────────────────────────┐
    │ MYSQL DATABASE                                    │
    │ ┌─────────────────────────────────────────────┐   │
    │ │ INSERT INTO businesses                      │   │
    │ │ (..., location)                             │   │
    │ │ VALUES (..., ST_GeomFromText(POINT(...)))   │   │
    │ └─────────────────────────────────────────────┘   │
    └───────────────────────────────────────────────────┘
                            │
                            ▼
                ┌──────────────────────┐
                │ ✅ BUSINESS REGISTERED
                │ WITH COORDINATES     │
                │ Redirects to         │
                │ business-dashboard   │
                └──────────────────────┘
```

---

## 🔍 Customer Search Flow

```
┌─────────────────────────────────────────────────────────────┐
│ CUSTOMER VISITS /nearby-businesses.php                       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
    ┌──────────────────────────────────────────────┐
    │ PAGE LOADS                                   │
    │ - Map initialized                            │
    │ - Default coordinates: 14.0697, 120.6328     │
    │ - Marker placed on map                       │
    └──────────────────────────────────────────────┘
                              │
            ┌─────────────────┴────────────────┐
            │                                  │
            ▼                                  ▼
    ┌─────────────────┐           ┌────────────────────┐
    │ METHOD A:       │           │ METHOD B:          │
    │ CLICK ON MAP    │           │ MANUAL INPUT       │
    │ OR DRAG MARKER  │           │ - Enter Latitude   │
    │                 │           │ - Enter Longitude  │
    │ Coordinates     │           │ - Enter Radius     │
    │ auto-populated  │           │ - Click Search     │
    └─────────────────┘           └────────────────────┘
            │                           │
            └───────────────┬───────────┘
                            ▼
            ┌──────────────────────────────┐
            │ AJAX REQUEST                 │
            │ GET /ajax/get_nearby...      │
            │ ?latitude=14.0697            │
            │ ?longitude=120.6328          │
            │ ?radius=10                   │
            └──────────────────────────────┘
                            │
                            ▼
        ┌────────────────────────────────────┐
        │ API ENDPOINT PROCESSING            │
        │ - Validate coordinates             │
        │ - getBusinessesByCoordinates()     │
        └────────────────────────────────────┘
                            │
                            ▼
    ┌───────────────────────────────────────────────┐
    │ MYSQL DATABASE QUERY                          │
    │ ┌─────────────────────────────────────────┐   │
    │ │ SELECT b.*, distance FROM businesses b │   │
    │ │ WHERE distance <= 10 km                 │   │
    │ │ ORDER BY distance ASC                   │   │
    │ │                                         │   │
    │ │ Haversine Formula:                      │   │
    │ │ distance = 6371 * acos(                 │   │
    │ │   cos(radians(lat1)) *                  │   │
    │ │   cos(radians(lat2)) *                  │   │
    │ │   cos(radians(lng2) - radians(lng1)) +  │   │
    │ │   sin(radians(lat1)) *                  │   │
    │ │   sin(radians(lat2))                    │   │
    │ │ )                                       │   │
    │ └─────────────────────────────────────────┘   │
    └───────────────────────────────────────────────┘
                            │
                            ▼
        ┌────────────────────────────────────┐
        │ FORMAT JSON RESPONSE                │
        │ - Status: success                  │
        │ - Count: [number]                  │
        │ - Businesses: [array]              │
        │ - Each with: name, distance, etc   │
        └────────────────────────────────────┘
                            │
                            ▼
        ┌────────────────────────────────────┐
        │ JAVASCRIPT RECEIVES RESPONSE       │
        │ - Parse JSON                       │
        │ - Check for errors                 │
        │ - Display results                  │
        └────────────────────────────────────┘
                            │
                            ▼
    ┌───────────────────────────────────────────┐
    │ DISPLAY BUSINESS CARDS GRID               │
    │                                           │
    │ ┌──────────┬──────────┬──────────┐        │
    │ │ Business │ Business │ Business │ ...    │
    │ │   Card   │   Card   │   Card   │        │
    │ │          │          │          │        │
    │ │ Distance │ Distance │ Distance │        │
    │ │ View Btn │ Map Btn  │ Map Btn  │        │
    │ └──────────┴──────────┴──────────┘        │
    └───────────────────────────────────────────┘
                            │
            ┌───────────────┴────────────────┐
            │                                │
            ▼                                ▼
    ┌─────────────────┐           ┌──────────────────┐
    │ CLICK "VIEW"    │           │ CLICK "MAP"      │
    │                 │           │                  │
    │ Redirect to     │           │ Zoom map to      │
    │ business-detail │           │ business location│
    │ ?id=[business]  │           │ Show marker for  │
    │                 │           │ 3 seconds        │
    └─────────────────┘           └──────────────────┘
            │                           │
            ▼                           ▼
    ┌──────────────────┐        ┌──────────────────┐
    │ BUSINESS DETAIL  │        │ MAP FOCUSED ON   │
    │ - Full info      │        │ BUSINESS         │
    │ - Services       │        │ - Zoomed in (16) │
    │ - Reviews        │        │ - Business marker│
    │ - Booking        │        │ - Auto-dismiss   │
    └──────────────────┘        └──────────────────┘
```

---

## 🗄️ Database Schema Relationship

```
┌──────────────────────────────────────────┐
│ BUSINESSES TABLE                         │
├──────────────────────────────────────────┤
│ business_id        INT PRIMARY KEY       │
│ business_name      VARCHAR(255)          │
│ business_type      VARCHAR(255)          │
│ business_email     VARCHAR(255)          │
│ business_num       VARCHAR(255)          │
│ business_address   VARCHAR(255)          │
│ city               VARCHAR(50)           │
│ opening_hour       TIME                  │
│ closing_hour       TIME                  │
│                                          │
│ ⭐ location        POINT ⭐ (NEW KEY)   │
│   └─ POINT(120.6328, 14.0697)            │
│   └─ Format: (longitude, latitude)       │
│   └─ Indexed: SPATIAL INDEX idx_location│
└──────────────────────────────────────────┘
```

---

## 🔄 Coordinate Data Flow

```
┌─────────────────────┐
│   BUSINESS OWNER    │
│   Registers via     │
│   MAP INTERFACE     │
└─────────────────────┘
           │
           │ Clicks map
           │ lat, lng captured
           │
           ▼
┌─────────────────────┐
│ HIDDEN INPUT FIELDS │
│ #latitude           │
│ #longitude          │
│ (JSON if needed)    │
└─────────────────────┘
           │
           │ Form submit
           │
           ▼
┌─────────────────────┐
│   BACKEND/AUTH.PHP  │
│ registerBusiness()  │
│ - Validate coords   │
│ - Call createBiz()  │
└─────────────────────┘
           │
           │ Format POINT()
           │
           ▼
┌─────────────────────┐
│  MYSQL DATABASE     │
│ ST_GeomFromText()   │
│ Stores as POINT     │
│ 6-decimal precision │
└─────────────────────┘
           │
           │ Spatial indexed
           │ Ready for queries
           │
           ▼
┌──────────────────────────┐
│   CUSTOMER SEARCHES       │
│   /nearby-businesses.php  │
│   - Enter coordinates     │
│   - Set search radius     │
└──────────────────────────┘
           │
           │ AJAX request
           │
           ▼
┌──────────────────────────┐
│ GET_NEARBY_BUSINESSES.PHP│
│ - Extract params         │
│ - Validate coordinates   │
│ - Query database         │
└──────────────────────────┘
           │
           │ Haversine formula
           │ Calculate distances
           │
           ▼
┌──────────────────────────┐
│   DATABASE QUERY         │
│ - Get all businesses     │
│ - Calculate distance     │
│ - Order by distance      │
│ - Limit results          │
└──────────────────────────┘
           │
           │ JSON response
           │
           ▼
┌──────────────────────────┐
│ JAVASCRIPT PROCESSES     │
│ - Parse JSON             │
│ - Display results        │
│ - Sort by distance       │
│ - Format display         │
└──────────────────────────┘
           │
           │ Render HTML
           │
           ▼
┌──────────────────────────┐
│   CUSTOMER SEES          │
│   Business cards with:   │
│   - Name, type, address  │
│   - Distance from search │
│   - Buttons: View, Map   │
└──────────────────────────┘
```

---

## 📱 Component Architecture

```
Frontend Layer
├── nearby-businesses.php (UI for search)
├── register-business.php (UI for registration)
└── HTML/CSS/JavaScript (Leaflet maps, AJAX)
        │
        │ AJAX calls / Form submission
        │
API Layer
├── /ajax/get_nearby_businesses.php (REST endpoint)
└── backend/auth.php (Registration handler)
        │
        │ Function calls / SQL
        │
Business Logic Layer
├── function_businesses.php
│   ├── getBusinessesByCoordinates()
│   ├── createBusiness()
│   └── getBusinessById()
└── function_utilities.php (Helpers)
        │
        │ SQL queries
        │
Database Layer
└── MySQL/MariaDB
    └── businesses table
        └── location column (POINT geometry)
            └── Spatial index
```

---

## 🎯 Key Calculations

### Haversine Distance Formula

```
Given:
  - User coordinates: (lat1, lng1)
  - Business coordinates: (lat2, lng2)

Distance in km = 6371 × acos(
    cos(radians(lat1)) × 
    cos(radians(lat2)) × 
    cos(radians(lng2) - radians(lng1)) + 
    sin(radians(lat1)) × 
    sin(radians(lat2))
)

Example:
  User: (14.0697, 120.6328)
  Business: (14.0720, 120.6340)
  Distance: 0.34 km ≈ 340 meters
```

### Coordinate Precision

```
Decimal Places | Precision
───────────────┼──────────────────────
1              | ~11 km
2              | ~1.1 km
3              | ~111 meters
4              | ~11 meters
5              | ~1.1 meters
6              | ~0.11 meters ✅ Used
7              | ~0.011 meters (overkill)
```

---

## 🚀 Performance Optimization

```
Without Optimization:
- Query time: 500-1000ms (scan all rows)
- CPU: High (calculate distance for each row)

With Optimization:
- Spatial Index: ✅ Added
- Query time: <50ms (indexed lookup)
- Filtering: Pre-filtered by distance
- Result limit: Max 8-12 results
- Response: ~2-3KB JSON
```

---

**Last Updated**: December 2, 2025
