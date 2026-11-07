<?php
/**
 * سرویس محاسبهٔ مبلغ پرداخت بر اساس نرخ‌نامه (FeeCalculatorService)
 * -----------------------------------------------------------------
 * منطق:
 *   ۱. اگر fee_tariff_active غیرفعال → registration_fee_amount (سازگاری عقب)
 *   ۲. اگر روستایی (village یا rural_district پر شده) → گروه الف
 *      - اگر city بزرگ (تهران/مشهد/...) بود → flag rural_address_mismatch
 *   ۳. در غیر این صورت، بر اساس دهک درآمدی
 *   ۴. اگر دهک خالی/نامعتبر → fallback به گروه C با flag fallback_used
 *   ۵. تشخیص تناقض دهک با پرونده‌های قبلی شخص → flag decile_history_conflict
 *
 * سپس مبلغ با تیک خدمات مدیر قابل تنظیم است (final_amount).
 */
class FeeCalculatorService
{
    /** شهرهای بزرگ ایران برای تشخیص آدرس مشکوک */
    private const BIG_CITIES = [
        'تهران', 'مشهد', 'اصفهان', 'شیراز', 'تبریز', 'اهواز', 'کرج', 'قم', 'رشت',
        'کرمانشاه', 'ارومیه', 'یزد', 'زاهدان', 'قزوین', 'گرگان', 'ساری',
        'بندرعباس', 'کرمان', 'همدان', 'اردبیل', 'بجنورد', 'ایلام', 'سنندج',
        'خرم‌آباد', 'مرکز', 'ساوه', 'نجف‌آباد', 'کرج', 'ورامین',
    ];

    /**
     * محاسبهٔ کامل برای یک متقاضی.
     *
     * @return array {
     *   amount: int,            مبلغ اصلی محاسبه‌شده (پیش از تخفیف)
     *   group: ?array,          گروه قیمتی
     *   is_rural: bool,         آیا معافیت روستایی اعمال شد؟
     *   is_suspicious: bool,    آیا flags مشکوک وجود دارد؟
     *   suspicion_flags: array, لیست flag ها
     *   fallback_used: bool,    آیا fallback استفاده شد؟
     *   reason: string,         توضیح محاسبه
     * }
     */
    public static function calculateForApplicant(int $applicantId): array
    {
        // اگر نرخ‌نامه غیرفعال است → مبلغ ثابت قبلی
        if ((int)setting('fee_tariff_active', '1') !== 1) {
            $legacy = (int)setting('registration_fee_amount', '500000');
            return [
                'amount' => $legacy,
                'group' => null,
                'is_rural' => false,
                'is_suspicious' => false,
                'suspicion_flags' => [],
                'fallback_used' => false,
                'reason' => 'نرخ‌نامه غیرفعال — مبلغ ثابت قبلی',
            ];
        }

        $applicant = (new Applicant())->findById($applicantId);
        if (!$applicant) {
            return self::fallback();
        }

        $propertyModel = new PropertyDetail();
        $property = $propertyModel->findByApplicantId($applicantId);

        // ۱. بررسی روستایی بودن
        $ruralCheck = self::isRural($property);
        if ($ruralCheck['rural'] && (int)setting('fee_rural_auto_eligible', '1') === 1) {
            $group = (new FeeGroup())->findByCode('A');
            $flags = [];
            if ($ruralCheck['suspicious']) {
                $flags[] = 'rural_address_mismatch';
            }
            return [
                'amount' => $group ? (int)$group['total_amount'] : self::fallbackAmount(),
                'group' => $group,
                'is_rural' => true,
                'is_suspicious' => !empty($flags),
                'suspicion_flags' => $flags,
                'fallback_used' => false,
                'reason' => 'معافیت روستایی اعمال شد — گروه الف',
            ];
        }

        // ۲. بر اساس دهک درآمدی
        $decile = (int)($applicant['income_decile'] ?? 0);
        if ($decile < 1 || $decile > 10) {
            // دهک نامعتبر → fallback به گروه C با flag
            $fallbackCode = setting('fee_fallback_group_code', 'C');
            $group = (new FeeGroup())->findByCode($fallbackCode);
            return [
                'amount' => $group ? (int)$group['total_amount'] : self::fallbackAmount(),
                'group' => $group,
                'is_rural' => false,
                'is_suspicious' => true,
                'suspicion_flags' => ['fallback_used'],
                'fallback_used' => true,
                'reason' => 'دهک درآمدی اعلام نشده — گروه متوسط اعمال شد',
            ];
        }

        $group = (new FeeGroup())->findByDecile($decile);
        if (!$group) {
            return self::fallback();
        }

        // تشخیص مشکوک بودن
        $flags = self::detectSuspicion($applicant, $property, $decile);

        return [
            'amount' => (int)$group['total_amount'],
            'group' => $group,
            'is_rural' => false,
            'is_suspicious' => !empty($flags),
            'suspicion_flags' => $flags,
            'fallback_used' => false,
            'reason' => 'گروه بر اساس دهک درآمدی ' . $decile . ' محاسبه شد',
        ];
    }

