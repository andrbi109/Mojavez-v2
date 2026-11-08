<?php
/**
 * کنترلر پنل مدیریت نرخ‌نامه (FeeTariffController)
 * ---------------------------------------------------
 * مدیر می‌تواند:
 *   - تنظیمات کلی نرخ‌نامه را فعال/غیرفعال کند
 *   - گروه‌های قیمتی را CRUD کند
 *   - مبالغ آیتم‌ها را ویرایش کند
 *   - پرونده‌های پرداخت را با تیک خدمات تأیید کند
 */
class FeeTariffController extends Controller
{
    private FeeGroup $groupModel;
    private FeeGroupItem $itemModel;

    public function __construct()
    {
        // نکته: کنترلر پایه constructor ندارد، لذا parent::__construct() فراخوانی نمی‌شود
        $this->groupModel = new FeeGroup();
        $this->itemModel = new FeeGroupItem();
    }

    /**
     * GET /admin/fee-tariff — داشبورد نرخ‌نامه
     */
    public function index(): void
    {
        $this->requireRole(['admin']);
        $groups = $this->groupModel->all();
        $groupedItems = $this->itemModel->groupedByFeeGroup();

        $this->render('admin/fee_tariff', [
            'title' => 'نرخ‌نامه و تعرفه‌ها',
            'groups' => $groups,
            'groupedItems' => $groupedItems,
            'settings' => [
                'fee_tariff_active' => setting('fee_tariff_active', '1'),
                'fee_tariff_valid_until' => setting('fee_tariff_valid_until', ''),
                'fee_rural_auto_eligible' => setting('fee_rural_auto_eligible', '1'),
                'fee_modal_enabled' => setting('fee_modal_enabled', '1'),
                'fee_fallback_group_code' => setting('fee_fallback_group_code', 'C'),
                'fee_min_amount_percent' => setting('fee_min_amount_percent', '10'),
                'fee_max_discount_without_reason' => setting('fee_max_discount_without_reason', '2'),
                'fee_max_discount_with_reason' => setting('fee_max_discount_with_reason', '50'),
            ],
        ], 'admin_layout');
    }

    /**
     * POST /admin/fee-tariff/settings — ذخیرهٔ تنظیمات کلی
     */
    public function saveSettings(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/fee-tariff');
        $this->verifyCsrf();

        $setModel = new Setting();
        $setModel->setMany([
            'fee_tariff_active' => $this->input('fee_tariff_active', '0') === '1' ? '1' : '0',
            'fee_tariff_valid_until' => (string)$this->input('fee_tariff_valid_until', ''),
            'fee_rural_auto_eligible' => $this->input('fee_rural_auto_eligible', '0') === '1' ? '1' : '0',
            'fee_modal_enabled' => $this->input('fee_modal_enabled', '0') === '1' ? '1' : '0',
            'fee_fallback_group_code' => (string)$this->input('fee_fallback_group_code', 'C'),
            'fee_min_amount_percent' => (string)max(1, min(50, (int)$this->input('fee_min_amount_percent', '10'))),
            'fee_max_discount_without_reason' => (string)max(0, min(12, (int)$this->input('fee_max_discount_without_reason', '2'))),
            'fee_max_discount_with_reason' => (string)max(10, min(100, (int)$this->input('fee_max_discount_with_reason', '50'))),
        ]);

        Session::set('success', 'تنظیمات نرخ‌نامه ذخیره شد.');
        $this->redirect('/admin/fee-tariff');
    }

