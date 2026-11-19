INSERT INTO health_centers (name, address_text, province, city)
SELECT 'DEMO UNIQUEMENT — structure fictive',
       'Adresse fictive — Goma',
       'Nord-Kivu (simulation)',
       'Goma (simulation)'
WHERE NOT EXISTS (
    SELECT 1 FROM health_centers
    WHERE name = 'DEMO UNIQUEMENT — structure fictive'
);

INSERT INTO health_center_services (health_center_id, service_name)
SELECT health_centers.id, demo_services.service_name
FROM health_centers
CROSS JOIN (
    SELECT 'Service fictif — démonstration' AS service_name
) AS demo_services
WHERE health_centers.name = 'DEMO UNIQUEMENT — structure fictive'
  AND NOT EXISTS (
      SELECT 1 FROM health_center_services
      WHERE health_center_services.health_center_id = health_centers.id
        AND health_center_services.service_name = demo_services.service_name
  );
