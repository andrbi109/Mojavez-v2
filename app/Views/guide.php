<?php
/**
 * راهنمای جامع سامانه — محتوای کامل برای متقاضیان
 * شامل: مراحل، مدارک، قوانین زمانی، پیگیری، قالب پیام اطلاع‌رسانی و سؤالات متداول
 */
$base = rtrim(BASE_URL, '/') . '/';
$username = setting('owner_username', 'Derogaramin');
$owner = owner_name();
$links = social_links();
?>

<div class="row g-4">
    <!-- فهرست مطالب (چسبان در دسکتاپ) -->
    <div class="col-lg-3">
        <div class="card p-3 guide-toc">
            <strong class="mb-2" style="color:var(--navy)">📑 فهرست راهنما</strong>
            <div class="list-group">
                <a class="list-group-item" href="#g-intro">۱. معرفی سامانه</a>
                <a class="list-group-item" href="#g-steps">۲. مراحل ثبت‌نام</a>
                <a class="list-group-item" href="#g-docs">۳. مدارک مورد نیاز</a>
                <a class="list-group-item" href="#g-files">۴. مشخصات فایل‌ها</a>
                <a class="list-group-item" href="#g-code">۵. کد رهگیری</a>
                <a class="list-group-item" href="#g-notify" style="color:var(--saffron-dark); font-weight:700">۶. اطلاع‌رسانی به مدیر ⭐</a>
                <a class="list-group-item" href="#g-rules">۷. قوانین و زمان‌بندی</a>
                <a class="list-group-item" href="#g-tracking">۸. پیگیری و تغییر کد پستی</a>
                <a class="list-group-item" href="#g-print">۹. نسخهٔ چاپی پرونده</a>
                <a class="list-group-item" href="#g-faq">۱۰. سؤالات متداول</a>
                <a class="list-group-item" href="#g-contact">۱۱. راه‌های ارتباطی</a>
            </div>
            <a href="<?= $base ?>register" class="btn btn-primary mt-3 w-100"><?= icon('edit', '', 16) ?> شروع ثبت‌نام</a>
        </div>
    </div>

    <div class="col-lg-9">

        <!-- ۱. معرفی -->
        <div class="card p-4 mb-4 guide-section" id="g-intro">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۱. معرفی سامانه</h4></div>
            <p class="mb-2">
                <strong><?= htmlspecialchars(setting('site_name')) ?></strong> بستری الکترونیکی برای ثبت درخواست،
                بارگذاری مدارک و پیگیری صدور <strong>مجوزهای صنایع دستی</strong> است؛ از فرش و گلیم تا سفال،
                منبت، معرق، قلم‌زنی و سایر رشته‌های هنرهای سنتی.
            </p>
            <p class="text-muted small mb-0">
                این سامانه توسط <strong><?= htmlspecialchars($owner) ?></strong>
                (آیدی <strong>@<?= htmlspecialchars($username) ?></strong> در ایتا، روبیکا و بله) طراحی و مدیریت می‌شود.
                کل فرآیند به‌صورت شفاف و مرحله‌به‌مرحله در تایم‌لاین پروندهٔ شما ثبت می‌گردد.
            </p>
        </div>

        <!-- ۲. مراحل -->
        <div class="card p-4 mb-4 guide-section" id="g-steps">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۲. مراحل ثبت‌نام (فرم چهارمرحله‌ای)</h4></div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded-3 h-100" style="background:var(--turquoise-light)">
                        <strong>🪪 مرحلهٔ ۱ — اطلاعات هویتی</strong>
                        <ul class="small mb-0 mt-2 pe-3">
                            <li>نام، نام خانوادگی و نام پدر (فارسی)</li>
                            <li>کد ملی ۱۰ رقمی (یکتا در سامانه)</li>
                            <li>کد پستی ۱۰ رقمی و موبایل ۰۹ (یکتا)</li>
                            <li>تاریخ تولد شمسی، وضعیت تأهل و فرزندان</li>
                            <li>وضعیت جسمانی، دهک درآمدی (اختیاری)</li>
                            <li>پروندهٔ قضایی، وام فعال و اقساط معوقه</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 h-100" style="background:var(--saffron-light)">
                        <strong>🏺 مرحلهٔ ۲ — اطلاعات شغلی</strong>
                        <ul class="small mb-0 mt-2 pe-3">
                            <li>عنوان مجوز درخواستی (انتخاب از فهرست رشته‌ها یا «سایر»)</li>
                            <li>سابقهٔ فعالیت در رشتهٔ درخواستی (برحسب ماه)</li>
                            <li>نحوهٔ آموزش: در خانه / استاد شاگردی / کارگاهی</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 h-100" style="background:var(--saffron-light)">
                        <strong>🏠 مرحلهٔ ۳ — اطلاعات ملک و مالکیت</strong>
                        <ul class="small mb-0 mt-2 pe-3">
                            <li>وضعیت مالکیت: شخص / مستأجر / سرپرست</li>
                            <li>استان، شهرستان، شهر (و بخش/دهستان/روستا در صورت وجود)</li>
                            <li>شناسهٔ قبض برق محل فعالیت</li>
                            <li>خیابان، کوچه و پلاک</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 h-100" style="background:var(--turquoise-light)">
                        <strong>📎 مرحلهٔ ۴ — بارگذاری مدارک</strong>
                        <ul class="small mb-0 mt-2 pe-3">
                            <li>تصویر کارت ملی (رو و پشت) و شناسنامه</li>
                            <li>مدرک مالکیت متناسب با انتخاب مرحلهٔ ۳</li>
                            <li>تصاویر محصولات حین تولید</li>
                            <li>مدارک تکمیلی (فنی‌وحرفه‌ای، تحصیلی، اختیاری)</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="alert mt-3 mb-0 py-2 small" style="background:var(--turquoise-light); color:var(--turquoise-dark);">
                💡 نوار پیشرفت بالای هر مرحله، گام جاری را با رنگ زعفرانی نشان می‌دهد. اطلاعات هر مرحله
                پیش از رفتن به مرحلهٔ بعد به‌طور کامل اعتبارسنجی می‌شود.
            </div>
        </div>

        <!-- ۳. مدارک -->
        <div class="card p-4 mb-4 guide-section" id="g-docs">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۳. مدارک مورد نیاز</h4></div>
            <h6 class="fw-bold">مدارک عمومی (برای همهٔ متقاضیان):</h6>
            <ul class="small">
                <li>تصویر <strong>کارت ملی</strong> — پشت و رو (ترجیحاً دو فایل جداگانه)</li>
                <li>تصویر <strong>شناسنامه</strong> — صفحهٔ اول (و در صورت نیاز صفحهٔ توضیحات)</li>
            </ul>
            <h6 class="fw-bold mt-3">مدرک محل فعالیت (بر اساس وضعیت مالکیت):</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-3">
                    <thead><tr><th>وضعیت مالکیت</th><th>مدرک لازم</th></tr></thead>
                    <tbody>
                        <tr><td><span class="badge bg-success">شخص</span></td><td>سند مالکیت یا قولنامهٔ معتبر</td></tr>
                        <tr><td><span class="badge bg-info">مستأجر</span></td><td>اجاره‌نامهٔ رسمی یا رضایت‌نامهٔ کتبی مالک</td></tr>
                        <tr><td><span class="badge bg-warning">سرپرست</span></td><td>حکم سرپرستی یا رضایت‌نامهٔ کتبی مرجع مربوطه</td></tr>
                    </tbody>
                </table>
            </div>
            <h6 class="fw-bold">مدارک تکمیلی (اختیاری اما مؤثر در ارزیابی):</h6>
            <ul class="small mb-0">
                <li>گواهینامهٔ فنی‌وحرفه‌ای یا گواهی دوره‌های آموزشی</li>
                <li>آخرین مدرک تحصیلی</li>
                <li><strong>تصاویر متقاضی با محصولات حین تولید</strong> (حداقل ۵ و حداکثر ۱۵ عکس)</li>
                <li>تا ۳ فایل اختیاری دیگر (نمونهٔ آثار، افتخارات، پروانه‌های قبلی و...)</li>
            </ul>
        </div>

        <!-- ۴. مشخصات فایل -->
        <div class="card p-4 mb-4 guide-section" id="g-files">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۴. مشخصات فایل‌های قابل قبول</h4></div>
            <div class="row g-3 text-center">
                <div class="col-4"><div class="p-3 rounded-3" style="background:var(--turquoise-light)"><div class="fs-4">🖼️</div><strong class="small">JPG / JPEG / PNG</strong><br><small class="text-muted">برای تصاویر مدارک و محصولات</small></div></div>
                <div class="col-4"><div class="p-3 rounded-3" style="background:var(--saffron-light)"><div class="fs-4">📄</div><strong class="small">PDF</strong><br><small class="text-muted">برای اسناد اسکن‌شده</small></div></div>
                <div class="col-4"><div class="p-3 rounded-3" style="background:var(--turquoise-light)"><div class="fs-4">⚖️</div><strong class="small">حداکثر ۲ مگابایت</strong><br><small class="text-muted">برای هر فایل</small></div></div>
            </div>
            <p class="text-muted small mt-3 mb-0">
                💡 پیشنهاد: عکس‌ها را با نور کافی و بدون بازتاب فلش بگیرید؛ فایل‌های ناخوانا باعث کُندی
                رسیدگی و درخواست اصلاح مدرک می‌شوند. سامانه فقط تصویر و PDF می‌پذیرد و هر فایل دیگری رد می‌شود.
            </p>
        </div>

        <!-- ۵. کد رهگیری -->
        <div class="card p-4 mb-4 guide-section" id="g-code">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۵. کد رهگیری؛ کلید پروندهٔ شما</h4></div>
            <p class="small mb-2">
                پس از تکمیل مرحلهٔ ۴، یک <strong>کد رهگیری یکتا</strong> مانند <code dir="ltr">HG-1A2B3C4D</code>
                به شما نمایش داده می‌شود. این کد <strong>تنها راه</strong> دسترسی به پرونده است:
            </p>
            <ul class="small">
                <li>مشاهدهٔ وضعیت و تایم‌لاین رویدادها</li>
                <li>درخواست تغییر کد پستی</li>
                <li>مشاهده و چاپ کامل پرونده</li>
            </ul>
            <div class="rule-card small">
                ⚠️ اطلاعات ثبت‌شده پس از نهایی‌سازی <strong>قابل ویرایش توسط متقاضی نیست</strong>.
                کد را یادداشت کنید، برگهٔ موفقیت را چاپ نمایید و در صورت مفقودی، با در دست داشتن کد ملی
                به مدیر سامانه پیام دهید.
            </div>
        </div>

        <!-- ۶. اطلاع‌رسانی به مدیر — بخش ویژه -->
        <div class="card p-4 mb-4 guide-section" id="g-notify" style="border:2px solid var(--saffron);">
            <div class="section-heading"><span class="ornament">⭐</span><h4 class="fw-bold mb-0" style="color:var(--saffron-dark)">۶. اطلاع‌رسانی به مدیر سامانه (بسیار مهم)</h4></div>
            <p class="small">
                <strong>پس از اخذ کد رهگیری</strong>، حتماً به آیدی
                <strong dir="ltr">@<?= htmlspecialchars($username) ?></strong>
                در یکی از شبکه‌های اجتماعی <strong>ایتا، روبیکا یا بله</strong> پیام دهید
                تا مدیر سامانه (<?= htmlspecialchars($owner) ?>) از ثبت درخواست شما مطلع شده و
                رسیدگی به پرونده سریع‌تر آغاز شود.
            </p>
            <div class="d-flex flex-wrap gap-2 mb-3 justify-content-center">
                <?php foreach ($links as $net => $link): ?>
                    <a class="social-chip <?= $net ?>" href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener">
                        <span class="dot"></span> <?= $link['label'] ?>: @<?= htmlspecialchars($link['id']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <h6 class="fw-bold mb-2">📨 قالب پیام (همین متن را کپی و نام/کد خود را جایگزین کنید):</h6>
            <div class="msg-template-box">
                <span class="msg-template-label">قالب پیام</span>
                <div class="msg-text" id="guideMsgTemplate">باسلام
{نام و نام خانوادگی متقاضی} هستم .
در سامانه مدارک رو بارگذاری کردم و کد رهگیری {کد رهگیری} را اخذ نمودم .
با تشکر {نام و نام خانوادگی متقاضی}</div>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button type="button" class="btn btn-gold" onclick="copyGuideTemplate()"><?= icon('copy', '', 16) ?> کپی قالب پیام</button>
            </div>
            <p class="text-muted small mt-3 mb-0 text-center">
                💡 این قالب به‌صورت <strong>تکمیل‌شده با نام و کد رهگیری شما</strong> در صفحهٔ موفقیتِ
                پایان ثبت‌نام نیز نمایش داده می‌شود و با یک دکمه قابل کپی است.
            </p>
        </div>

        <!-- ۷. قوانین -->
        <div class="card p-4 mb-4 guide-section" id="g-rules">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۷. قوانین و زمان‌بندی رسیدگی</h4></div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="rule-card h-100 small">
                        <strong>⏳ لغو خودکار (۱۴ روز)</strong><br>
                        اگر از مرحلهٔ «ثبت درخواست صدور» بیش از <strong>۱۴ روز</strong> بگذرد و مجوز صادر نشده باشد،
                        درخواست به‌صورت خودکار <strong>لغو</strong> و دلیل آن در تایم‌لاین پرونده ثبت می‌شود.
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="rule-card h-100 small">
                        <strong>⭐ اولویت رسیدگی (۱۲ روز)</strong><br>
                        اگر تکمیل ثبت‌نام شما (از شروع تا نهایی‌سازی) بیش از <strong>۱۲ روز</strong> طول بکشد،
                        پرونده با پرچم اولویت در صدر فهرست رسیدگی قرار می‌گیرد.
                    </div>
                </div>
            </div>
            <h6 class="fw-bold mt-3">مراحل رسیدگی به پرونده (به ترتیب):</h6>
            <ol class="small text-muted mb-0 pe-3">
                <li><strong>تکمیل اطلاعات</strong> — ثبت نهایی توسط متقاضی و صدور کد رهگیری</li>
                <li><strong>احراز نشانی</strong> — بررسی صحت اطلاعات ملک و آدرس</li>
                <li><strong>بررسی کارشناسی رشته</strong> — ارزیابی تخصصی رشتهٔ فعالیت توسط کارشناس</li>
                <li><strong>ثبت درخواست صدور</strong> — ارسال درخواست صدور مجوز (آغاز شمارش ۱۴ روزه)</li>
                <li><strong>بارگذاری مدارک به سازمان</strong> — تحویل مدارک به سازمان صادرکننده</li>
                <li><strong>درخواست اصلاح مدارک</strong> — در صورت نقص مدارک، از شما اصلاحیه خواسته می‌شود</li>
                <li><strong>تأیید اولیه</strong> — تأیید مدارک توسط سازمان</li>
                <li><strong>تأیید نهایی</strong> — تأیید نهایی پرونده پیش از صدور</li>
                <li><strong>صدور مجوز</strong> — صدور رسمی مجوز 🎉</li>
                <li><strong>تحویل مجوز</strong> — تحویل نسخهٔ فیزیکی/تصویر مجوز به متقاضی</li>
            </ol>
        </div>

        <!-- ۸. پیگیری -->
        <div class="card p-4 mb-4 guide-section" id="g-tracking">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۸. پیگیری پرونده و تغییر کد پستی</h4></div>
            <p class="small mb-2">در صفحهٔ <a href="<?= $base ?>tracking">پیگیری</a> با وارد کردن کد رهگیری:</p>
            <ul class="small">
                <li><strong>تایم‌لاین رویدادها:</strong> هر تغییر وضعیت با تاریخ شمسی و شرح کامل ثبت می‌شود.</li>
                <li><strong>درخواست تغییر کد پستی:</strong> کد پستی جدید ۱۰ رقمی را ثبت کنید؛
                    درخواست در صف بررسی مدیر قرار می‌گیرد و <strong>پس از تأیید</strong> اعمال می‌شود.
                    تا زمان بررسی، امکان ثبت درخواست دوم وجود ندارد.</li>
                <li><strong>وضعیت بررسی:</strong> نتیجهٔ هر درخواست (تأیید/رد + دلیل) در همان صفحه و نسخهٔ چاپی دیده می‌شود.</li>
            </ul>
        </div>

        <!-- ۹. چاپ -->
        <div class="card p-4 mb-4 guide-section" id="g-print">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۹. نسخهٔ چاپی پرونده</h4></div>
            <p class="small mb-0">
                با دکمهٔ «🖨️ مشاهده نسخهٔ چاپی» در صفحهٔ پیگیری، برگهٔ رسمی پرونده در قطع
                <strong>A4</strong> شامل اطلاعات هویتی، شغلی، ملک، تاریخچهٔ رویدادها، فهرست مدارک،
                سوابق تغییر کد پستی و محل امضا باز می‌شود. نوار ابزار شناور پایین صفحه برای چاپ است و
                هنگام پرینت محو می‌شود. هر بار مشاهدهٔ این صفحه، رویداد «مشاهده و چاپ پرونده» در
                تایم‌لاین ثبت می‌گردد.
            </p>
        </div>

        <!-- ۱۰. سؤالات متداول -->
        <div class="card p-4 mb-4 guide-section" id="g-faq">
            <div class="section-heading"><span class="ornament">❖</span><h4 class="fw-bold mb-0">۱۰. سؤالات متداول</h4></div>
            <div class="accordion faq-item" id="faqAccordion">
                <?php
                $faqs = [
                    ['کد رهگیری خود را گم کرده‌ام. چه کنم؟',
                        'کد رهگیری تنها کلید دسترسی الکترونیکی شماست. در صورت مفقودی، به آیدی Derogaramin در ایتا/روبیکا/بله پیام دهید و با ارائهٔ کد ملی، کد را از مدیر سامانه بخواهید.'],
                    ['آیا می‌توانم اطلاعات ثبت‌شده را ویرایش کنم؟',
                        'خیر — طبق قوانین سامانه، اطلاعات پس از نهایی‌سازی توسط متقاضی قابل ویرایش نیست. تنها «درخواست تغییر کد پستی» از صفحهٔ پیگیری ممکن است که پس از تأیید مدیر اعمال می‌شود. سایر اصلاحات فقط توسط مدیر سامانه انجام می‌گیرد.'],
                    ['چرا درخواست من خودکار لغو شد؟',
                        'اگر بیش از ۱۴ روز از مرحلهٔ «ثبت درخواست صدور» گذشته و مجوز صادر نشده باشد، سامانه به‌طور خودکار پرونده را لغو می‌کند. دلیل و تاریخ لغو در تایم‌لاین ثبت می‌شود. برای ثبت مجدد باید فرآیند را از ابتدا انجام دهید.'],
                    ['پرونده‌ام چقدر طول می‌کشد؟',
                        'رسیدگی به ترتیب مراحل (احراز نشانی، ثبت درخواست صدور، بارگذاری مدارک به سازمان، تأیید اولیه و صدور) انجام می‌شود. با پیام دادن به مدیر سامانه پس از اخذ کد رهگیری، از شروع رسیدگی مطمئن شوید. پرونده‌هایی که ثبت‌نامشان بیش از ۱۲ روز طول کشیده با اولویت بالاتر رسیدگی می‌شوند.'],
                    ['فرمت یا حجم فایل‌هایم نامناسب است. چه کنم؟',
                        'فقط jpg، jpeg، png و pdf با حداکثر حجم ۲ مگابایت پذیرفته می‌شود. عکس‌های بزرگ موبایل را می‌توانید با ابزارهای ویرایش تصویر کوچک کنید یا به‌صورت PDF اسکن بگیرید.'],
                    ['تصاویر محصولات چه مشخصاتی داشته باشند؟',
                        'حداقل ۵ و حداکثر ۱۵ عکس روشن و واضح از خودتان در حال کار با محصولات؛ ترجیحاً در محیط کارگاه یا محل فعالیت. این تصاویر در ارزیابی اصالت فعالیت شما نقش مهمی دارند.'],
                    ['درخواست تغییر کد پستی دادم؛ چه زمانی اعمال می‌شود؟',
                        'پس از بررسی و تأیید مدیر سامانه، کد پستی جدید فوراً روی پرونده اعمال و در تایم‌لاین ثبت می‌شود. در صورت رد درخواست، دلیل آن نیز نمایش داده می‌شود.'],
                    ['آیا اطلاعات و مدارک من محرمانه است؟',
                        'بله. مدارک فقط توسط کاربران مجاز پنل مدیریت قابل مشاهده است، فایل‌ها با نام تصادفی و خارج از دسترس مستقیم وب نگهداری می‌شوند و دسترسی عمومی فقط با کد رهگیری ممکن است.'],
                ];
                foreach ($faqs as $i => [$q, $a]): ?>
                <div class="accordion-item mb-2 rounded-3 overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                            <?= htmlspecialchars($q) ?>
                        </button>
                    </h2>
                    <div id="faq<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body small"><?= str_replace('Derogaramin', '<strong dir="ltr">@' . htmlspecialchars($username) . '</strong>', htmlspecialchars($a)) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ۱۱. ارتباط -->
        <div class="card p-4 guide-section" id="g-contact" style="background:linear-gradient(135deg,#16283C,#22374E); color:#fff; border:none;">
            <div class="section-heading"><span class="ornament" style="color:var(--saffron)">❖</span><h4 class="fw-bold mb-0 text-white">۱۱. راه‌های ارتباطی</h4></div>
            <p class="small mb-3" style="color:#D8E2EA;">
                پشتیبانی، هماهنگی و پیگیری ویژهٔ پرونده‌ها فقط از طریق شبکه‌های اجتماعی زیر
                (آیدی یکسان <strong dir="ltr">@<?= htmlspecialchars($username) ?></strong>):
            </p>
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                <?php foreach ($links as $net => $link): ?>
                    <a class="social-chip <?= $net ?>" href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener">
                        <span class="dot"></span> <?= $link['label'] ?>: @<?= htmlspecialchars($link['id']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="text-center small mb-0" style="color:#8B98A5;">
                مدیریت سامانه: <strong style="color:var(--saffron)"><?= htmlspecialchars($owner) ?></strong>
            </p>
        </div>
    </div>
</div>

<script>
function copyGuideTemplate() {
    const text = document.getElementById('guideMsgTemplate').textContent.trim();
    const done = () => alert('قالب پیام کپی شد ✔\nنام و کد رهگیری خود را جایگزین کنید.');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(done);
    } else {
        const ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta); done();
    }
}
</script>
