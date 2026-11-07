// تست واکنش‌گرایی با Playwright
// اسکرین‌شات از صفحه نمونه در اندازه‌های مختلف موبایل/تبلت/دسکتاپ
const { chromium } = require('playwright');

(async () => {
  const devices = [
    ['iPhone SE', 375, 667, 'موبایل کوچک'],
    ['iPhone 12', 390, 844, 'موبایل استاندارد'],
    ['iPhone 14 Pro Max', 430, 932, 'موبایل بزرگ'],
    ['Galaxy S20', 360, 800, 'اندرویدی'],
    ['iPad Mini', 768, 1024, 'تبلت کوچک'],
    ['iPad Pro 11', 834, 1194, 'تبلت متوسط'],
    ['iPad Pro 12.9', 1024, 1366, 'تبلت بزرگ'],
    ['Desktop', 1366, 768, 'دسکتاپ'],
  ];

  const browser = await chromium.launch({ headless: true });
  const results = [];

  for (const [name, w, h, label] of devices) {
    const ctx = await browser.newContext({
      viewport: { width: Number(w), height: Number(h) },
      deviceScaleFactor: 2,
      isMobile: w <= 768,
      hasTouch: w <= 768,
    });
    const page = await ctx.newPage();
    try {
      await page.goto('file:///home/z/my-project/download/responsive-test-sample.html',
        { waitUntil: 'networkidle', timeout: 15000 });
      await page.waitForTimeout(800);

      const fileName = 'screenshot-' + name.replace(/[^a-zA-Z0-9]/g, '_') + '.png';
      await page.screenshot({
        path: '/home/z/my-project/download/' + fileName,
        fullPage: true
      });

      // بررسی عدم وجود اسکرول افقی
      const dims = await page.evaluate(() => ({
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        scrollHeight: document.documentElement.scrollHeight,
      }));
      const hasHScroll = dims.scrollWidth > dims.clientWidth + 5;

      // بررسی دکمه‌ها
      const btnCount = await page.locator('button, a.btn').count();

      // بررسی سرریز متن
      const overflow = await page.evaluate(() => {
        const els = document.querySelectorAll('body *');
        let overflowCount = 0;
        for (let i = 0; i < Math.min(els.length, 200); i++) {
          const r = els[i].getBoundingClientRect();
          if (r.right > window.innerWidth + 1 || r.left < -1) overflowCount++;
        }
        return overflowCount;
      });

      results.push({
        name, width: w, height: h, label,
        btnCount, hasHScroll, overflow,
        scrollWidth: dims.scrollWidth, clientWidth: dims.clientWidth,
        file: fileName, ok: !hasHScroll && overflow === 0
      });
    } catch (e) {
      results.push({ name, error: e.message.slice(0, 100) });
    }
    await ctx.close();
  }

  await browser.close();
  console.log(JSON.stringify(results, null, 2));
})();
