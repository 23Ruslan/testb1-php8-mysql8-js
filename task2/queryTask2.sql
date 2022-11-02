DROP TABLE IF EXISTS osv; 
DROP VIEW IF EXISTS summary_osv;
DROP TABLE IF EXISTS osv_class_names; 
CREATE TABLE osv_class_names (
    class_id    INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    class_name  VARCHAR(200) NOT NULL
);
CREATE TABLE osv (
  account                     CHAR(4) NOT NULL PRIMARY KEY,
  account_subgroup            CHAR(2) AS (LEFT(account, 2)), -- calculated column
  account_group               CHAR(1) AS (LEFT(account, 1)), -- calculated column
  start_assets                BIGINT NOT NULL,               -- store integers, but draw a comma upon request (SELECT query)
  start_liabilities           BIGINT NOT NULL,
  current_assets              BIGINT NOT NULL,
  current_liabilities         BIGINT NOT NULL,
  final_assets                BIGINT /*AS (operations for saldo computing column if the formula is known)*/ NOT NULL,
  final_liabilities           BIGINT /*AS (operations for saldo computing column if the formula is known)*/ NOT NULL,
  group_id                    INTEGER UNSIGNED AS (CAST(account_group AS UNSIGNED )), -- UNSIGNED is short for INTEGER UNSIGNED, another spelling in this database does not work
  FOREIGN KEY (group_id)      REFERENCES osv_class_names(class_id) ON DELETE CASCADE  -- when deleting rows from the parent table (osv_class_names), the corresponding rows of the child table (osv) will be automatically deleted
);
CREATE UNIQUE INDEX account  ON osv(account);
CREATE UNIQUE INDEX class_id ON osv_class_names(class_id);
CREATE VIEW summary_osv (account, start_assets, start_liabilities, current_assets, current_liabilities, final_assets, final_liabilities) AS ( -- when changing the main table, automatically changes its total values when a request is made to receive them
        SELECT account, start_assets, start_liabilities, current_assets, current_liabilities, final_assets, final_liabilities
            FROM osv
    UNION ALL
        SELECT account_subgroup, SUM(start_assets), SUM(start_liabilities), SUM(current_assets), SUM(current_liabilities), SUM(final_assets), SUM(final_liabilities)
            FROM osv
        GROUP BY account_subgroup
    UNION ALL
        SELECT account_group, SUM(start_assets), SUM(start_liabilities), SUM(current_assets), SUM(current_liabilities), SUM(final_assets), SUM(final_liabilities)
            FROM osv
        GROUP BY account_group
    UNION ALL
        SELECT 'Balance', SUM(start_assets), SUM(start_liabilities), SUM(current_assets), SUM(current_liabilities), SUM(final_assets), SUM(final_liabilities)
            FROM osv
    UNION ALL
        SELECT class_id, class_name, '', '', '', '' ,'' 
            FROM osv_class_names
    ORDER BY 1 ASC, 2 DESC
);
SELECT * FROM summary_osv;


-- test data:
INSERT osv (account, start_assets, start_liabilities, current_assets, current_liabilities, final_assets, final_liabilities) VALUES
(1033, 16521001749.35, 0.00, 654759955289.74, 3865541431398.25, -3194260474359.15, 0.00),
(1036, 16521001749.35, 0.00, 654759955289.74, 3865541431398.25, -3194260474359.15, 0.00),
(1196, 16521001749.35, 0.00, 654759955289.74, 3865541431398.25, -3194260474359.15, 0.00),
(2034, 16521001749.35, 0.00, 654759955289.74, 3865541431398.25, -3194260474359.15, 0.00),
(2035, 16521001749.35, 0.00, 654759955289.74, 3865541431398.25, -3194260474359.15, 0.00);
SELECT * FROM summary_osv;