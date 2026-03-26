CREATE VIEW v_tourisms AS 
SELECT 
	t.project_id,
	t.project_type_id,
	t.nib,
	t.business_name,
	t.oss_publication_date,
	t.investment_status_description,
	t.business_form,
	t.project_risk,
	t.project_name,
	t.business_scale,
	t.business_address,
	v.village_name as village_name,
	d.district_name as district_name,
	t.longitude,
	t.latitude,
	t.project_submission_date,
	t.kbli_title,
	t.supervisory_sector,
	t.user_name,
	t.email,
	t.contact,
	t.land_area_in_m2,
	t.investment_amount,
	t.tki
FROM tourisms t
JOIN villages v on t.village_code = v.village_code
JOIN districts d on t.district_code = d.district_code;