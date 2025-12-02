-- BeautyGo Coordinates Feature - SQL Reference & Testing Queries
-- Use these queries to test and verify the coordinate storage implementation
-- Date: December 2, 2025

-- ============================================================================
-- VERIFICATION QUERIES - Run these to confirm everything is working
-- ============================================================================

-- 1. Check if locations column exists and has data
SELECT 
    COUNT(*) as total_businesses,
    COUNT(location) as businesses_with_coords,
    COUNT(*) - COUNT(location) as businesses_without_coords
FROM businesses;

-- 2. View all businesses with their coordinates
SELECT 
    business_id,
    business_name,
    business_type,
    city,
    ST_X(location) AS longitude,
    ST_Y(location) AS latitude,
    CONCAT('POINT(', ST_X(location), ' ', ST_Y(location), ')') AS point_format
FROM businesses
WHERE location IS NOT NULL
ORDER BY business_id;

-- 3. Check coordinate data quality
SELECT 
    business_id,
    business_name,
    ST_X(location) AS longitude,
    ST_Y(location) AS latitude,
    CASE 
        WHEN ST_X(location) BETWEEN -180 AND 180 AND 
             ST_Y(location) BETWEEN -90 AND 90 THEN 'VALID'
        ELSE 'INVALID'
    END AS coordinate_quality
FROM businesses
WHERE location IS NOT NULL;

-- 4. Find businesses with invalid coordinates
SELECT 
    business_id,
    business_name,
    ST_X(location) AS longitude,
    ST_Y(location) AS latitude
FROM businesses
WHERE location IS NOT NULL
    AND (ST_X(location) NOT BETWEEN -180 AND 180
         OR ST_Y(location) NOT BETWEEN -90 AND 90);

-- 5. Get businesses with NULL coordinates (need registration update)
SELECT 
    business_id,
    business_name,
    city,
    business_address
FROM businesses
WHERE location IS NULL;

-- ============================================================================
-- DISTANCE CALCULATION QUERIES - Test the Haversine formula
-- ============================================================================

-- 6. Find businesses within 10km of Nasugbu City Center
-- Reference point: 14.0697, 120.6328
SELECT 
    business_id,
    business_name,
    business_type,
    business_address,
    ST_X(location) AS longitude,
    ST_Y(location) AS latitude,
    ROUND(
        6371 * ACOS(
            COS(RADIANS(14.0697)) * 
            COS(RADIANS(ST_Y(location))) * 
            COS(RADIANS(ST_X(location)) - RADIANS(120.6328)) + 
            SIN(RADIANS(14.0697)) * 
            SIN(RADIANS(ST_Y(location)))
        ), 2
    ) AS distance_km
FROM businesses
WHERE location IS NOT NULL
HAVING distance_km <= 10
ORDER BY distance_km ASC;

-- 7. Find businesses within 15km
SELECT 
    business_id,
    business_name,
    business_type,
    ROUND(
        6371 * ACOS(
            COS(RADIANS(14.0697)) * 
            COS(RADIANS(ST_Y(location))) * 
            COS(RADIANS(ST_X(location)) - RADIANS(120.6328)) + 
            SIN(RADIANS(14.0697)) * 
            SIN(RADIANS(ST_Y(location)))
        ), 2
    ) AS distance_km
FROM businesses
WHERE location IS NOT NULL
HAVING distance_km <= 15
ORDER BY distance_km ASC
LIMIT 10;

-- 8. Find the nearest business to a given point
SELECT 
    business_id,
    business_name,
    business_type,
    city,
    ST_X(location) AS longitude,
    ST_Y(location) AS latitude,
    ROUND(
        6371 * ACOS(
            COS(RADIANS(14.0697)) * 
            COS(RADIANS(ST_Y(location))) * 
            COS(RADIANS(ST_X(location)) - RADIANS(120.6328)) + 
            SIN(RADIANS(14.0697)) * 
            SIN(RADIANS(ST_Y(location)))
        ), 2
    ) AS distance_km
FROM businesses
WHERE location IS NOT NULL
ORDER BY distance_km ASC
LIMIT 1;

-- 9. Distribution of businesses by distance from center
SELECT 
    CASE 
        WHEN distance_km < 1 THEN '< 1 km'
        WHEN distance_km < 5 THEN '1-5 km'
        WHEN distance_km < 10 THEN '5-10 km'
        WHEN distance_km < 20 THEN '10-20 km'
        ELSE '> 20 km'
    END AS distance_range,
    COUNT(*) AS count,
    GROUP_CONCAT(business_name) AS businesses
