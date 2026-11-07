/**
 * مدیر تم روز/شب سامانه (v0.3.0)
 * ------------------------------------------------
 * - انتخاب کاربر در localStorage کلید «mojavez-theme» ذخیره می‌شود
 * - در نبود انتخاب، از ترجیح سیستم (prefers-color-scheme) پیروی می‌کند
 * - اسکریپت زودهنگام داخل <head> از پرش رنگ (FOUC) جلوگیری می‌کند
 */
(function () {
    'use strict';

    const KEY = 'mojavez-theme';

    function currentTheme() {
        return document.documentElement.getAttribute('data-bs-theme') || 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        // به‌روزرسانی همهٔ دکمه‌های تعویض تم
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            const sun = btn.querySelector('.icon-sun');
            const moon = btn.querySelector('.icon-moon');
            if (sun && moon) {
                sun.style.display = theme === 'dark' ? '' : 'none';
                moon.style.display = theme === 'dark' ? 'none' : '';
            }
            btn.setAttribute('title', theme === 'dark' ? 'حالت روز' : 'حالت شب');
        });
    }

    window.mojavezToggleTheme = function () {
        const next = currentTheme() === 'dark' ? 'light' : 'dark';
        localStorage.setItem(KEY, next);
        applyTheme(next);
    };

    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(currentTheme());
    });
})();
