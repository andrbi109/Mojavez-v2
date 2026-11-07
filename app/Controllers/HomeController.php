<?php
/**
 * کنترلر صفحات عمومی: خانه، راهنما، دربارهٔ ما
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home', [
            'title' => setting('site_name', 'سامانه مجوز صنایع دستی'),
        ]);
    }

    /**
     * راهنمای جامع ثبت‌نام و پیگیری
     */
    public function guide(): void
    {
        $this->render('guide', [
            'title' => 'راهنمای جامع سامانه',
        ]);
    }

    /**
     * دربارهٔ ما — محتوا از تنظیمات سامانه (قابل ویرایش از پنل مدیریت)
     */
    public function about(): void
    {
        $this->render('about', [
            'title' => setting('about_title', 'دربارهٔ ما'),
        ]);
    }
}
