<?php
/**
 * نمای فهرست کاربران پنل
 * نقش‌ها با برچسب فارسی و رنگ مجزا نمایش داده می‌شوند.
 * حذف کاربر با فرم POST + توکن CSRF انجام می‌شود (نه لینک GET).
 */
$roleLabels = [
    'admin' => ['مدیر کل', 'danger'],
    'expert' => ['کارشناس', 'info'],
    'document_uploader' => ['متصدی مدارک', 'secondary'],
];
$me = Session::get('admin_id', 0);
?>
<div class="card p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="fw-bold mb-0">👥 کاربران سامانه</h4>
        <a href="<?= BASE_URL ?>admin/users/create" class="btn btn-primary btn-sm">➕ کاربر جدید</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>نام کاربری</th><th>نام نمایشی</th><th>نقش</th><th>تاریخ ایجاد</th><th>عملیات</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <?php [$roleLabel, $roleColor] = $roleLabels[$user['role']] ?? [$user['role'], 'secondary']; ?>
                <tr>
                    <td><?= (int)$user['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($user['username']) ?>
                        <?php if ((int)$user['id'] === (int)$me): ?><span class="badge bg-light text-dark border">شما</span><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($user['display_name'] ?: '—') ?></td>
                    <td><span class="badge bg-<?= $roleColor ?>"><?= $roleLabel ?></span></td>
                    <td class="small text-muted"><?= persianDate((string)$user['created_at']) ?></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>admin/users/edit/<?= (int)$user['id'] ?>" class="btn btn-sm btn-outline-warning">✏️ ویرایش</a>
                        <form method="post" action="<?= BASE_URL ?>admin/users/delete" class="d-inline"
                              onsubmit="return confirm('آیا از حذف کاربر «<?= htmlspecialchars(addslashes($user['username'])) ?>» مطمئن هستید؟');">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" <?= ((int)$user['id'] === 1 || (int)$user['id'] === (int)$me) ? 'disabled title="غیرقابل حذف"' : '' ?>>🗑️ حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">کاربری ثبت نشده است.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="alert alert-light border small mb-0">
        <strong>راهنمای نقش‌ها:</strong>
        «مدیر کل» به همهٔ بخش‌ها دسترسی دارد؛ «کارشناس» فقط پرونده‌های در انتظار بررسی (احراز نشانی / تأیید اولیه) و درخواست‌های کد پستی را می‌بیند؛
        «متصدی مدارک» فقط پرونده‌های مرحلهٔ بارگذاری مدارک به سازمان را مدیریت می‌کند.
    </div>
</div>
