import test from 'node:test';
import assert from 'node:assert/strict';
import {prepareConfig,sessionRows,workHours,aggregate,dateRange,patternForDate,problems} from '../../resources/js/shift-planner.js';

const people=[{id:1,nama:'One'},{id:2,nama:'Two'},{id:3,nama:'Three'},{id:4,nama:'Four'}];
const raw={start_time:'23:00',sessions_count:2,session_duration_min:120,session_durations:[90,180],charging_duration_min:30,tools_count:3,hp_per_tool:2,day_off:'monday',hourly_rate:50000,qa_yield_rate:90,karyawan:[{name:'One',contributor_id:1,alat:'Alat 1',sessions:[1,2]}]};
test('different shift lengths and overnight times',()=>{
    const cfg=prepareConfig(raw,people),rows=sessionRows(cfg);
    assert.equal(rows[0].end,'00:30 (+1 hari)');assert.equal(rows[1].start,'01:00 (+1 hari)');assert.equal(rows[1].end,'04:00 (+1 hari)');
    assert.equal(workHours(cfg.karyawan[0],cfg),4.5);assert.deepEqual(problems(cfg),[]);
});
test('period spans 22–21 and summaries count leave once per person-day',()=>{
    const cfg=prepareConfig(raw,people),dates=dateRange('2026-10-22','2026-11-21');assert.equal(dates.length,31);
    const day=prepareConfig(raw,people);day.karyawan[0].status='sakit';
    const s=aggregate(['2026-11-01','2026-11-02','2026-11-03'],d=>d==='2026-11-03'?day:patternForDate(cfg,d),50000);
    assert.equal(s.hours,4.5);assert.equal(s.production,9);assert.equal(s.absences,1);assert.equal(s.rows[0].sakit,1);assert.equal(s.activeDays,1);assert.equal(s.cost,450000);
});
test('capacity rejects four while absent workers free slots and identity survives rename',()=>{
    const cfg=prepareConfig({...raw,karyawan:people.map(p=>({name:p.nama,contributor_id:p.id,sessions:[1],alat:'Alat 1'}))},people);
    assert.ok(problems(cfg).includes('Shift 1 maksimal 3 orang.'));cfg.karyawan[3].status='izin';assert.deepEqual(problems(cfg),[]);
    assert.equal(prepareConfig(raw,[{id:1,nama:'New Name'}]).karyawan[0].name,'New Name');
    assert.equal(prepareConfig(raw,[]).karyawan[0].contributor_id,null);
});
