<?php

declare(strict_types=1);

final class DashboardController
{
    public function __construct(private readonly ?PDO $database = null)
    {
    }

    public function parentDashboard(array $user): array
    {
        $database = $this->database();
        $userId = (int) $user['id'];
        $countChildren = $database->prepare('SELECT COUNT(*) FROM child_guardians WHERE user_id = :user_id');
        $countChildren->execute(['user_id' => $userId]);
        $countMeasurements = $database->prepare(
            'SELECT COUNT(DISTINCT gm.id)
             FROM growth_measurements AS gm
             INNER JOIN child_guardians AS cg ON cg.child_id = gm.child_id
             WHERE cg.user_id = :user_id AND cg.consent_status = \'granted\'',
        );
        $countMeasurements->execute(['user_id' => $userId]);
        $countVaccines = $database->prepare(
            'SELECT COUNT(*)
             FROM vaccination_records AS vr
             INNER JOIN child_guardians AS cg ON cg.child_id = vr.child_id
             WHERE cg.user_id = :user_id AND cg.consent_status = \'granted\'
               AND vr.status = \'administered\'',
        );
        $countVaccines->execute(['user_id' => $userId]);
        $childrenQuery = $database->prepare(
            'SELECT c.id, c.first_name, c.last_name, c.date_of_birth, cg.consent_status,
                    (SELECT MAX(gm.measured_at_utc) FROM growth_measurements AS gm
                     WHERE gm.child_id = c.id) AS last_measurement
             FROM children AS c
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id
             WHERE cg.user_id = :user_id
             ORDER BY c.date_of_birth DESC, c.id DESC',
        );
        $childrenQuery->execute(['user_id' => $userId]);
        $notificationsQuery = $database->prepare(
            'SELECT title, message, created_at_utc
             FROM notifications WHERE user_id = :user_id
             ORDER BY created_at_utc DESC LIMIT 5',
        );
        $notificationsQuery->execute(['user_id' => $userId]);
        $vaccinesQuery = $database->prepare(
            'SELECT c.first_name, c.last_name, v.display_name AS vaccine_name, vr.next_dose_date
             FROM vaccination_records AS vr
             INNER JOIN children AS c ON c.id = vr.child_id
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id
             INNER JOIN vaccine_doses AS vd ON vd.id = vr.vaccine_dose_id
             INNER JOIN vaccines AS v ON v.id = vd.vaccine_id
             WHERE cg.user_id = :user_id AND cg.consent_status = \'granted\'
               AND vr.status = \'administered\' AND vr.next_dose_date >= :today
             ORDER BY vr.next_dose_date ASC LIMIT 5',
        );
        $vaccinesQuery->execute([
            'user_id' => $userId,
            'today' => (new DateTimeImmutable('today', new DateTimeZone('Africa/Lubumbashi')))->format('Y-m-d'),
        ]);
        $measurementsQuery = $database->prepare(
                'SELECT c.first_name, c.last_name, gm.measured_at_utc, gm.weight_kg,
                    gm.length_height_cm, gm.length_height_type, gm.muac_cm,
                    CASE WHEN gm.recorded_by_user_id = cg.user_id
                     THEN \'parent\' ELSE \'health_staff\' END AS recorded_by_type
             FROM growth_measurements AS gm
             INNER JOIN children AS c ON c.id = gm.child_id
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id
             WHERE cg.user_id = :user_id AND cg.consent_status = \'granted\'
             ORDER BY gm.measured_at_utc DESC LIMIT 10',
        );
        $measurementsQuery->execute(['user_id' => $userId]);
        $medicalRecordsQuery = $database->prepare(
            'SELECT c.first_name, c.last_name, mr.blood_group, mr.allergies,
                    mr.medical_history, mr.notes
             FROM medical_records AS mr
             INNER JOIN children AS c ON c.id = mr.child_id
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id
             WHERE cg.user_id = :user_id AND cg.consent_status = \'granted\'
             ORDER BY c.first_name, c.last_name',
        );
        $medicalRecordsQuery->execute(['user_id' => $userId]);
        $nutritionQuery = $database->prepare(
            'SELECT c.first_name, c.last_name, nr.record_date, nr.nutritional_status,
                    nr.feeding_type, nr.recommendations
             FROM nutrition_records AS nr
             INNER JOIN children AS c ON c.id = nr.child_id
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id
             WHERE cg.user_id = :user_id AND cg.consent_status = \'granted\'
             ORDER BY nr.record_date DESC, nr.id DESC LIMIT 10',
        );
        $nutritionQuery->execute(['user_id' => $userId]);
        $alertsQuery = $database->prepare(
            'SELECT c.first_name, c.last_name, ha.alert_type, ha.severity,
                    ha.description, ha.created_at_utc
             FROM health_alerts AS ha
             INNER JOIN children AS c ON c.id = ha.child_id
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id
             WHERE cg.user_id = :user_id AND cg.consent_status = \'granted\'
               AND ha.status IN (\'pending_review\', \'under_review\', \'referred\')
             ORDER BY FIELD(ha.severity, \'critical\', \'high\', \'medium\', \'low\'),
                      ha.created_at_utc DESC LIMIT 10',
        );
        $alertsQuery->execute(['user_id' => $userId]);
        $appointmentRequestsQuery = $database->prepare(
                'SELECT ar.request_type, ar.preferred_date, ar.status, ar.scheduled_at_utc,
                    ar.created_at_utc,
                    c.first_name, c.last_name, hc.name AS center_name
             FROM appointment_requests AS ar
             INNER JOIN children AS c ON c.id = ar.child_id
             INNER JOIN health_centers AS hc ON hc.id = ar.health_center_id
             WHERE ar.parent_user_id = :parent_id
             ORDER BY ar.created_at_utc DESC LIMIT 10',
        );
        $appointmentRequestsQuery->execute(['parent_id' => $userId]);
        $centers = $database->query(
            'SELECT id, name, address_text, city, province, phone_e164
             FROM health_centers
             WHERE name NOT LIKE \'DEMO UNIQUEMENT%\'
             ORDER BY name LIMIT 50',
        );

        return [
            'displayName' => (string) $user['display_name'],
            'stats' => [
                ['label' => 'Enfants enregistrés', 'value' => (int) $countChildren->fetchColumn()],
                ['label' => 'Mesures consultables', 'value' => (int) $countMeasurements->fetchColumn()],
                ['label' => 'Vaccinations enregistrées', 'value' => (int) $countVaccines->fetchColumn()],
            ],
            'children' => $childrenQuery->fetchAll(),
            'measurements' => $measurementsQuery->fetchAll(),
            'medicalRecords' => $medicalRecordsQuery->fetchAll(),
            'nutrition' => $nutritionQuery->fetchAll(),
            'alerts' => $alertsQuery->fetchAll(),
            'appointmentRequests' => $appointmentRequestsQuery->fetchAll(),
            'notifications' => $notificationsQuery->fetchAll(),
            'appointments' => $vaccinesQuery->fetchAll(),
            'centers' => $centers->fetchAll(),
        ];
    }

    public function healthWorkerDashboard(array $user): array
    {
        $database = $this->database();
        $userId = (int) $user['id'];
        $eligibleChildren = 'EXISTS (
            SELECT 1 FROM child_guardians AS cg
            WHERE cg.child_id = c.id AND cg.consent_status = \'granted\'
        )';
        $countChildren = $database->prepare(
            'SELECT COUNT(DISTINCT c.id) FROM children AS c
             WHERE ' . $eligibleChildren . '
               AND (c.created_by_user_id = :created_by
                    OR EXISTS (SELECT 1 FROM growth_measurements AS gm
                               WHERE gm.child_id = c.id AND gm.recorded_by_user_id = :growth_by)
                    OR EXISTS (SELECT 1 FROM nutrition_records AS nr
                               WHERE nr.child_id = c.id AND nr.health_worker_id = :nutrition_by)
                    OR EXISTS (SELECT 1 FROM community_visits AS cv
                               WHERE cv.child_id = c.id AND cv.agent_user_id = :visit_by)
                    OR EXISTS (SELECT 1 FROM health_referrals AS hr
                               WHERE hr.child_id = c.id AND hr.agent_user_id = :referral_by))',
        );
        $countChildren->execute([
            'created_by' => $userId,
            'growth_by' => $userId,
            'nutrition_by' => $userId,
            'visit_by' => $userId,
            'referral_by' => $userId,
        ]);
        $countMeasurements = $database->prepare(
            'SELECT COUNT(DISTINCT gm.id)
             FROM growth_measurements AS gm
             INNER JOIN child_guardians AS cg ON cg.child_id = gm.child_id
             WHERE gm.recorded_by_user_id = :user_id
               AND cg.consent_status = \'granted\'',
        );
        $countMeasurements->execute(['user_id' => $userId]);
        $countAlerts = $database->prepare(
            'SELECT COUNT(*) FROM health_alerts AS ha
             INNER JOIN children AS c ON c.id = ha.child_id
             WHERE ha.status IN (\'pending_review\', \'under_review\')
               AND ha.created_by_user_id = :user_id AND ' . $eligibleChildren,
        );
        $countAlerts->execute(['user_id' => $userId]);
        $patients = $database->prepare(
            'SELECT c.id, c.first_name, c.last_name, c.date_of_birth,
                    (SELECT MAX(gm.measured_at_utc) FROM growth_measurements AS gm
                     WHERE gm.child_id = c.id AND gm.recorded_by_user_id = :measurement_user) AS last_measurement
             FROM children AS c
             WHERE ' . $eligibleChildren . '
               AND (c.created_by_user_id = :created_by
                    OR EXISTS (SELECT 1 FROM growth_measurements AS gm
                               WHERE gm.child_id = c.id AND gm.recorded_by_user_id = :growth_user)
                    OR EXISTS (SELECT 1 FROM nutrition_records AS nr
                               WHERE nr.child_id = c.id AND nr.health_worker_id = :nutrition_user)
                    OR EXISTS (SELECT 1 FROM community_visits AS cv
                               WHERE cv.child_id = c.id AND cv.agent_user_id = :visit_user)
                    OR EXISTS (SELECT 1 FROM health_referrals AS hr
                               WHERE hr.child_id = c.id AND hr.agent_user_id = :referral_user))
             ORDER BY last_measurement DESC, c.id DESC LIMIT 50',
        );
        $patients->execute([
            'measurement_user' => $userId,
            'created_by' => $userId,
            'growth_user' => $userId,
            'nutrition_user' => $userId,
            'visit_user' => $userId,
            'referral_user' => $userId,
        ]);
        $communityChildren = $database->prepare(
            'SELECT c.id, c.first_name, c.last_name
             FROM children AS c
             WHERE ' . $eligibleChildren . '
               AND (c.created_by_user_id = :created_by
                    OR EXISTS (SELECT 1 FROM growth_measurements AS gm
                               WHERE gm.child_id = c.id AND gm.recorded_by_user_id = :growth_by)
                    OR EXISTS (SELECT 1 FROM nutrition_records AS nr
                               WHERE nr.child_id = c.id AND nr.health_worker_id = :nutrition_by)
                    OR EXISTS (SELECT 1 FROM community_visits AS cv
                               WHERE cv.child_id = c.id AND cv.agent_user_id = :visit_by)
                    OR EXISTS (SELECT 1 FROM health_referrals AS hr
                               WHERE hr.child_id = c.id AND hr.agent_user_id = :referral_by))
             ORDER BY c.last_name, c.first_name, c.id LIMIT 100',
        );
        $communityChildren->execute([
            'created_by' => $userId,
            'growth_by' => $userId,
            'nutrition_by' => $userId,
            'visit_by' => $userId,
            'referral_by' => $userId,
        ]);
        $pendingEnrollments = $database->prepare(
            'SELECT c.id, c.first_name, c.last_name, c.date_of_birth, u.display_name AS parent_name
             FROM children AS c
             INNER JOIN child_guardians AS cg ON cg.child_id = c.id AND cg.consent_status = \'pending\'
             INNER JOIN users AS u ON u.id = cg.user_id
             WHERE c.created_by_user_id = :agent_id
             ORDER BY c.created_at_utc DESC LIMIT 50',
        );
        $pendingEnrollments->execute(['agent_id' => $userId]);
        $visits = $database->prepare(
            'SELECT cv.visit_type, cv.visited_at_utc, cv.purpose, cv.observations,
                    c.first_name, c.last_name
             FROM community_visits AS cv
             INNER JOIN children AS c ON c.id = cv.child_id
             WHERE cv.agent_user_id = :agent_id AND ' . $eligibleChildren . '
             ORDER BY cv.visited_at_utc DESC, cv.id DESC LIMIT 20',
        );
        $visits->execute(['agent_id' => $userId]);
        $growthRecords = $database->prepare(
            'SELECT gm.measured_at_utc, gm.weight_kg, gm.length_height_cm,
                gm.length_height_type, gm.muac_cm, c.first_name, c.last_name
             FROM growth_measurements AS gm
             INNER JOIN children AS c ON c.id = gm.child_id
             WHERE gm.recorded_by_user_id = :agent_id AND ' . $eligibleChildren . '
             ORDER BY gm.measured_at_utc DESC, gm.id DESC LIMIT 20',
        );
        $growthRecords->execute(['agent_id' => $userId]);
        $nutritionRecords = $database->prepare(
            'SELECT nr.record_date, nr.feeding_type, nr.observations,
                c.first_name, c.last_name
             FROM nutrition_records AS nr
             INNER JOIN children AS c ON c.id = nr.child_id
             WHERE nr.health_worker_id = :agent_id AND ' . $eligibleChildren . '
             ORDER BY nr.record_date DESC, nr.id DESC LIMIT 20',
        );
        $nutritionRecords->execute(['agent_id' => $userId]);
        $referrals = $database->prepare(
            'SELECT hr.id, hr.priority, hr.status, hr.concern_description, hr.referred_at_utc,
                    hr.follow_up_notes, c.first_name, c.last_name,
                    hc.name AS health_center_name
             FROM health_referrals AS hr
             INNER JOIN children AS c ON c.id = hr.child_id
             INNER JOIN health_centers AS hc ON hc.id = hr.health_center_id
             WHERE hr.agent_user_id = :agent_id AND ' . $eligibleChildren . '
             ORDER BY FIELD(hr.status, \'awaiting_follow_up\', \'not_attended\', \'attended\', \'cancelled\'),
                      hr.referred_at_utc DESC LIMIT 30',
        );
        $referrals->execute(['agent_id' => $userId]);
        $centers = $database->query(
            'SELECT id, name, city, province, address_text, phone_e164
             FROM health_centers
             WHERE name NOT LIKE \'DEMO UNIQUEMENT%\'
             ORDER BY name LIMIT 100',
        );
        $countReferrals = $database->prepare(
            'SELECT COUNT(*) FROM health_referrals
             WHERE agent_user_id = :agent_id AND status = \'awaiting_follow_up\'',
        );
        $countReferrals->execute(['agent_id' => $userId]);
        $activities = $database->prepare(
            'SELECT action, description, created_at_utc
             FROM activity_logs WHERE user_id = :user_id
             ORDER BY created_at_utc DESC LIMIT 10',
        );
        $activities->execute(['user_id' => $userId]);

        return [
            'displayName' => (string) $user['display_name'],
            'stats' => [
                ['label' => 'Enfants autorisés', 'value' => (int) $countChildren->fetchColumn()],
                ['label' => 'Mesures enregistrées', 'value' => (int) $countMeasurements->fetchColumn()],
                ['label' => 'Alertes à examiner', 'value' => (int) $countAlerts->fetchColumn()],
                ['label' => 'Référencements à suivre', 'value' => (int) $countReferrals->fetchColumn()],
            ],
            'patients' => $patients->fetchAll(),
            'communityChildren' => $communityChildren->fetchAll(),
            'pendingEnrollments' => $pendingEnrollments->fetchAll(),
            'visits' => $visits->fetchAll(),
            'growthRecords' => $growthRecords->fetchAll(),
            'nutritionRecords' => $nutritionRecords->fetchAll(),
            'referrals' => $referrals->fetchAll(),
            'centers' => $centers->fetchAll(),
            'activities' => $activities->fetchAll(),
        ];
    }

    public function healthProfessionalDashboard(array $user): array
    {
        $database = $this->database();
        $userId = (int) $user['id'];
        $assignedCenters = 'EXISTS (
            SELECT 1 FROM user_health_centers AS uhc
            WHERE uhc.user_id = :center_user
              AND uhc.health_center_id = ha.health_center_id
        )';
        $consentedChildren = 'EXISTS (
            SELECT 1 FROM child_guardians AS cg
            WHERE cg.child_id = c.id AND cg.consent_status = \'granted\'
        )';
        $countFiles = $database->prepare(
            'SELECT COUNT(DISTINCT c.id) FROM health_alerts AS ha
             INNER JOIN children AS c ON c.id = ha.child_id
             WHERE ha.health_center_id IS NOT NULL AND ' . $assignedCenters . ' AND ' . $consentedChildren,
        );
        $countFiles->execute(['center_user' => $userId]);
        $countAlerts = $database->prepare(
            'SELECT COUNT(*) FROM health_alerts AS ha
             INNER JOIN children AS c ON c.id = ha.child_id
             WHERE ha.status IN (\'pending_review\', \'under_review\')
               AND ha.health_center_id IS NOT NULL AND ' . $assignedCenters . ' AND ' . $consentedChildren,
        );
        $countAlerts->execute(['center_user' => $userId]);
        $recentAlerts = $database->prepare(
            'SELECT ha.id, ha.child_id, ha.health_center_id, ha.alert_type, ha.severity,
                    ha.status, ha.description, ha.review_notes, ha.created_at_utc,
                    c.first_name, c.last_name, hc.name AS health_center_name
             FROM health_alerts AS ha
             INNER JOIN children AS c ON c.id = ha.child_id
             INNER JOIN health_centers AS hc ON hc.id = ha.health_center_id
             WHERE ha.health_center_id IS NOT NULL AND ' . $assignedCenters . ' AND ' . $consentedChildren . '
             ORDER BY FIELD(ha.status, \'pending_review\', \'under_review\', \'referred\', \'resolved\', \'dismissed\'),
                      ha.created_at_utc DESC LIMIT 30',
        );
        $recentAlerts->execute(['center_user' => $userId]);
        $referrals = $database->prepare(
            'SELECT hr.id, hr.child_id, hr.health_center_id, hr.concern_description,
                    hr.priority, hr.status, hr.referred_at_utc, hr.follow_up_notes,
                    c.first_name, c.last_name, hc.name AS health_center_name
             FROM health_referrals AS hr
             INNER JOIN children AS c ON c.id = hr.child_id
             INNER JOIN health_centers AS hc ON hc.id = hr.health_center_id
             INNER JOIN user_health_centers AS uhc
                ON uhc.health_center_id = hr.health_center_id AND uhc.user_id = :center_user
             WHERE hr.status IN (\'awaiting_follow_up\', \'not_attended\') AND ' . $consentedChildren . '
             ORDER BY FIELD(hr.priority, \'emergency\', \'prompt\', \'routine\'),
                      hr.referred_at_utc ASC LIMIT 50',
        );
        $referrals->execute(['center_user' => $userId]);
        $accessibleCases = '(
            EXISTS (
                SELECT 1 FROM health_alerts AS case_alert
                INNER JOIN user_health_centers AS alert_center
                    ON alert_center.health_center_id = case_alert.health_center_id
                INNER JOIN child_guardians AS alert_guardian
                    ON alert_guardian.child_id = case_alert.child_id
                   AND alert_guardian.consent_status = \'granted\'
                WHERE case_alert.child_id = c.id AND alert_center.user_id = :alert_center_user
            )
            OR EXISTS (
                SELECT 1 FROM health_referrals AS case_referral
                INNER JOIN user_health_centers AS referral_center
                    ON referral_center.health_center_id = case_referral.health_center_id
                INNER JOIN child_guardians AS referral_guardian
                    ON referral_guardian.child_id = case_referral.child_id
                   AND referral_guardian.consent_status = \'granted\'
                WHERE case_referral.child_id = c.id AND referral_center.user_id = :referral_center_user
            )
        )';
        $growthRecords = $database->prepare(
            'SELECT c.id AS child_id, c.first_name, c.last_name,
                    gm.measured_at_utc, gm.weight_kg, gm.length_height_cm,
                    gm.length_height_type, gm.muac_cm
             FROM growth_measurements AS gm
             INNER JOIN children AS c ON c.id = gm.child_id
             WHERE ' . $accessibleCases . '
             ORDER BY c.id, gm.measured_at_utc DESC LIMIT 100',
        );
        $growthRecords->execute([
            'alert_center_user' => $userId,
            'referral_center_user' => $userId,
        ]);
        $nutritionRecords = $database->prepare(
            'SELECT c.id AS child_id, c.first_name, c.last_name,
                    nr.record_date, nr.nutritional_status, nr.feeding_type,
                    nr.observations, nr.recommendations
             FROM nutrition_records AS nr
             INNER JOIN children AS c ON c.id = nr.child_id
             WHERE ' . $accessibleCases . '
             ORDER BY nr.record_date DESC, nr.id DESC LIMIT 100',
        );
        $nutritionRecords->execute([
            'alert_center_user' => $userId,
            'referral_center_user' => $userId,
        ]);
        $observationContexts = $database->prepare(
            'SELECT ha.id AS context_id, ha.child_id, ha.health_center_id,
                    c.first_name, c.last_name, hc.name AS health_center_name,
                    \'alert\' AS context_type
             FROM health_alerts AS ha
             INNER JOIN children AS c ON c.id = ha.child_id
             INNER JOIN health_centers AS hc ON hc.id = ha.health_center_id
             INNER JOIN user_health_centers AS uhc ON uhc.health_center_id = ha.health_center_id
             WHERE uhc.user_id = :alert_user AND ' . $consentedChildren . '
             UNION ALL
             SELECT hr.id AS context_id, hr.child_id, hr.health_center_id,
                    c.first_name, c.last_name, hc.name AS health_center_name,
                    \'referral\' AS context_type
             FROM health_referrals AS hr
             INNER JOIN children AS c ON c.id = hr.child_id
             INNER JOIN health_centers AS hc ON hc.id = hr.health_center_id
             INNER JOIN user_health_centers AS uhc ON uhc.health_center_id = hr.health_center_id
             WHERE uhc.user_id = :referral_user AND ' . $consentedChildren . '
             ORDER BY context_id DESC LIMIT 100',
        );
        $observationContexts->execute([
            'alert_user' => $userId,
            'referral_user' => $userId,
        ]);
        $professionalObservations = $database->prepare(
            'SELECT po.observation_type, po.content, po.created_at_utc,
                    c.first_name, c.last_name, u.display_name AS author_name,
                    hc.name AS health_center_name
             FROM professional_observations AS po
             INNER JOIN children AS c ON c.id = po.child_id
             INNER JOIN users AS u ON u.id = po.health_professional_id
             INNER JOIN health_centers AS hc ON hc.id = po.health_center_id
             INNER JOIN user_health_centers AS uhc
                ON uhc.health_center_id = po.health_center_id AND uhc.user_id = :center_user
             INNER JOIN child_guardians AS cg
                ON cg.child_id = c.id AND cg.consent_status = \'granted\'
             ORDER BY po.created_at_utc DESC LIMIT 50',
        );
        $professionalObservations->execute(['center_user' => $userId]);
        $professionalCenters = $database->prepare(
            'SELECT hc.id, hc.name
             FROM user_health_centers AS uhc
             INNER JOIN health_centers AS hc ON hc.id = uhc.health_center_id
             WHERE uhc.user_id = :user_id ORDER BY hc.name',
        );
        $professionalCenters->execute(['user_id' => $userId]);

        return [
            'displayName' => (string) $user['display_name'],
            'stats' => [
                ['label' => 'Dossiers autorisés', 'value' => (int) $countFiles->fetchColumn()],
                ['label' => 'Alertes à examiner', 'value' => (int) $countAlerts->fetchColumn()],
            ],
            'alerts' => $recentAlerts->fetchAll(),
            'referrals' => $referrals->fetchAll(),
            'growthRecords' => $growthRecords->fetchAll(),
            'nutritionRecords' => $nutritionRecords->fetchAll(),
            'observationContexts' => $observationContexts->fetchAll(),
            'professionalObservations' => $professionalObservations->fetchAll(),
            'centers' => $professionalCenters->fetchAll(),
        ];
    }

    public function intelligenceDashboard(): array
    {
        return [
            'stats' => [
                ['label' => 'Dossiers fictifs', 'value' => '1 284', 'note' => 'Jeu de démonstration'],
                ['label' => 'Zones illustratives', 'value' => '4', 'note' => 'Noms non géographiques'],
                ['label' => 'Points à examiner', 'value' => '7', 'note' => 'Exemples, sans alerte clinique'],
            ],
            'zones' => [
                ['name' => 'Zone Démo A', 'records' => '382', 'coverage' => '76 %', 'status' => 'Illustration'],
                ['name' => 'Zone Démo B', 'records' => '316', 'coverage' => '68 %', 'status' => 'Illustration'],
                ['name' => 'Zone Démo C', 'records' => '294', 'coverage' => '81 %', 'status' => 'Illustration'],
                ['name' => 'Zone Démo D', 'records' => '292', 'coverage' => '72 %', 'status' => 'Illustration'],
            ],
            'alerts' => [
                ['title' => 'Variation illustrative à vérifier', 'zone' => 'Zone Démo A', 'priority' => 'Exemple'],
                ['title' => 'Données de démonstration incomplètes', 'zone' => 'Zone Démo C', 'priority' => 'Exemple'],
                ['title' => 'Tendance fictive à examiner', 'zone' => 'Zone Démo D', 'priority' => 'Exemple'],
            ],
            'indicatorPoints' => [
                'week' => '30,88 100,72 170,77 240,53 310,64 380,44 450,37 520,23',
                'month' => '30,91 100,84 170,67 240,74 310,55 380,48 450,31 520,26',
            ],
        ];
    }

    public function healthCenterDashboard(array $user): array
    {
        $database = $this->database();
        $managerId = (int) ($user['id'] ?? 0);
        if ($managerId < 1 || ($user['role_code'] ?? null) !== 'health_center_manager') {
            throw new InvalidArgumentException('Cet espace est réservé aux responsables d’établissement.');
        }

        $centersQuery = $database->prepare(
            'SELECT hc.id, hc.name, hc.address_text, hc.city, hc.province,
                    hc.phone_e164
             FROM user_health_centers AS uhc
             INNER JOIN health_centers AS hc ON hc.id = uhc.health_center_id
             WHERE uhc.user_id = :manager_id
             ORDER BY hc.name',
        );
        $centersQuery->execute(['manager_id' => $managerId]);
        $centers = $centersQuery->fetchAll();

        $appointments = $database->prepare(
            'SELECT ar.id, ar.request_type, ar.preferred_date, ar.created_at_utc,
                    c.first_name, c.last_name, hc.name AS center_name
             FROM appointment_requests AS ar
             INNER JOIN children AS c ON c.id = ar.child_id
             INNER JOIN health_centers AS hc ON hc.id = ar.health_center_id
             INNER JOIN user_health_centers AS uhc
                ON uhc.health_center_id = ar.health_center_id AND uhc.user_id = :manager_id
             INNER JOIN child_guardians AS cg
                ON cg.child_id = ar.child_id AND cg.user_id = ar.parent_user_id
               AND cg.consent_status = \'granted\'
             WHERE ar.status = \'pending\'
             ORDER BY ar.preferred_date, ar.created_at_utc LIMIT 50',
        );
        $appointments->execute(['manager_id' => $managerId]);
        $referrals = $database->prepare(
            'SELECT hr.id, hr.priority, hr.status, hr.concern_description,
                    hr.referred_at_utc, c.first_name, c.last_name,
                    hc.name AS center_name
             FROM health_referrals AS hr
             INNER JOIN children AS c ON c.id = hr.child_id
             INNER JOIN health_centers AS hc ON hc.id = hr.health_center_id
             INNER JOIN user_health_centers AS uhc
                ON uhc.health_center_id = hr.health_center_id AND uhc.user_id = :manager_id
             INNER JOIN child_guardians AS cg
                ON cg.child_id = hr.child_id AND cg.consent_status = \'granted\'
             WHERE hr.status IN (\'awaiting_follow_up\', \'not_attended\')
             ORDER BY FIELD(hr.priority, \'emergency\', \'prompt\', \'routine\'),
                      hr.referred_at_utc LIMIT 50',
        );
        $referrals->execute(['manager_id' => $managerId]);
        $professionals = $database->prepare(
            'SELECT u.display_name, u.email, hc.name AS center_name
             FROM user_health_centers AS manager_center
             INNER JOIN user_health_centers AS professional_center
                ON professional_center.health_center_id = manager_center.health_center_id
             INNER JOIN health_centers AS hc ON hc.id = manager_center.health_center_id
             INNER JOIN users AS u ON u.id = professional_center.user_id AND u.is_active = 1
             INNER JOIN user_roles AS ur ON ur.user_id = u.id
               AND ur.role_code = \'health_professional\'
             WHERE manager_center.user_id = :manager_id
             ORDER BY hc.name, u.display_name',
        );
        $professionals->execute(['manager_id' => $managerId]);
        $centerSummary = $database->prepare(
            'SELECT hc.id, hc.name, hc.city, hc.province,
                    (SELECT COUNT(*) FROM health_referrals AS hr
                     INNER JOIN child_guardians AS cg ON cg.child_id = hr.child_id
                        AND cg.consent_status = \'granted\'
                     WHERE hr.health_center_id = hc.id
                       AND hr.status IN (\'awaiting_follow_up\', \'not_attended\')) AS referrals_to_follow,
                    (SELECT COUNT(*) FROM appointment_requests AS ar
                     INNER JOIN child_guardians AS cg ON cg.child_id = ar.child_id
                        AND cg.user_id = ar.parent_user_id AND cg.consent_status = \'granted\'
                     WHERE ar.health_center_id = hc.id AND ar.status = \'pending\') AS appointments_to_process,
                    (SELECT COUNT(*) FROM vaccination_records AS vr
                     WHERE vr.health_center_id = hc.id AND vr.status = \'administered\') AS vaccinations_recorded
             FROM user_health_centers AS uhc
             INNER JOIN health_centers AS hc ON hc.id = uhc.health_center_id
             WHERE uhc.user_id = :manager_id ORDER BY hc.name',
        );
        $centerSummary->execute(['manager_id' => $managerId]);

        $appointmentRows = $appointments->fetchAll();
        $referralRows = $referrals->fetchAll();
        $summary = $centerSummary->fetchAll();
        return [
            'displayName' => (string) $user['display_name'],
            'stats' => [
                ['label' => 'Établissements affectés', 'value' => count($centers)],
                ['label' => 'Rendez-vous à traiter', 'value' => count($appointmentRows)],
                ['label' => 'Orientations en attente', 'value' => count($referralRows)],
                ['label' => 'Vaccinations par centre', 'value' => array_sum(array_map(static fn (array $center): int => (int) $center['vaccinations_recorded'], $summary))],
            ],
            'centers' => $centers,
            'centerSummary' => $summary,
            'appointments' => $appointmentRows,
            'referrals' => $referralRows,
            'professionals' => $professionals->fetchAll(),
        ];
    }

    public function administratorDashboard(): array
    {
        $database = $this->database();
        $userCount = $database->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $roleCounts = $database->query(
            'SELECT ur.role_code, COUNT(DISTINCT ur.user_id) AS total
             FROM user_roles AS ur INNER JOIN users AS u ON u.id = ur.user_id
             GROUP BY ur.role_code',
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        $users = $database->query(
            'SELECT u.display_name AS name, u.email, u.is_active, u.created_at_utc,
                    MIN(ur.role_code) AS role_code
             FROM users AS u INNER JOIN user_roles AS ur ON ur.user_id = u.id
             GROUP BY u.id, u.display_name, u.email, u.is_active, u.created_at_utc
             ORDER BY u.created_at_utc DESC LIMIT 8',
        )->fetchAll();
        $centerCount = (int) $database->query(
            'SELECT COUNT(*) FROM health_centers WHERE name NOT LIKE \'DEMO UNIQUEMENT%\'',
        )->fetchColumn();
        $activities = $database->query(
            'SELECT al.action, al.description, al.created_at_utc, u.display_name
             FROM activity_logs AS al
             LEFT JOIN users AS u ON u.id = al.user_id
             ORDER BY al.created_at_utc DESC LIMIT 8',
        )->fetchAll();

        return [
            'stats' => [
                ['label' => 'Utilisateurs', 'value' => (int) $userCount],
                ['label' => 'Parents', 'value' => (int) ($roleCounts['parent'] ?? 0)],
                ['label' => 'Agents communautaires', 'value' => (int) ($roleCounts['community_health_worker'] ?? 0)],
                ['label' => 'Professionnels de santé', 'value' => (int) ($roleCounts['health_professional'] ?? 0)],
                ['label' => 'Responsables de centre', 'value' => (int) ($roleCounts['health_center_manager'] ?? 0)],
                ['label' => 'Superviseurs sanitaires', 'value' => (int) ($roleCounts['health_supervisor'] ?? 0)],
                ['label' => 'Centres partenaires', 'value' => $centerCount],
            ],
            'users' => $users,
            'activities' => $activities,
        ];
    }

    public function healthSupervisorDashboard(array $user): array
    {
        $database = $this->database();
        $supervisorId = (int) ($user['id'] ?? 0);
        if ($supervisorId < 1 || ($user['role_code'] ?? null) !== 'health_supervisor') {
            throw new InvalidArgumentException('Cet espace est réservé aux superviseurs sanitaires.');
        }

        $centerMetrics = $database->prepare(
            'SELECT hc.id, hc.name, hc.city, hc.province,
                    COUNT(DISTINCT CASE WHEN cg.child_id IS NOT NULL THEN hr.child_id END) AS referred_children,
                    COUNT(DISTINCT CASE WHEN cg.child_id IS NOT NULL THEN hr.id END) AS referrals_total,
                    COUNT(DISTINCT CASE WHEN cg.child_id IS NOT NULL
                        AND hr.status IN (\'awaiting_follow_up\', \'not_attended\') THEN hr.id END) AS referrals_open,
                    COUNT(DISTINCT CASE WHEN vgc.child_id IS NOT NULL
                        AND vr.status = \'administered\' THEN vr.id END) AS vaccination_doses,
                    COUNT(DISTINCT CASE WHEN cg.child_id IS NOT NULL THEN gm.id END) AS growth_measurements,
                    COUNT(DISTINCT CASE WHEN cg.child_id IS NOT NULL THEN nr.id END) AS nutrition_screenings
             FROM user_health_centers AS supervisor_center
             INNER JOIN health_centers AS hc ON hc.id = supervisor_center.health_center_id
             LEFT JOIN health_referrals AS hr ON hr.health_center_id = hc.id
             LEFT JOIN child_guardians AS cg ON cg.child_id = hr.child_id AND cg.consent_status = \'granted\'
             LEFT JOIN growth_measurements AS gm ON gm.child_id = hr.child_id
             LEFT JOIN nutrition_records AS nr ON nr.child_id = hr.child_id
             LEFT JOIN vaccination_records AS vr ON vr.health_center_id = hc.id
             LEFT JOIN child_guardians AS vgc ON vgc.child_id = vr.child_id AND vgc.consent_status = \'granted\'
             WHERE supervisor_center.user_id = :supervisor_id
             GROUP BY hc.id, hc.name, hc.city, hc.province
             ORDER BY hc.province, hc.city, hc.name',
        );
        $centerMetrics->execute(['supervisor_id' => $supervisorId]);
        $centers = $centerMetrics->fetchAll();
        $visibleCenters = 0;
        $suppressedCenters = 0;
        $totalReferralsOpen = 0;
        $totalVaccinations = 0;
        $totalNutritionScreenings = 0;
        foreach ($centers as &$center) {
            $center['privacy_suppressed'] = (int) $center['referred_children'] < 5;
            if ($center['privacy_suppressed']) {
                $suppressedCenters++;
                foreach (['referred_children', 'referrals_total', 'referrals_open', 'vaccination_doses', 'growth_measurements', 'nutrition_screenings'] as $metric) {
                    $center[$metric] = null;
                }
                continue;
            }
            $visibleCenters++;
            $totalReferralsOpen += (int) $center['referrals_open'];
            $totalVaccinations += (int) $center['vaccination_doses'];
            $totalNutritionScreenings += (int) $center['nutrition_screenings'];
        }
        unset($center);

        $monthlyTrends = $database->prepare(
            'SELECT DATE_FORMAT(hr.referred_at_utc, \'%Y-%m\') AS period,
                    COUNT(DISTINCT hr.child_id) AS referred_children,
                    COUNT(DISTINCT hr.id) AS referrals_total
             FROM health_referrals AS hr
             INNER JOIN user_health_centers AS uhc
                ON uhc.health_center_id = hr.health_center_id AND uhc.user_id = :supervisor_id
             INNER JOIN child_guardians AS cg
                ON cg.child_id = hr.child_id AND cg.consent_status = \'granted\'
             WHERE hr.referred_at_utc >= UTC_TIMESTAMP() - INTERVAL 6 MONTH
             GROUP BY DATE_FORMAT(hr.referred_at_utc, \'%Y-%m\')
             HAVING COUNT(DISTINCT hr.child_id) >= 5
             ORDER BY period',
        );
        $monthlyTrends->execute(['supervisor_id' => $supervisorId]);
        $trendRows = $monthlyTrends->fetchAll();
          $workerActivity = $database->prepare(
                'SELECT u.display_name, COUNT(DISTINCT hr.child_id) AS referred_children,
                          COUNT(DISTINCT hr.id) AS referrals_total
                 FROM health_referrals AS hr
                 INNER JOIN user_health_centers AS uhc
                     ON uhc.health_center_id = hr.health_center_id AND uhc.user_id = :supervisor_id
                 INNER JOIN users AS u ON u.id = hr.agent_user_id AND u.is_active = 1
                 INNER JOIN user_roles AS ur ON ur.user_id = u.id
                    AND ur.role_code = \'community_health_worker\'
                 INNER JOIN child_guardians AS cg
                     ON cg.child_id = hr.child_id AND cg.consent_status = \'granted\'
                 GROUP BY u.id, u.display_name
                 HAVING COUNT(DISTINCT hr.child_id) >= 5
                 ORDER BY referrals_total DESC, u.display_name LIMIT 30',
          );
          $workerActivity->execute(['supervisor_id' => $supervisorId]);

        return [
            'displayName' => (string) $user['display_name'],
            'stats' => [
                ['label' => 'Centres affectés', 'value' => count($centers)],
                ['label' => 'Centres avec cohortes suffisantes', 'value' => $visibleCenters],
                ['label' => 'Orientations en attente', 'value' => $totalReferralsOpen],
                ['label' => 'Doses enregistrées', 'value' => $totalVaccinations],
            ],
            'suppressedCenters' => $suppressedCenters,
            'centers' => $centers,
            'monthlyTrends' => $trendRows,
            'workerActivity' => $workerActivity->fetchAll(),
            'nutritionScreenings' => $totalNutritionScreenings,
        ];
    }

    private function database(): PDO
    {
        if (!$this->database instanceof PDO) {
            throw new RuntimeException('Une connexion à la base de données est requise pour afficher ce tableau de bord.');
        }

        return $this->database;
    }

    public static function renderServiceUnavailable(string $basePath, RuntimeException $error): never
    {
        error_log('[AFYAMTOTO dashboard] ' . $error->getMessage());
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        $safeBasePath = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Service indisponible — AFYAMTOTO AI</title></head><body><main><h1>Espace momentanément indisponible</h1><p>Vérifiez la configuration MySQL et réessayez.</p><a href="' . $safeBasePath . '/pages/connexion.php">Retour à la connexion</a></main></body></html>';
        exit;
    }
}
