CREATE VIEW v_advertisements AS 
SELECT 
    a.no,
    a.business_name,
    a.npwpd,
    a.advertisement_type,
    a.advertisement_content,
    a.business_address,
    a.advertisement_location,
    v.village_name AS village_name,
    d.district_name AS district_name,
    a.length,
    a.width,
    a.viewing_angle,
    a.face,
    a.area,
    a.angle,
    a.contact
FROM advertisements a 
JOIN villages v ON a.village_code = v.village_code
JOIN districts d ON a.district_code = d.district_code;