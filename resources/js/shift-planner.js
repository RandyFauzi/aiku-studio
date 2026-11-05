export const clone = value => JSON.parse(JSON.stringify(value));
export const statusLabels = {scheduled:'Terjadwal',izin:'Izin',sakit:'Sakit',libur:'Libur'};
export function prepareConfig(raw, people=[]) {
    const cfg={...clone(raw),planner_version:2,tools_count:3};
    cfg.session_durations=Array.from({length:Number(cfg.sessions_count)},(_,i)=>Number(raw.session_durations?.[i]??raw.session_duration_min));
    cfg.karyawan=(cfg.karyawan||[]).map(p=>{
        const matches=people.filter(c=>Number(c.id)===Number(p.contributor_id)||(!p.contributor_id&&c.nama.trim().toLowerCase()===p.name.trim().toLowerCase()));
        const person=matches.length===1?matches[0]:null;
        return {...p,contributor_id:person?.id||null,name:person?.nama||p.name,status:p.status||'scheduled',note:p.note||'',sessions:[...(p.sessions||[])].map(Number).sort((a,b)=>a-b)};
    });return cfg;
}
export function sessionRows(cfg) {
    if(!cfg)return [];
    const[h,m]=String(cfg.start_time||'08:00').split(':').map(Number);let minute=h*60+m;
    const time=n=>`${String(Math.floor(n/60)%24).padStart(2,'0')}:${String(n%60).padStart(2,'0')}${n>=1440?` (+${Math.floor(n/1440)} hari)`:''}`;
    return cfg.session_durations.map((duration,index)=>{const start=minute;minute+=Number(duration);const row={id:index+1,duration:Number(duration),start:time(start),end:time(minute)};minute+=Number(cfg.charging_duration_min);return row;});
}
export function workHours(person,cfg,planned=false) {
    if(!planned&&(person.status||'scheduled')!=='scheduled')return 0;
    return person.sessions.reduce((sum,id)=>sum+Number(cfg.session_durations[id-1]||0),0)/60;
}
export function members(cfg,session){return cfg.karyawan.filter(p=>(p.status||'scheduled')==='scheduled'&&p.sessions.includes(session));}
export function problems(cfg) {
    if(!cfg)return [];
    const errors=[],identities=new Set();
    if(!/^([01]\d|2[0-3]):[0-5]\d$/.test(cfg.start_time||''))errors.push('Isi jam mulai operasional.');
    if(cfg.session_durations.length<1||cfg.session_durations.length>12||cfg.session_durations.some(n=>!Number.isInteger(Number(n))||Number(n)<1||Number(n)>1440))errors.push('Durasi setiap shift harus 1–1.440 menit, maksimal 12 shift.');
    if(!Number.isInteger(Number(cfg.charging_duration_min))||cfg.charging_duration_min<0||cfg.charging_duration_min>1440)errors.push('Jeda harus 0–1.440 menit.');
    if(!Number.isInteger(Number(cfg.hp_per_tool))||cfg.hp_per_tool<1||cfg.hp_per_tool>100)errors.push('Jumlah HP per alat harus 1–100.');
    if(!Number.isFinite(Number(cfg.hourly_rate))||cfg.hourly_rate<0)errors.push('Tarif simulasi harus nol atau lebih.');
    if(!Number.isFinite(Number(cfg.qa_yield_rate))||cfg.qa_yield_rate<0||cfg.qa_yield_rate>100)errors.push('Estimasi lolos QA harus 0–100%.');
    if(cfg.session_durations.reduce((s,n)=>s+Number(n),0)+Math.max(0,cfg.sessions_count-1)*Number(cfg.charging_duration_min)>1440)errors.push('Total shift dan jeda melebihi 24 jam.');
    cfg.karyawan.forEach(p=>{
        if(!p.contributor_id)errors.push(`Hubungkan ${p.name} ke kontributor, atau keluarkan dari pola.`);
        const key=p.contributor_id||p.name;if(identities.has(key))errors.push(`${p.name} terdaftar dua kali.`);identities.add(key);
        if(p.sessions.some(id=>id<1||id>cfg.sessions_count))errors.push(`Periksa shift ${p.name}.`);
    });
    sessionRows(cfg).forEach(s=>{if(members(cfg,s.id).length>3)errors.push(`Shift ${s.id} maksimal 3 orang.`);});return errors;
}
export function dateRange(start,end){
    const dates=[];for(let d=new Date(start+'T12:00:00Z');d.toISOString().slice(0,10)<=end;d.setUTCDate(d.getUTCDate()+1))dates.push(d.toISOString().slice(0,10));return dates;
}
export function patternForDate(cfg,date){
    const result=clone(cfg),weekday=['sunday','monday','tuesday','wednesday','thursday','friday','saturday'][new Date(date+'T12:00:00Z').getUTCDay()];
    if(weekday===cfg.day_off)result.karyawan.forEach(p=>{p.sessions=[];p.status='libur';});return result;
}
export function aggregate(dates,forDate,rate){
    const rows=new Map();let activeDays=0,hours=0,production=0,absences=0,planned=0;
    dates.forEach(date=>{const cfg=forDate(date);let active=false;
        cfg.karyawan.forEach(p=>{
            const work=workHours(p,cfg),expect=workHours(p,cfg,true),id=p.contributor_id||p.name;
            const row=rows.get(id)||{id,name:p.name,hours:0,production:0,days:0,izin:0,sakit:0};
            row.name=p.name;row.hours+=work;row.production+=work*Number(cfg.hp_per_tool);if(work)row.days++;
            if(['izin','sakit'].includes(p.status)){row[p.status]++;absences++;}
            rows.set(id,row);hours+=work;planned+=expect;production+=work*Number(cfg.hp_per_tool);active ||= work>0;
        });if(active)activeDays++;
    });return {rows:[...rows.values()],activeDays,hours,planned,production,absences,cost:production*Number(rate)};
}
