<?php $base=rtrim(BASE_URL,'/').'/';?>
<div class="card">
    <div class="card-header fw-bold"><?= icon('search','me-1',18) ?> پیگیری درخواست</div>
    <div class="card-body">
        <p class="text-muted small mb-3">برای مشاهدهٔ وضعیت پرونده، کد رهگیری خود را وارد کنید:</p>
        <form action="<?= $base ?>tracking" method="get" class="row g-2">
            <div class="col-md-8">
                <input type="text" class="form-control form-control-lg" name="code" placeholder="HG-XXXXXXXX" dir="ltr" pattern="HG-[A-Z0-9]{8}" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-lg w-100"><?= icon('search','',16) ?> پیگیری</button>
            </div>
        </form>
    </div>
</div>