    /**
     * POST /admin/fee-tariff/group/save — ایجاد یا ویرایش گروه
     */
    public function groupSave(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/fee-tariff');
        $this->verifyCsrf();

        $id = (int)$this->input('id', 0);
        $data = [
            'code' => (string)$this->input('code', ''),
            'name' => (string)$this->input('name', ''),
            'icon' => (string)$this->input('icon', ''),
            'decile_min' => (int)$this->input('decile_min', 1),
            'decile_max' => (int)$this->input('decile_max', 2),
            'is_rural_eligible' => $this->input('is_rural_eligible', '0') === '1' ? 1 : 0,
            'total_amount' => (int)$this->input('total_amount', 0),
            'message_title' => (string)$this->input('message_title', ''),
            'message_body' => (string)$this->input('message_body', ''),
            'is_active' => $this->input('is_active', '0') === '1' ? 1 : 0,
            'sort_order' => (int)$this->input('sort_order', 50),
        ];

        // ولیدیشن
        if ($data['code'] === '' || $data['name'] === '') {
            Session::set('errors', ['کد و نام گروه الزامی است.']);
            $this->redirect('/admin/fee-tariff');
        }
        if ($data['decile_min'] < 1 || $data['decile_max'] > 10 || $data['decile_min'] > $data['decile_max']) {
            Session::set('errors', ['محدودهٔ دهک نامعتبر است (۱ تا ۱۰).']);
            $this->redirect('/admin/fee-tariff');
        }
        if ($data['total_amount'] < 0) {
            Session::set('errors', ['مبلغ گروه نامعتبر است.']);
            $this->redirect('/admin/fee-tariff');
        }

        if ($id > 0) {
            $this->groupModel->update($id, $data);
            Session::set('success', 'گروه با موفقیت ویرایش شد.');
        } else {
            // بررسی یکتایی کد
            if ($this->groupModel->findByCode($data['code'])) {
                Session::set('errors', ['کد گروه تکراری است.']);
                $this->redirect('/admin/fee-tariff');
            }
            $this->groupModel->create($data);
            Session::set('success', 'گروه با موفقیت ایجاد شد.');
        }
        $this->redirect('/admin/fee-tariff');
    }

    /**
     * POST /admin/fee-tariff/group/{id}/delete — حذف گروه
     */
    public function groupDelete(int $id): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/fee-tariff');
        $this->verifyCsrf();

        $group = $this->groupModel->findById($id);
        if (!$group) {
            Session::set('errors', ['گروه یافت نشد.']);
            $this->redirect('/admin/fee-tariff');
        }

        // جلوگیری از حذف گروهی که پرداخت مرتبط دارد
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM payments WHERE fee_group_id = :gid");
        $stmt->execute(['gid' => $id]);
        $count = (int)$stmt->fetchColumn();
        if ($count > 0) {
            Session::set('errors', ["این گروه {$count} پرداخت مرتبط دارد و قابل حذف نیست. می‌توانید آن را غیرفعال کنید."]);
            $this->redirect('/admin/fee-tariff');
        }

