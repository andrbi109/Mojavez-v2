<?php
$formData = $formData ?? [];
$errors = $errors ?? [];
$editMode = $editMode ?? false;
$user = $user ?? null;
// در حالت ویرایش، مقادیر فعلی کاربر به‌عنوان پیش‌فرض فرم نمایش داده شوند
if ($editMode && $user) {
    $formData = array_merge([
        'username' => $user['username'],
        'display_name' => $user['display_name'],
        'role' => $user['role'],
    ], array_filter($formData, fn($v) => $v !== '' && $v !== null));
}
?>
<div class="card p-3 p-md-4">
    <h4 class="fw-bold mb-4"><?= $editMode ? '✏️ ویرایش کاربر' : '➕ ایجاد کاربر جدید' ?></h4>
    <form method="post" action="<?= $editMode ? BASE_URL . 'admin/users/update/' . $user['id'] : BASE_URL . 'admin/users/store' ?>">
        <?= Session::csrfField() ?>
        <div class="mb-3">
            <label class="form-label">نام کاربری <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" name="username"
                   value="<?= htmlspecialchars($formData['username'] ?? '') ?>" autocomplete="off" required>
            <?php if (!empty($errors['username'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['username']) ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">نام نمایشی</label>
            <input type="text" class="form-control" name="display_name" value="<?= htmlspecialchars($formData['display_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">رمز عبور <?= $editMode ? '(خالی بگذارید اگر تغییر نمی‌کند)' : '' ?><?= $editMode ? '' : ' <span class="text-danger">*</span>' ?></label>
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" name="password"
                   minlength="8" autocomplete="new-password" <?= $editMode ? '' : 'required' ?>>
            <div class="form-text">حداقل ۸ کاراکتر؛ ترکیبی از حروف و اعداد توصیه می‌شود.</div>
            <?php if (!empty($errors['password'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['password']) ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">نقش <span class="text-danger">*</span></label>
            <select class="form-select" name="role" required>
                <option value="expert" <?= (($formData['role'] ?? '') === 'expert') ? 'selected' : '' ?>>کارشناس صنایع دستی</option>
                <option value="document_uploader" <?= (($formData['role'] ?? '') === 'document_uploader') ? 'selected' : '' ?>>بارگذار مدارک</option>
                <option value="admin" <?= (($formData['role'] ?? '') === 'admin') ? 'selected' : '' ?>>مدیر کل</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">💾 ذخیره</button>
        <a href="<?= BASE_URL ?>admin/users" class="btn btn-outline-secondary">انصراف</a>
    </form>
</div>