FROM (
    SELECT 
        business_name,
        ROUND(
            6371 * ACOS(
                COS(RADIANS(14.0697)) * 
                COS(RADIANS(ST_Y(location))) * 
                COS(RADIANS(ST_X(location)) - RADIANS(120.6328)) + 
                SIN(RADIANS(14.0697)) * 
                SIN(RADIANS(ST_Y(location)))
            ), 2
        ) AS distance_km
    FROM businesses
    WHERE location IS NOT NULL
) AS dist_calc
GROUP BY distance_range
ORDER BY MIN(distance_km);

-- ============================================================================
-- PERFORMANCE QUERIES - Check query performance
-- ============================================================================

-- 10. Check if spatial index exists
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_NAME = 'businesses' 
    AND COLUMN_NAME = 'location'
    AND TABLE_SCHEMA = 'beautygo_db2';

-- 11. Create spatial index for performance (if not exists)
-- Run this if the index doesn't exist
CREATE SPATIAL INDEX idx_business_location ON businesses(location);

-- 12. Test query performance (with EXPLAIN)
EXPLAIN SELECT 
    business_id,
    business_name,
    ROUND(
        6371 * ACOS(
            COS(RADIANS(14.0697)) * 
            COS(RADIANS(ST_Y(location))) * 
            COS(RADIANS(ST_X(location)) - RADIANS(120.6328)) + 
            SIN(RADIANS(14.0697)) * 
            SIN(RADIANS(ST_Y(location)))
        ), 2
    ) AS distance_km
FROM businesses
WHERE location IS NOT NULL
HAVING distance_km <= 10
ORDER BY distance_km ASC;

-- ============================================================================
-- COORDINATE UPDATE QUERIES - Fix incorrect coordinates if needed
-- ============================================================================

-- 13. Update a specific business's coordinates
-- EXAMPLE: Update business ID 1 to coordinates (14.0720, 120.6340)
UPDATE businesses 
SET location = POINT(120.6340, 14.0720)
WHERE business_id = 1;

-- 14. Update all NULL coordinates to default (Nasugbu center)
-- WARNING: Only use if businesses don't have real coordinates
-- UPDATE businesses 
-- SET location = POINT(120.6328, 14.0697)
-- WHERE location IS NULL;

-- ============================================================================
-- TESTING THE API ENDPOINT - Sample data for testing
-- ============================================================================

-- 15. Get sample business data for testing API response
SELECT 
    business_id,
    business_name,
    business_type,
    business_desc,
    business_email,
    business_num,
    business_address,
    city,
    TIME_FORMAT(opening_hour, '%H:%i') AS opening_hour,
    TIME_FORMAT(closing_hour, '%H:%i') AS closing_hour,
    ST_X(location) AS longitude,
    ST_Y(location) AS latitude
FROM businesses
WHERE location IS NOT NULL
LIMIT 3;

-- ============================================================================
-- REAL-WORLD SCENARIOS - Test queries for common use cases
-- ============================================================================

-- 16. "Find all Hair Salons within 20km"
SELECT 
    business_id,
    business_name,
    business_address,
    opening_hour,
    closing_hour,
    ROUND(
        6371 * ACOS(
            COS(RADIANS(14.0697)) * 
            COS(RADIANS(ST_Y(location))) * 
            COS(RADIANS(ST_X(location)) - RADIANS(120.6328)) + 
            SIN(RADIANS(14.0697)) * 
            SIN(RADIANS(ST_Y(location)))
        ), 2
    ) AS distance_km
FROM businesses
WHERE business_type = 'Hair Salon'
    AND location IS NOT NULL
HAVING distance_km <= 20
ORDER BY distance_km ASC;

-- 17. "Find all open businesses right now within 10km"
SELECT 
    business_id,
    business_name,
    business_type,
    opening_hour,
    closing_hour,
    ROUND(
        6371 * ACOS(
            COS(RADIANS(14.0697)) * 
            COS(RADIANS(ST_Y(location))) * 
            COS(RADIANS(ST_X(location)) - RADIANS(120.6328)) + 
            SIN(RADIANS(14.0697)) * 
            SIN(RADIANS(ST_Y(location)))
        ), 2
    ) AS distance_km
FROM businesses
WHERE location IS NOT NULL
    AND TIME(NOW()) BETWEEN opening_hour AND closing_hour
HAVING distance_km <= 10
ORDER BY distance_km ASC;

