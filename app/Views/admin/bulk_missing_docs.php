<?php $base=rtrim(BASE_URL,'/').'/';$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$apps=$applicants??[];?>
<div class="container-fluid py-4">
    <h3 class="fw-bold mb-3"><?= icon('folder','me-2',22) ?> کاربران نیازمند مدرک</h3>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>کد رهگیری</th><th>نام</th><th>کد ملی</th><th>خدمت</th><th>تعداد مدرک</th><th>وضعیت</th><th>اقدام</th></tr></thead>
                    <tbody>
                        <?php foreach($apps as $a):
                            $dc=(int)($a['doc_count']??0);
                            if($dc>=8){$st='🟢 کامل';$cls='success';}
                            elseif($dc>0){$st='🟡 ناقص';$cls='warning';}
                            else{$st='🔴 بدون مدرک';$cls='danger';}
                        ?>
                            <tr class="table-<?= $cls ?>">
                                <td><code dir="ltr"><?= $e($a['tracking_code']) ?></code></td>
                                <td class="small"><?= $e($a['first_name'].' '.$a['last_name']) ?></td>
                                <td class="small"><code dir="ltr"><?= $e($a['national_code']) ?></code></td>
                                <td class="small"><?= $e($a['service_title']??'—') ?></td>
                                <td><?= $dc ?></td>
                                <td><span class="badge bg-<?= $cls ?>"><?= $st ?></span></td>
                                <td>
                                    <a href="<?= $base ?>admin/bulk-import/documents/<?= rawurlencode($a['tracking_code']) ?>" class="btn btn-sm btn-outline-primary"><?= icon('upload','',14) ?> بارگذاری</a>
                                    <a href="<?= $base ?>admin/view/<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= icon('eye','',14) ?></a>
                                </td>
                            </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
