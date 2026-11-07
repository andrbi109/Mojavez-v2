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
            'ownership_status' => $data['ownership_status'],
            'province' => $data['province'],
            'county' => $data['county'],
            'city' => $data['city'],
            'district' => $data['district'] ?? null,
            'rural_district' => $data['rural_district'] ?? null,
            'village' => $data['village'] ?? null,
            'electricity_bill_id' => $data['electricity_bill_id'],
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
}