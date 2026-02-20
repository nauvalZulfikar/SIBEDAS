CREATE VIEW v_tourisms_based_kbli AS
SELECT kbli_title, total_records
FROM (
    SELECT kbli, kbli_title, COUNT(*) AS total_records
    FROM tourisms
    GROUP BY kbli, kbli_title
) AS subquery
ORDER BY total_records DESC;