    /**
     * تشخیص روستایی بودن از فیلدهای آدرس موجود.
     *
     * @return array { rural: bool, suspicious: bool }
     */
    public static function isRural(?array $property): array
    {
        if (!$property) return ['rural' => false, 'suspicious' => false];

        $village = trim((string)($property['village'] ?? ''));
        $ruralDistrict = trim((string)($property['rural_district'] ?? ''));
        $city = trim((string)($property['city'] ?? ''));

        $isRural = $village !== '' || $ruralDistrict !== '';
        $suspicious = $isRural && self::isBigCity($city);

        return ['rural' => $isRural, 'suspicious' => $suspicious];
    }

    /**
     * تشخیص flags مشکوک بودن.
     */
    private static function detectSuspicion(array $applicant, ?array $property, int $decile): array
    {
        $flags = [];

        // ۱. روستا + شهر بزرگ
        if ($property && !empty(trim((string)($property['village'] ?? '')))) {
            $city = trim((string)($property['city'] ?? ''));
            if (self::isBigCity($city)) {
                $flags[] = 'rural_address_mismatch';
            }
        }

        // ۲. تناقض دهک با پرونده‌های قبلی شخص
        $nationalCode = (string)($applicant['national_code'] ?? '');
        if ($nationalCode) {
            $previousDeciles = self::getPreviousDeciles($nationalCode, (int)$applicant['id']);
            if (!empty($previousDeciles)) {
                $maxPrev = max($previousDeciles);
                if ($maxPrev - $decile >= 3) {
                    $flags[] = 'decile_history_conflict';
                }
            }
        }

        // ۳. دهک پایین + آدرس کامل شهری (مشکوک)
        if ($decile <= 2 && $property && empty(trim((string)($property['village'] ?? '')))) {
            $city = trim((string)($property['city'] ?? ''));
            if (self::isBigCity($city)) {
                $flags[] = 'low_decile_urban';
            }
        }

        return $flags;
    }

    /**
     * دریافت دهک‌های قبلی شخص (برای تشخیص تناقض).
     */
    private static function getPreviousDeciles(string $nationalCode, int $excludeApplicantId): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT a.income_decile FROM applicants a
                 JOIN persons p ON p.id = a.person_id
                 WHERE p.national_code = :nc
                   AND a.id != :excl
                   AND a.income_decile IS NOT NULL
                   AND a.income_decile BETWEEN 1 AND 10"
            );
            $stmt->execute(['nc' => $nationalCode, 'excl' => $excludeApplicantId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function isBigCity(string $city): bool
    {
        $city = trim($city);
        if ($city === '') return false;
        return in_array($city, self::BIG_CITIES, true);
    }

    private static function fallback(): array
    {
        return [
            'amount' => self::fallbackAmount(),
            'group' => null,
            'is_rural' => false,
            'is_suspicious' => false,
            'suspicion_flags' => [],
            'fallback_used' => true,
            'reason' => 'محاسبهٔ گروه ممکن نشد — مبلغ پیش‌فرض',
        ];
    }

    private static function fallbackAmount(): int
    {
        return (int)setting('fee_default_amount', '500000');
    }

    /**
     * محاسبهٔ مبلغ نهایی پس از کسر خدمات انجام‌نشده (با تیک مدیر).
     *
     * @param int $paymentId
     * @return int مبلغ نهایی (با رعایت حداقل ۱۰٪)
     */
    public static function calculateFinalAmount(int $paymentId): int
    {
        $tickModel = new PaymentServiceTick();
        $sumDone = $tickModel->sumDone($paymentId);

        $payment = (new Payment())->findById($paymentId);
        if (!$payment) return $sumDone;

        $originalAmount = (int)($payment['original_amount'] ?? $payment['amount'] ?? 0);
        $minPercent = (int)setting('fee_min_amount_percent', '10');
        $minAmount = (int)($originalAmount * $minPercent / 100);

        return max($sumDone, $minAmount);
    }

    /**
     * آیا تخفیف نیاز به دلیل اجباری دارد؟
     * اگر تعداد تیک‌های برداشته‌شده > fee_max_discount_without_reason
     */
    public static function discountRequiresReason(int $paymentId): bool
    {
        $tickModel = new PaymentServiceTick();
        $untickedCount = $tickModel->countUnticked($paymentId);
        $maxWithoutReason = (int)setting('fee_max_discount_without_reason', '2');
        return $untickedCount > $maxWithoutReason;
    }

    /**
     * آیا تخفیف از حد مجاز (۵۰٪ یاfee_max_discount_with_reason) بیشتر است؟
     */
    public static function isDiscountExcessive(int $paymentId): bool
    {
        $tickModel = new PaymentServiceTick();
        $sumDone = $tickModel->sumDone($paymentId);
        $sumAll = $tickModel->sumAll($paymentId);
        if ($sumAll <= 0) return false;
        $discountPercent = (1 - ($sumDone / $sumAll)) * 100;
        $maxWithReason = (int)setting('fee_max_discount_with_reason', '50');
        return $discountPercent > $maxWithReason;
    }
}
