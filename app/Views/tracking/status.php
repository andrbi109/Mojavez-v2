<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- کارت وضعیت -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <h4 class="fw-bold">وضعیت: <span class="badge fs-5 px-4 py-2" style="background: var(--primary);"><?= htmlspecialchars($applicant['current_status']) ?></span></h4>
                <p><strong>کد رهگیری:</strong> <?= htmlspecialchars($applicant['tracking_code']) ?></p>
                <p><strong>نام:</strong> <?= htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']) ?></p>
                <?php if ($applicant['priority']): ?><span class="priority-badge">⭐ اولویت</span><?php endif; ?>
            </div>
        </div>
        <!-- تایم‌لاین -->
        <div class="card mb-4">
            <div class="card-header">تاریخچه رویدادها</div>
            <div class="card-body">
                <ul class="timeline">
                    <?php foreach ($statusLogs as $log): ?>
                        <?php $class = (strpos($log['status_name'], 'لغو') !== false) ? 'cancel' : ((strpos($log['status_name'], 'صدور') !== false || strpos($log['status_name'], 'تأیید') !== false) ? 'success' : ''); ?>
                        <li class="timeline-item <?= $class ?>">
                            <div class="time"><?= persianDate($log['created_at']) ?></div>
                            <div class="description"><strong><?= htmlspecialchars($log['status_name']) ?></strong></div>
                            <?php if (!empty($log['description'])): ?>
                                <div class="text-muted small"><?= htmlspecialchars($log['description']) ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- پیام‌های بازخورد -->
        <?php if (!empty($changePostalSuccess)): ?>
            <div class="alert alert-success text-center py-2"><?= htmlspecialchars($changePostalSuccess) ?></div>
        <?php endif; ?>
        <?php if (!empty($changePostalError)): ?>
            <div class="alert alert-danger text-center py-2"><?= htmlspecialchars($changePostalError) ?></div>
        <?php endif; ?>

        <!-- اگر درخواست در انتظار بررسی وجود دارد، اطلاع‌رسانی شود -->
        <?php if (!empty($pendingPostal)): ?>
            <div class="alert alert-warning text-center py-2">
                ⏳ یک درخواست تغییر کد پستی
                (<code><?= htmlspecialchars($pendingPostal['old_postal_code']) ?></code> ← <code><?= htmlspecialchars($pendingPostal['new_postal_code']) ?></code>)
                در انتظار بررسی مدیر است. تا زمان بررسی، امکان ثبت درخواست جدید وجود ندارد.
            </div>
        <?php endif; ?>

        <?php if (!empty($revisionError)): ?>
            <div class="alert alert-danger text-center py-2"><?= htmlspecialchars($revisionError) ?></div>
        <?php endif; ?>
        <?php if (!empty($revisionSuccess)): ?>
            <div class="alert alert-success text-center py-2"><?= htmlspecialchars($revisionSuccess) ?></div>
        <?php endif; ?>
        <?php if (!empty($pendingRevision)): ?>
            <div class="alert alert-warning text-center py-2">
                ⏳ یک درخواست بازبینی پرونده در انتظار بررسی مدیر است (ثبت: <?= persianDate((string)$pendingRevision['created_at']) ?>).
            </div>
        <?php endif; ?>

        <!-- دکمه‌های عملیات (نوار ابزار) -->
        <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#postalModal" <?= !empty($pendingPostal) ? 'disabled title="درخواست در انتظار بررسی دارید"' : '' ?>>
                <?= icon('mail', '', 16) ?> درخواست تغییر کد پستی
            </button>
            <a href="<?= BASE_URL ?>tracking/revision/<?= htmlspecialchars(rawurlencode($applicant['tracking_code'])) ?>"
               class="btn btn-outline-success <?= !empty($pendingRevision) ? 'disabled' : '' ?>"
               <?= !empty($pendingRevision) ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                <?= icon('edit', '', 16) ?> بازبینی اطلاعات / افزودن مدرک
            </a>
            <a href="<?= BASE_URL ?>tracking/print/<?= htmlspecialchars(rawurlencode($applicant['tracking_code'])) ?>" target="_blank" class="btn btn-outline-primary">
                <?= icon('print', '', 16) ?> مشاهده نسخهٔ چاپی
            </a>
        </div>

        <!-- مودال تغییر کد پستی -->
        <div class="modal fade" id="postalModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background: var(--primary); color: #fff;">
                        <h5 class="modal-title">درخواست تغییر کد پستی</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post" action="<?= BASE_URL ?>tracking/change-postal-code">
                        <?= Session::csrfField() ?>
                        <div class="modal-body">
                            <input type="hidden" name="tracking_code" value="<?= htmlspecialchars($applicant['tracking_code']) ?>">
                            <label class="form-label">کد پستی جدید (۱۰ رقمی)</label>
                            <input type="text" class="form-control" name="new_postal_code" pattern="\d{10}" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-warning">ثبت درخواست</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============ پرداخت هزینهٔ ثبت‌نام (فاز ۳) ============ -->
        <?php if (!empty($paymentRequired)): ?>
        <div class="card mb-4" style="border:2px solid var(--saffron);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= icon('money', 'me-1') ?> پرداخت هزینهٔ ثبت‌نام</span>
                <?php if (($feeAmount ?? 0) > 0): ?>
                    <span class="badge bg-warning fs-6"><?= number_format((int)$feeAmount) ?> تومان</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($paymentError)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($paymentError) ?></div>
                <?php endif; ?>
                <?php if (!empty($paymentSuccess)): ?>
                    <div class="alert alert-success py-2 small"><?= htmlspecialchars($paymentSuccess) ?></div>
                <?php endif; ?>

                <?php
                $pay = $latestPayment ?? null;
                $payBadge = $pay ? match ($pay['status']) {
                    'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark',
                } : null;
                ?>
                <?php if ($pay && in_array($pay['status'], ['در انتظار بررسی', 'تأیید شده'], true)): ?>
                    <div class="alert alert-<?= $pay['status'] === 'تأیید شده' ? 'success' : 'info' ?> py-2 small mb-0">
                        <strong>رسید پرداخت شما <?= htmlspecialchars($pay['status']) ?> است.</strong>
                        مبلغ <?= number_format((int)$pay['amount']) ?> تومان
                        <?php if ($pay['status'] === 'در انتظار بررسی'): ?>— پس از بررسی مدیر، نتیجه در تایم‌لاین پرونده اعلام می‌شود.<?php else: ?>— پرداخت شما تأیید شد. ✔<?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if ($pay && $pay['status'] === 'رد شده'): ?>
                        <div class="alert alert-danger py-2 small">
                            رسید قبلی شما <strong>رد شد</strong><?= !empty($pay['reject_reason']) ? ' — دلیل: ' . htmlspecialchars($pay['reject_reason']) : '' ?>.
                            می‌توانید رسید اصلاح‌شده را مجدداً ثبت کنید.
                        </div>
                    <?php endif; ?>

                    <!-- کانال‌های پرداخت -->
                    <h6 class="fw-bold mb-2"><?= icon('info', '', 16) ?> مراحل پرداخت:</h6>
                    <ol class="small text-muted mb-3">
                        <li>مبلغ را از یکی از کانال‌های زیر واریز کنید:</li>
                    </ol>
                    <div class="row g-2 mb-3">
                        <?php foreach (($paymentChannels ?? []) as $ch): ?>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label"><?= icon('money', '', 14) ?> <?= htmlspecialchars($ch['title']) ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($ch['channel_type']) ?></span></div>
                                <div class="info-value small" dir="ltr" style="text-align:right"><?= htmlspecialchars($ch['account_info'] ?? '') ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($paymentChannels)): ?><p class="text-muted small">کانالی فعال نیست — به مدیر سامانه پیام دهید.</p><?php endif; ?>
                    </div>
                    <ol class="small text-muted mb-3" start="2">
                        <li>سپس اطلاعات واریز و تصویر رسید را در فرم زیر ثبت کنید.</li>
                    </ol>

                    <?php if (!empty($paymentChannels)): ?>
                    <form method="post" action="<?= BASE_URL ?>tracking/payments/submit" enctype="multipart/form-data">
                        <?= Session::csrfField() ?>
                        <input type="hidden" name="tracking_code" value="<?= htmlspecialchars($applicant['tracking_code']) ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">کانال پرداخت <span class="text-danger">*</span></label>
                                <select class="form-select" name="payment_channel_id" required>
                                    <option value="">انتخاب کنید...</option>
                                    <?php foreach ($paymentChannels as $ch): ?>
                                        <option value="<?= (int)$ch['id'] ?>"><?= htmlspecialchars($ch['title'] . ' — ' . $ch['channel_type']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">مبلغ واریزی (تومان) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="amount" min="1" required inputmode="numeric"
                                       value="<?= (int)($feeAmount ?? 0) ?: '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">کد پیگیری بانکی (اختیاری)</label>
                                <input type="text" class="form-control" name="reference_code" maxlength="50" dir="ltr">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تاریخ واریز <span class="text-danger">*</span></label>
                                <div class="row g-1">
                                    <div class="col-4">
                                        <select class="form-select form-select-sm" name="paid_year" required>
                                            <option value="">سال</option>
                                            <?php $cy = currentPersianYear(); foreach ([$cy - 1, $cy, $cy + 1] as $y): ?>
                                                <option value="<?= $y ?>" <?= $y === $cy ? 'selected' : '' ?>><?= $y ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select class="form-select form-select-sm" name="paid_month" required>
                                            <option value="">ماه</option>
                                            <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                                            foreach ($months as $mi => $mn): ?>
                                                <option value="<?= $mi + 1 ?>"><?= $mn ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select class="form-select form-select-sm" name="paid_day" required>
                                            <option value="">روز</option>
                                            <?php for ($d = 1; $d <= 31; $d++): ?><option value="<?= $d ?>"><?= $d ?></option><?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">تصویر رسید پرداخت <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf" required>
                                <div class="form-text">jpg / png / pdf — حداکثر ۲ مگابایت</div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gold mt-3 w-100"><?= icon('upload', '', 16) ?> ثبت رسید پرداخت</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============ تصویر مجوز صادرشده (فاز ۶) ============ -->
        <?php if (!empty($licenseFile)): ?>
        <div class="card mb-4" style="border:2px solid var(--persian-green);">
            <div class="card-header d-flex justify-content-between align-items-center" style="color:var(--persian-green)">
                <span><?= icon('award', 'me-1') ?> تصویر مجوز شما</span>
                <span class="badge bg-success"><?= htmlspecialchars($applicant['current_status']) ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($licenseMessage)): ?>
                    <div class="alert alert-info py-2 small"><?= htmlspecialchars($licenseMessage) ?></div>
                <?php endif; ?>

                <?php if (!empty($licenseValidToken)): ?>
                    <!-- لینک معتبر جاری -->
                    <div class="text-center">
                        <p class="small text-muted mb-2">
                            لینک دسترسی شما فعال است و تا
                            <strong><?= persianDate((string)$licenseValidToken['expires_at']) ?></strong>
                            اعتبار دارد (پس از آن منقضی می‌شود).
                        </p>
                        <a href="<?= BASE_URL ?>license/view/<?= htmlspecialchars($licenseValidToken['access_token']) ?>"
                           target="_blank" class="btn btn-success btn-lg px-4"><?= icon('award', '', 18) ?> مشاهدهٔ تصویر مجوز</a>
                    </div>

                <?php elseif ((int)$licenseFile['download_count'] === 0): ?>
                    <!-- دریافت اول: خودکار و رایگان -->
                    <div class="text-center">
                        <p class="small text-muted mb-2">
                            🎉 تبریک! مجوز شما صادر شده است. <strong>دریافت اول تصویر مجوز رایگان است.</strong>
                            پس از کلیک، لینک مشاهده به‌مدت ۷۲ ساعت فعال می‌شود.
                        </p>
                        <form method="post" action="<?= BASE_URL ?>tracking/license/first-access">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="tracking_code" value="<?= htmlspecialchars($applicant['tracking_code']) ?>">
                            <button type="submit" class="btn btn-gold btn-lg px-4"><?= icon('download', '', 18) ?> دریافت تصویر مجوز</button>
                        </form>
                    </div>

                <?php else: ?>
                    <!-- دریافت اول انجام شده — جریان دریافت مجدد -->
                    <?php
                    $req = $licenseReq ?? null;
                    $inProgress = $req && in_array($req['status'], ['در انتظار', 'در انتظار پرداخت'], true);
                    ?>
                    <p class="small text-muted">
                        شما یک‌بار تصویر مجوز را دریافت کرده‌اید
                        (<?= persianDate((string)($licenseFile['first_accessed_at'] ?? '')) ?>).
                        برای دریافت مجدد، لازم است درخواست ثبت کنید و پس از بررسی مدیر
                        <?= ((int)($reissueFee ?? 0) > 0) ? 'و پرداخت هزینهٔ تعیین‌شده، ' : '' ?>
                        لینک جدید صادر می‌شود.
                    </p>

                    <?php if ($req && $req['status'] === 'در انتظار'): ?>
                        <div class="alert alert-warning py-2 small mb-0">⏳ درخواست دریافت مجدد شما در انتظار بررسی مدیر است.</div>

                    <?php elseif ($req && $req['status'] === 'در انتظار پرداخت'): ?>
                        <?php
                        $paidPending = ($reissuePayment ?? null)
                            && in_array($reissuePayment['status'], ['در انتظار بررسی', 'تأیید شده'], true)
                            && (int)($reissuePayment['linked_request_id'] ?? 0) === (int)$req['id'];
                        ?>
                        <?php if ($paidPending): ?>
                            <div class="alert alert-info py-2 small mb-0">
                                رسید پرداخت شما <?= $reissuePayment['status'] === 'تأیید شده' ? 'تأیید شد ✔ — لینک دریافت به‌زودی فعال می‌شود.' : 'ثبت شده و در انتظار بررسی مدیر است.' ?>
                            </div>
                        <?php else: ?>
                        <div class="alert alert-warning py-2 small">
                            💳 درخواست شما تأیید شده و <strong>مشروط به پرداخت <?= number_format((int)$req['amount']) ?> تومان</strong> است.
                            مبلغ را به یکی از کانال‌های زیر واریز و رسید را ثبت کنید:
                        </div>
                        <div class="row g-2 mb-3">
                            <?php foreach (($paymentChannels ?? []) as $ch): ?>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label"><?= icon('money', '', 14) ?> <?= htmlspecialchars($ch['title']) ?></div>
                                    <div class="info-value small" dir="ltr" style="text-align:right"><?= htmlspecialchars($ch['account_info'] ?? '') ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="post" action="<?= BASE_URL ?>tracking/license/pay" enctype="multipart/form-data">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="tracking_code" value="<?= htmlspecialchars($applicant['tracking_code']) ?>">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">کانال پرداخت *</label>
                                    <select class="form-select form-select-sm" name="payment_channel_id" required>
                                        <option value="">انتخاب کنید...</option>
                                        <?php foreach (($paymentChannels ?? []) as $ch): ?>
                                            <option value="<?= (int)$ch['id'] ?>"><?= htmlspecialchars($ch['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">مبلغ واریزی (تومان) *</label>
                                    <input type="number" class="form-control form-control-sm" name="amount" min="<?= (int)$req['amount'] ?>" value="<?= (int)$req['amount'] ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">کد پیگیری</label>
                                    <input type="text" class="form-control form-control-sm" name="reference_code" maxlength="50" dir="ltr">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small mb-1">تاریخ واریز *</label>
                                    <div class="row g-1">
                                        <div class="col-4"><select class="form-select form-select-sm" name="paid_year" required><option value="">سال</option>
                                            <?php $cy = currentPersianYear(); foreach ([$cy - 1, $cy, $cy + 1] as $y): ?><option value="<?= $y ?>" <?= $y === $cy ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
                                        </select></div>
                                        <div class="col-4"><select class="form-select form-select-sm" name="paid_month" required><option value="">ماه</option>
                                            <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                                            foreach ($months as $mi => $mn): ?><option value="<?= $mi + 1 ?>"><?= $mn ?></option><?php endforeach; ?>
                                        </select></div>
                                        <div class="col-4"><select class="form-select form-select-sm" name="paid_day" required><option value="">روز</option>
                                            <?php for ($d = 1; $d <= 31; $d++): ?><option value="<?= $d ?>"><?= $d ?></option><?php endfor; ?>
                                        </select></div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">تصویر رسید *</label>
                                    <input type="file" class="form-control form-control-sm" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-gold btn-sm mt-3 w-100"><?= icon('upload', '', 15) ?> ثبت رسید پرداخت دریافت مجدد</button>
                        </form>
                        <?php endif; ?>

                    <?php else: ?>
                        <?php if ($req && $req['status'] === 'رد شده'): ?>
                            <div class="alert alert-danger py-2 small">
                                درخواست قبلی شما <strong>رد شد</strong><?= !empty($req['reject_reason']) ? ' — دلیل: ' . htmlspecialchars($req['reject_reason']) : '' ?>.
                            </div>
                        <?php elseif ($req && $req['status'] === 'منقضی'): ?>
                            <div class="alert alert-secondary py-2 small">لینک قبلی منقضی شد؛ برای دریافت مجدد درخواست ثبت کنید.</div>
                        <?php endif; ?>
                        <form method="post" action="<?= BASE_URL ?>tracking/license/request-access">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="tracking_code" value="<?= htmlspecialchars($applicant['tracking_code']) ?>">
                            <button type="submit" class="btn btn-outline-primary"><?= icon('send', '', 15) ?> ثبت درخواست دریافت مجدد تصویر مجوز</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============ ثبت پیامک دریافتی (فاز ۵) ============ -->
        <?php if (!empty($smsTemplates)): ?>
        <div class="card mb-4">
            <div class="card-header"><?= icon('phone', 'me-1') ?> ثبت پیامک دریافتی از سازمان</div>
            <div class="card-body">
                <p class="small text-muted">
                    اگر از سمت سازمان صادرکننده برای شما پیامکی دربارهٔ وضعیت پرونده ارسال شده است،
                    آن را اینجا ثبت کنید. <strong>پس از تأیید مدیر</strong>، وضعیت پروندهٔ شما به‌روز می‌شود.
                </p>

                <?php if (!empty($smsError)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($smsError) ?></div>
                <?php endif; ?>
                <?php if (!empty($smsSuccess)): ?>
                    <div class="alert alert-success py-2 small"><?= htmlspecialchars($smsSuccess) ?></div>
                <?php endif; ?>

                <?php if (!empty($pendingSms)): ?>
                    <div class="alert alert-warning py-2 small mb-0">
                        ⏳ پیامک «<?= htmlspecialchars($pendingSms['template_title'] ?? '') ?>» در انتظار بررسی مدیر است.
                        تا زمان بررسی، ثبت پیامک جدید ممکن نیست.
                    </div>
                <?php else: ?>
                <form method="post" action="<?= BASE_URL ?>tracking/sms/submit">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="tracking_code" value="<?= htmlspecialchars($applicant['tracking_code']) ?>">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">نوع پیامک <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="sms_template_id" id="smsTemplate" required onchange="smsTplChanged()">
                                <option value="">انتخاب کنید...</option>
                                <?php foreach ($smsTemplates as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" data-req="<?= (int)$t['requires_code'] ?>"
                                            data-sample="<?= htmlspecialchars($t['sample_text'] ?? '') ?>">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" id="smsSample"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">کد/شمارهٔ پیامک <span class="text-danger" id="smsCodeReq" style="display:none">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="sms_code" id="smsCode" maxlength="50" dir="ltr" placeholder="مثلاً: 1234567">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">متن پیامک (اختیاری)</label>
                            <textarea class="form-control form-control-sm" name="sms_text" rows="2" maxlength="1000" placeholder="خلاصهٔ متن پیامک را بنویسید..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">تاریخ دریافت پیامک <span class="text-danger">*</span></label>
                            <div class="row g-1">
                                <div class="col-4"><select class="form-select form-select-sm" name="sms_year" required><option value="">سال</option>
                                    <?php $cy = currentPersianYear(); foreach ([$cy - 1, $cy, $cy + 1] as $y): ?><option value="<?= $y ?>" <?= $y === $cy ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-4"><select class="form-select form-select-sm" name="sms_month" required><option value="">ماه</option>
                                    <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                                    foreach ($months as $mi => $mn): ?><option value="<?= $mi + 1 ?>"><?= $mn ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-4"><select class="form-select form-select-sm" name="sms_day" required><option value="">روز</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?><option value="<?= $d ?>"><?= $d ?></option><?php endfor; ?>
                                </select></div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm mt-3 w-100"><?= icon('send', '', 15) ?> ثبت پیامک</button>
                </form>
                <script>
                function smsTplChanged() {
                    const sel = document.getElementById('smsTemplate');
                    const opt = sel.options[sel.selectedIndex];
                    const req = opt.getAttribute('data-req') === '1';
                    document.getElementById('smsCodeReq').style.display = req ? '' : 'none';
                    document.getElementById('smsCode').required = req;
                    document.getElementById('smsSample').textContent = opt.getAttribute('data-sample') || '';
                }
                </script>
                <?php endif; ?>

                <?php if (!empty($smsEntries)): ?>
                <hr class="my-3">
                <h6 class="small fw-bold mb-2"><?= icon('clock', '', 15) ?> پیامک‌های ثبت‌شدهٔ شما</h6>
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($smsEntries, 0, 6) as $se): ?>
                        <?php $sb = match ($se['status']) { 'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark' }; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1 px-0 small">
                            <span>
                                <strong><?= htmlspecialchars($se['template_title']) ?></strong>
                                <?php if (!empty($se['sms_code'])): ?><code class="ms-1"><?= htmlspecialchars($se['sms_code']) ?></code><?php endif; ?>
                                <span class="text-muted d-block" style="font-size:.72rem">ثبت: <?= persianDate((string)$se['created_at']) ?> — دریافت پیامک: <?= persianDate((string)($se['received_at'] ?? '')) ?></span>
                            </span>
                            <span class="badge bg-<?= $sb ?>"><?= htmlspecialchars($se['status']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- تاریخچه تغییرات قبلی -->
        <?php if (!empty($postalChanges)): ?>
        <div class="card">
            <div class="card-header">تاریخچه درخواست‌های تغییر کد پستی</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($postalChanges as $pc): ?>
                    <?php
                    $pcBadge = match ($pc['status'] ?? 'در انتظار') {
                        'تأیید شده' => 'success',
                        'رد شده' => 'danger',
                        default => 'warning text-dark',
                    };
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span>
                            <code><?= htmlspecialchars($pc['old_postal_code']) ?></code>
                            ←
                            <code><?= htmlspecialchars($pc['new_postal_code']) ?></code>
                            <small class="text-muted d-block d-sm-inline">(<?= persianDate((string)$pc['requested_at']) ?>)</small>
                        </span>
                        <span class="badge bg-<?= $pcBadge ?>"><?= htmlspecialchars($pc['status'] ?? 'در انتظار') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>