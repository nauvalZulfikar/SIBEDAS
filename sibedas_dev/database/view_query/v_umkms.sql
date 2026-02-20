CREATE VIEW v_umkms AS 
SELECT 
	u.business_address,
	u.business_contact,
	u.business_desc,
	bf.business_form,
	u.business_id_number,
	u.business_name,
	bs.business_scale,
	u.business_type,
	u.created_at,
	d.district_name,
	u.land_area,
	u.number_of_employee,
	u.owner_address,
	u.owner_contact,
	u.owner_id,
	u.owner_name,
	ps.permit_status,
	u.revenue,
	u.updated_at,
	v.village_name
FROM umkms u 
JOIN business_form bf on u.business_form_id  = bf.id
JOIN permit_status ps on u.permit_status_id = ps.id
JOIn business_scale bs on u.business_scale_id = bs.id
JOIN villages v on u.village_code = v.village_code
JOIN districts d on u.district_code = v.district_code;