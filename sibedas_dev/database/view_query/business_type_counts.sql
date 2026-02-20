CREATE VIEW business_type_counts AS
SELECT b.business_type, COUNT(t.id) AS count
FROM tourisms t
JOIN business_type b ON t.business_type_id = b.id
GROUP BY b.business_type;