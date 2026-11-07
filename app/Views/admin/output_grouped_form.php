<div class="card p-4">
    <h4 class="fw-bold mb-4">گزارش گروه‌بندی‌شده</h4>
    <form method="post" action="<?= BASE_URL ?>admin/output/grouped">
        <?= Session::csrfField() ?>
        <div class="mb-3">
            <label class="form-label">گروه‌بندی بر اساس:</label>
            <select class="form-select" name="group_by">
                <option value="province">استان</option>
                <option value="city">شهر</option>
                <option value="village">روستا</option>
                <option value="license_title">رشته</option>
                <option value="registration_date">تاریخ ثبت‌نام</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">مشاهده گزارش</button>
    </form>
</div>