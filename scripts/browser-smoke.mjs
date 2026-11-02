import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const legacyRequire = createRequire(path.join(process.argv[2], 'package.json'));
const puppeteer = legacyRequire('puppeteer');
const credentials = fs.readFileSync(path.join(root, 'storage/app/private/initial-admin.txt'), 'utf8');
const email = credentials.match(/Email: (.+)/)[1];
const password = credentials.match(/Password: (.+)/)[1];
const browser = await puppeteer.launch({executablePath: 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', headless: true});
const report = [];
try {
    const page = await browser.newPage();
    await page.setViewport({width: 1440, height: 1000});
    const errors = [];
    page.on('pageerror', e => errors.push(String(e)));
    page.on('console', e => { if (e.type() === 'error') errors.push(e.text()); });
    await page.goto('http://127.0.0.1:8010/login', {waitUntil: 'networkidle0'});
    await page.type('#email', email);
    await page.type('#password', password);
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('button')]);
    if (new URL(page.url()).pathname === '/login') throw new Error('Login failed');
    for (const name of ['index', 'contributors', 'create', 'payslips', 'profit', 'recordings', 'shifts']) {
        errors.length = 0;
        const response = await page.goto(`http://127.0.0.1:8010/${name}.html`, {waitUntil: 'networkidle0'});
        await page.waitForFunction(() => document.querySelector('[x-data]')?._x_dataStack?.length > 0);
        const state = await page.evaluate(() => ({
            title: document.title, visible: !document.body.hasAttribute('x-cloak'),
            overflow: document.documentElement.scrollWidth > window.innerWidth,
            initialized: !!document.querySelector('[x-data]')?._x_dataStack,
        }));
        await page.screenshot({path: path.join(root, `storage/app/private/migration/${name}-desktop.png`), fullPage: true});
        report.push({name, status: response.status(), ...state, errors: [...errors]});
    }
    await page.setViewport({width: 390, height: 844});
    for (const name of ['index', 'recordings', 'payslips']) {
        await page.goto(`http://127.0.0.1:8010/${name}.html`, {waitUntil: 'networkidle0'});
        await page.screenshot({path: path.join(root, `storage/app/private/migration/${name}-mobile.png`), fullPage: true});
        report.push({name: name+'-mobile', overflow: await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)});
    }
    const csrfResult = await page.evaluate(async () => {
        const contributor = await (await fetch('/api/contributors', {method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({nama: 'Migration Smoke Test', email: 'migration-smoke@example.invalid'})})).json();
        if (!contributor.id) throw new Error('CSRF-protected create failed');
        const removed = await fetch('/api/contributors/'+contributor.id, {method: 'DELETE'});
        if (!removed.ok) throw new Error('CSRF-protected delete failed');
        return {createdAndDeleted: true};
    });
    report.push({name: 'session-and-csrf', ...csrfResult});
    const result = await page.evaluate(async () => {
        const slips = await (await fetch('/api/slips')).json();
        const result = await (await fetch(`/api/payslips/${slips[0].id}/export`)).json();
        if (!result.success) throw new Error('PDF export failed');
        return result;
    });
    const pdfResponse = await page.goto('http://127.0.0.1:8010'+result.url);
    if (pdfResponse?.status() !== 200 && !page.url().includes('pdf')) throw new Error('PDF download failed');
} finally {
    await browser.close();
    fs.writeFileSync(path.join(root, 'storage/app/private/migration/browser-report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
}
