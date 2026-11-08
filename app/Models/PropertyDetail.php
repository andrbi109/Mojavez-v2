<?php
class PropertyDetail
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO property_details 
                (applicant_id, ownership_status, province, county, city, district, rural_district, village, electricity_bill_id, 
                address_street, address_alley, address_plaque)
            VALUES 
                (:applicant_id, :ownership_status, :province, :county, :city, :district, :rural_district, :village, :electricity_bill_id,
                :address_street, :address_alley, :address_plaque)"
        );

        $stmt->execute([
            'applicant_id' => $data['applicant_id'],
            'ownership_status' => $data['ownership_status'] ?? 'شخص',
            'province' => $data['province'] ?? '',
            'county' => $data['county'] ?? $data['city'] ?? '',
            'city' => $data['city'] ?? '',
            'district' => $data['district'] ?? null,
            'rural_district' => $data['rural_district'] ?? null,
            'village' => $data['village'] ?? null,
            'electricity_bill_id' => $data['electricity_bill_id'] ?? '0000000000',
            'address_street' => $data['address_street'] ?? null,
            'address_alley' => $data['address_alley'] ?? null,
            'address_plaque' => $data['address_plaque'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByApplicantId(int $applicantId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM property_details WHERE applicant_id = :id LIMIT 1");
        $stmt->execute(['id' => $applicantId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * به‌روزرسانی اطلاعات ملک یک متقاضی.
     * اگر رکوردی موجود نبود، آن را می‌سازد (upsert).
     */
    public function updateByApplicantId(int $applicantId, array $data): void
    {
        $existing = $this->findByApplicantId($applicantId);
        $params = [
            'aid' => $applicantId,
            'os' => $data['ownership_status'] ?? 'شخص',
            'pr' => $data['province'] ?? '',
            'co' => $data['county'] ?? ($data['city'] ?? ''),
            'ci' => $data['city'] ?? '',
            'di' => $data['district'] ?? null,
            'rd' => $data['rural_district'] ?? null,
            'vi' => $data['village'] ?? null,
            'eb' => $data['electricity_bill_id'] ?? '0000000000',
            'as' => $data['address_street'] ?? null,
            'al' => $data['address_alley'] ?? null,
            'ap' => $data['address_plaque'] ?? null,
        ];
        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE property_details
                 SET ownership_status = :os, province = :pr, county = :co, city = :ci,
                     district = :di, rural_district = :rd, village = :vi,
                     electricity_bill_id = :eb, address_street = :as, address_alley = :al, address_plaque = :ap
                 WHERE applicant_id = :aid"
            );
            $stmt->execute($params);
        } else {
            $this->create([
                'applicant_id' => $applicantId,
                'ownership_status' => $params['os'],
                'province' => $params['pr'],
                'county' => $params['co'],
                'city' => $params['ci'],
                'district' => $params['di'],
                'rural_district' => $params['rd'],
                'village' => $params['vi'],
                'electricity_bill_id' => $params['eb'],
                'address_street' => $params['as'],
                'address_alley' => $params['al'],
                'address_plaque' => $params['ap'],
            ]);
        }
    }
}