-- 18. "Find top 5 closest businesses of any type"
SELECT 
    business_id,
    business_name,
    business_type,
    city,
    ROUND(
        6371 * ACOS(
            COS(RADIANS(14.0697)) * 
            COS(RADIANS(ST_Y(location))) * 
            COS(RADIANS(ST_X(location)) - RADIANS(120.6328)) + 
            SIN(RADIANS(14.0697)) * 
            SIN(RADIANS(ST_Y(location)))
        ), 2
    ) AS distance_km
FROM businesses
WHERE location IS NOT NULL
ORDER BY distance_km ASC
LIMIT 5;

-- ============================================================================
-- BACKUP & RESTORE - Coordinate data management
-- ============================================================================

-- 19. Backup coordinates to CSV
-- Run in MySQL client:
-- SELECT business_id, business_name, ST_X(location) AS longitude, 
--        ST_Y(location) AS latitude 
-- INTO OUTFILE '/tmp/businesses_coords_backup.csv'
-- FIELDS TERMINATED BY ',' 
-- FROM businesses WHERE location IS NOT NULL;

-- 20. Create a backup table of coordinates
CREATE TABLE businesses_coordinates_backup AS
SELECT 
    business_id,
    business_name,
    ST_X(location) AS longitude,
    ST_Y(location) AS latitude,
    location,
    NOW() AS backup_date
FROM businesses
WHERE location IS NOT NULL;

-- ============================================================================
-- CLEANUP & MAINTENANCE - Database maintenance queries
-- ============================================================================

-- 21. Analyze table for query optimization
ANALYZE TABLE businesses;

-- 22. Check table structure
DESCRIBE businesses;

-- 23. Get table statistics
SELECT 
    table_name,
    engine,
    table_rows,
    avg_row_length,
    data_length,
    index_length
FROM information_schema.tables
WHERE table_schema = 'beautygo_db2' 
    AND table_name = 'businesses';

-- ============================================================================
-- TESTING NOTES
-- ============================================================================
/*
COORDINATE REFERENCE POINTS FOR TESTING:

Nasugbu City Center:
- Latitude: 14.0697
- Longitude: 120.6328
- Location: Nasugbu, Batangas, Philippines

Test Coordinates (within ~5km):
- Brgy 1: 14.0750, 120.6350
- Brgy 2: 14.0650, 120.6300
- Brgy 3: 14.0700, 120.6400

API Testing:
- Endpoint: http://localhost/BeautyGo/ajax/get_nearby_businesses.php
- Test URL: ?latitude=14.0697&longitude=120.6328&radius=15&limit=8

Expected Results:
- Query should return all businesses within 15km
- Results should be sorted by distance (ascending)
- Each result should include distance in km
- All coordinates should be valid numbers
*/

-- ============================================================================
-- TROUBLESHOOTING QUERIES
-- ============================================================================

-- 24. Find all issues with coordinate data
SELECT 
    'NULL coordinates' AS issue_type,
    COUNT(*) AS count,
    GROUP_CONCAT(business_name) AS affected_businesses
FROM businesses
WHERE location IS NULL
UNION ALL
SELECT 
    'Invalid latitude (not between -90 and 90)',
    COUNT(*),
    GROUP_CONCAT(business_name)
FROM businesses
WHERE location IS NOT NULL
    AND (ST_Y(location) < -90 OR ST_Y(location) > 90)
UNION ALL
SELECT 
    'Invalid longitude (not between -180 and 180)',
    COUNT(*),
    GROUP_CONCAT(business_name)
FROM businesses
WHERE location IS NOT NULL
    AND (ST_X(location) < -180 OR ST_X(location) > 180)
UNION ALL
SELECT 
    'Coordinates are all zeros',
    COUNT(*),
    GROUP_CONCAT(business_name)
FROM businesses
WHERE location IS NOT NULL
    AND ST_X(location) = 0 
    AND ST_Y(location) = 0;

-- 25. Generate comprehensive coordinate health report
SELECT 
    COUNT(DISTINCT business_id) AS total_businesses,
    COUNT(DISTINCT CASE WHEN location IS NOT NULL THEN business_id END) AS with_coords,
    COUNT(DISTINCT CASE WHEN location IS NULL THEN business_id END) AS without_coords,
    ROUND(
        COUNT(DISTINCT CASE WHEN location IS NOT NULL THEN business_id END) * 100.0 / 
        COUNT(DISTINCT business_id), 
        2
    ) AS coverage_percent,
    MIN(ST_X(location)) AS min_longitude,
    MAX(ST_X(location)) AS max_longitude,
    MIN(ST_Y(location)) AS min_latitude,
    MAX(ST_Y(location)) AS max_latitude
FROM businesses;

-- ============================================================================
-- END OF SQL REFERENCE
-- ============================================================================
