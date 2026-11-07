/**
 * اسکریپت زودهنگام تم — قبل از رندر بدنه اجرا می‌شود تا پرش رنگ رخ ندهد.
 * این قطعه به‌صورت درون‌خطی در <head> هر دو قالب (عمومی و مدیریت) قرار می‌گیرد.
 */
(function () {
    try {
        var saved = localStorage.getItem('mojavez-theme');
        var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-bs-theme', theme);
    } catch (e) { /* localStorage مسدود → حالت روز */ }
})();
