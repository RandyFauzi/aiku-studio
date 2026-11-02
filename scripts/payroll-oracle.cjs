// Run only the legacy read-only payroll handler against its source database.
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const {createRequire} = require('node:module');
const root = path.resolve(__dirname, '..');
const source = process.argv[2];
const mysql = createRequire(path.join(source, 'package.json'))('mysql2/promise');
(async () => {
    const db = await mysql.createConnection({host: '127.0.0.1', user: 'root', password: '', database: 'aiku_payroll', namedPlaceholders: true});
    try {
        await db.query('SET TRANSACTION READ ONLY');
        await db.beginTransaction();
        const code = fs.readFileSync(path.join(source, 'backend/server.js'), 'utf8');
        const helpers = code.slice(code.indexOf('function getCutoffPeriod('), code.indexOf('// GET /api/payroll/periods'));
        const handler = code.slice(code.indexOf("app.get('/api/payroll/live-summary'"), code.indexOf('// POST /api/payroll/status'));
        let callback;
        const context = {Date, Intl, console, app: {get: (route, fn) => {callback = fn;}},
            sequelize: {query: (sql, options) => db.query(sql, options?.replacements || {})},
            Payslip: {findAll: async ({where}) => (await db.query('SELECT * FROM payslips WHERE period = ?', [where.period]))[0]},
        };
        vm.createContext(context);
        vm.runInContext(helpers+'\n'+handler, context);
        const results = [];
        for (const id of ['2026-09-22_2026-10-21', '2026-10-22_2026-11-21', 'training_2026']) {
            await callback({query: {periodId: id}}, {json: data => results.push(data), status: status => {throw new Error('Legacy handler failed: '+status);}});
        }
        const target = path.join(root, 'storage/app/private/migration/legacy-payroll-oracle.json');
        fs.writeFileSync(target, JSON.stringify(results, null, 2));
        console.log('Read-only legacy oracle generated for '+results.length+' periods.');
        await db.rollback();
    } finally {await db.end();}
})().catch(error => {console.error(error.message); process.exitCode = 1;});
