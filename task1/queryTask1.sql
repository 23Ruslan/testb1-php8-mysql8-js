/*
DROP 
  TABLE IF EXISTS from_file;
-- table structure
CREATE TABLE from_file (
    id 							INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    random_date 				DATE NOT NULL,
    random_lat_string 			VARCHAR(10) NOT NULL,
    random_rus_string 			VARCHAR(10) NOT NULL,
    random_even_number 			INT UNSIGNED NOT NULL,
    random_fractional_number 	DECIMAL(10,8) UNSIGNED NOT NULL
);
-- образец вставки данных:
INSERT from_file VALUES
(NULL, STR_TO_DATE('31.01.2012', '%d.%m.%Y'), 'ZtyOpfgOyt', 'ёуЁщНкКупд', 1000000000, 10.000),
(NULL, STR_TO_DATE('31.01.2012', '%d.%m.%Y'), 'ZtyOpfgOyt', 'ёуЁщНкКупд', 1000000000, 10.00000001),
(NULL, STR_TO_DATE('31.01.2012', '%d.%m.%Y'), 'ZtyOpfgOyt', 'ёуЁщНкКупд', 1000000000, 90.12345678),
(NULL, STR_TO_DATE('31.01.2012', '%d.%m.%Y'), 'ZtyOpfgOyt', 'ёуЁщНкКупд', 1000000000, 90.12345678);
SELECT * FROM from_file;
*/
WITH mediana_cte_preparing AS ( -- use window functions to get the numbering and entries of the total number
SELECT ROW_NUMBER() OVER (ORDER BY random_fractional_number) rn,
    COUNT(random_fractional_number) OVER () cnt,
    random_fractional_number needed_values
    FROM from_file
),
mediana_cte AS (
SELECT rn, cnt, needed_values
    FROM mediana_cte_preparing
    GROUP BY cnt, rn, needed_values -- for odd cnt we return one row, otherwise we return two, because all numbering is greater than 0:
    HAVING rn IN ( 
        CEILING(cnt / 2), IF(
                            cnt % 2 = 0, 
                            cnt/2 + 1, 
                            -1 
                            ) 
        ) 
)
SELECT
SUM(random_even_number) sum, 
CAST((SELECT AVG(mediana_cte.needed_values) FROM mediana_cte) AS DOUBLE) mediana -- if the HAVING condition in mediana_cte matches 1 row, AVG will return it, if 2, then it will return the average
FROM from_file;