        $this->groupModel->delete($id);
        Session::set('success', 'گروه حذف شد.');
        $this->redirect('/admin/fee-tariff');
    }

    /**
     * POST /admin/fee-tariff/items/save — ذخیرهٔ مبالغ آیتم‌های یک گروه
     */
    public function itemsSave(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/fee-tariff');
        $this->verifyCsrf();

        $groupId = (int)$this->input('group_id', 0);
        $group = $this->groupModel->findById($groupId);
        if (!$group) {
            Session::set('errors', ['گروه یافت نشد.']);
            $this->redirect('/admin/fee-tariff');
        }

        $items = $this->input('items', []);
        if (!is_array($items)) $items = [];

        foreach ($items as $key => $data) {
            $label = (string)($data['label'] ?? '');
            $amount = (int)($data['amount'] ?? 0);
            $sort = (int)($data['sort'] ?? 0);
            $notes = (string)($data['notes'] ?? '');
            if ($key !== '' && $label !== '') {
                $this->itemModel->upsert($groupId, $key, $label, max(0, $amount), $sort, $notes ?: null);
            }
        }

        Session::set('success', 'مبالغ آیتم‌ها ذخیره شد.');
        $this->redirect('/admin/fee-tariff#group-' . $groupId);
    }

    /**
     * POST /admin/payment/tick — تغییر وضعیت تیک یک خدمت
     */
    public function toggleTick(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'متد مجاز نیست.']);
            return;
        }
        $this->verifyCsrf();

        try {
            $tickId = (int)$this->input('tick_id', 0);
            $done = $this->input('done', '1') === '1';
            $reason = (string)$this->input('reason', '');

            if ($tickId <= 0) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'شناسهٔ تیک نامعتبر است.']);
                return;
            }

            $tickModel = new PaymentServiceTick();

            // ذخیرهٔ tickId قبل از تغییر برای audit
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM payment_service_ticks WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $tickId]);
            $tick = $stmt->fetch();

            if (!$tick) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'تیک یافت نشد.']);
                return;
            }

            $tickModel->setDone($tickId, $done, $done ? null : $reason);

            // محاسبهٔ مبلغ نهایی جدید
            $newFinal = FeeCalculatorService::calculateFinalAmount((int)$tick['payment_id']);

            // ذخیرهٔ final_amount در payments
            $stmt = $db->prepare("UPDATE payments SET final_amount = :fa WHERE id = :pid");
            $stmt->execute(['fa' => $newFinal, 'pid' => $tick['payment_id']]);

            // اگر دلیل تخفیف داده شده، در payments ذخیره شود
            if (!$done && $reason) {
                $stmt = $db->prepare("UPDATE payments SET discount_reason = :dr WHERE id = :pid");
                $stmt->execute(['dr' => mb_substr($reason, 0, 500), 'pid' => $tick['payment_id']]);
            }

            // audit log (با try/catch چون ممکن است در دیتابیس قدیمی نباشد)
            try {
                $auditModel = new PaymentAuditLog();
                $auditModel->log(
                    (int)$tick['payment_id'],
                    (int)($_SESSION['admin_id'] ?? 0),
                    'tick_changed',
                    'is_done',
                    $tick['is_done'],
                    $done ? '1' : '0',
                    'خدمت: ' . $tick['item_label'] . ($reason ? ' — دلیل: ' . $reason : '')
                );
            } catch (\Throwable $e) {
                // audit log شکست خورد ولی عملیات اصلی موفق بود — ادامه می‌دهیم
            }

            // پاسخ JSON برای AJAX
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'tick_id' => $tickId,
                'is_done' => $done,
                'final_amount' => $newFinal,
                'final_amount_formatted' => number_format($newFinal),
            ]);
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'خطای سرور: ' . $e->getMessage(),
                'code' => 'server_error',
            ]);
        }
    }

    /**
     * رندر کردن فرم گروه (در داخل view فراخوانی می‌شود).
     * این متد public است چون از داخل view صدا زده می‌شود.
     */
    public function renderGroupForm(array $g): void
    {
        $g = array_merge([
            'code' => '', 'name' => '', 'icon' => '', 'decile_min' => 1, 'decile_max' => 2,
            'is_rural_eligible' => 0, 'total_amount' => 0, 'message_title' => '',
            'message_body' => '', 'is_active' => 1, 'sort_order' => 50,
        ], $g);
        ?>
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label">کد گروه <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="code" value="<?= htmlspecialchars($g['code']) ?>" maxlength="20" required <?= !empty($g['id']) ? 'readonly' : '' ?> placeholder="A">
                <small class="text-muted">یک حرف لاتین (مثلاً A,B,C,D,E)</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">نام گروه <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($g['name']) ?>" required placeholder="گروه الف: حمایتی">
            </div>
            <div class="col-md-2">
                <label class="form-label">آیکون (اموجی)</label>
                <input type="text" class="form-control" name="icon" value="<?= htmlspecialchars($g['icon']) ?>" maxlength="30" placeholder="🌱">
            </div>
            <div class="col-md-2">
                <label class="form-label">ترتیب</label>
                <input type="number" class="form-control" name="sort_order" value="<?= (int)$g['sort_order'] ?>" min="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">دهک از <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="decile_min" value="<?= (int)$g['decile_min'] ?>" min="1" max="10" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">دهک تا <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="decile_max" value="<?= (int)$g['decile_max'] ?>" min="1" max="10" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">مبلغ کل (تومان) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="total_amount" value="<?= (int)$g['total_amount'] ?>" min="0" required>
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="is_rural_eligible" value="1" id="rural_<?= (int)($g['id'] ?? 0) ?>" <?= (int)$g['is_rural_eligible'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rural_<?= (int)($g['id'] ?? 0) ?>">روستاییان در این گروه</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">عنوان پیام مودال</label>
                <input type="text" class="form-control" name="message_title" value="<?= htmlspecialchars($g['message_title']) ?>" placeholder="پیام ما به شما">
            </div>
            <div class="col-12">
                <label class="form-label">متن پیام مودال</label>
                <textarea class="form-control" name="message_body" rows="4" placeholder="متن حمایتی که پس از کد رهگیری نمایش داده می‌شود"><?= htmlspecialchars($g['message_body']) ?></textarea>
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_<?= (int)($g['id'] ?? 0) ?>" <?= (int)$g['is_active'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active_<?= (int)($g['id'] ?? 0) ?>">فعال</label>
                </div>
            </div>
        </div>
        <?php
    }
}
