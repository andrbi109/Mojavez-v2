<div class="card p-4">
    <h4 class="fw-bold mb-4">گزارش کلی (خروجی Excel / PDF)</h4>
    <form method="get" action="<?= BASE_URL ?>admin/report/csv" target="_blank">
        <div class="row g-3">
            <div class="col-md-2">
                <input class="form-control" name="license_title" placeholder="رشته" value="<?= htmlspecialchars($_GET['license_title'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input class="form-control" name="province" placeholder="استان" value="<?= htmlspecialchars($_GET['province'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input class="form-control" name="city" placeholder="شهر" value="<?= htmlspecialchars($_GET['city'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input class="form-control" name="village" placeholder="روستا" value="<?= htmlspecialchars($_GET['village'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">وضعیت</option>
                    <?php foreach (['پیش‌نویس','تکمیل اطلاعات','احراز نشانی','ثبت درخواست صدور','بارگذاری مدارک به سازمان','تأیید اولیه','صدور مجوز','لغو'] as $st): ?>
                        <option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-success">خروجی CSV</button>
            </div>
        </div>
    </form>
    <form method="get" action="<?= BASE_URL ?>admin/report/pdf" target="_blank" class="mt-3">
        <input type="hidden" name="license_title" id="pdf_license" value="<?= htmlspecialchars($_GET['license_title'] ?? '') ?>">
        <input type="hidden" name="province" id="pdf_province" value="<?= htmlspecialchars($_GET['province'] ?? '') ?>">
        <input type="hidden" name="city" id="pdf_city" value="<?= htmlspecialchars($_GET['city'] ?? '') ?>">
        <input type="hidden" name="village" id="pdf_village" value="<?= htmlspecialchars($_GET['village'] ?? '') ?>">
        <input type="hidden" name="status" id="pdf_status" value="<?= htmlspecialchars($_GET['status'] ?? '') ?>">
        <button type="submit" class="btn btn-info">خروجی PDF</button>
    </form>
</div>
<script>
document.querySelectorAll('input[name],select[name]').forEach(el => {
    el.addEventListener('input', function() {
        const pdfEl = document.getElementById('pdf_' + this.name);
        if (pdfEl) pdfEl.value = this.value;
    });
});
</script>