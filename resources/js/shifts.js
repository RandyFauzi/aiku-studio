import {clone,statusLabels,prepareConfig,sessionRows,workHours,members,problems,dateRange,patternForDate,aggregate} from './shift-planner';

window.shiftsData=()=>({
    sidebarOpen:window.innerWidth>=768&&localStorage.getItem('aiku_sidebar_open')!=='false',
    loading:true,saving:false,error:'',saved:'',mode:'period',view:'board',search:'',
    config:null,baseline:'',revision:0,inherited:false,period:null,contributors:[],
    days:{},selectedDate:'',dayDraft:null,dayBaseline:'',dayRevision:0,drag:null,dropTarget:null,chosenPerson:'',chosenShift:1,
    today:new Intl.DateTimeFormat('sv-SE',{timeZone:'Asia/Makassar',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date()),statuses:statusLabels,
    async request(url,options={}){
        const response=await fetch(url,{...options,headers:{Accept:'application/json','Content-Type':'application/json',...options.headers}});
        const result=await response.json().catch(()=>({}));
        if(!response.ok)throw new Error(Object.values(result.errors||{}).flat().join(' ')||result.message||'Permintaan gagal. Silakan coba lagi.');return result;
    },
    async initData(){
        try{this.contributors=await this.request('/api/contributors');await this.loadPeriod('current');
            window.addEventListener('beforeunload',e=>{if(this.dirty){e.preventDefault();e.returnValue='';}});
            this.$watch('sidebarOpen',v=>localStorage.setItem('aiku_sidebar_open',String(v)));
        }catch(e){this.error=e.message;}finally{this.loading=false;}
    },
    async loadPeriod(id){
        const result=await this.request('/api/shifts/config?periodId='+encodeURIComponent(id));
        const dates=await this.request('/api/shifts/days?startDate='+result.period.startDate+'&endDate='+result.period.endDate);
        this.period=result.period;this.config=prepareConfig(result.config,this.contributors);this.revision=result.revision;this.inherited=result.inherited;
        this.baseline=JSON.stringify(this.config);this.days=dates.days||{};Object.values(this.days).forEach(d=>d.config=prepareConfig(d.config,this.contributors));
        this.chosenShift=1;this.selectDate(this.today>=this.period.startDate&&this.today<=this.period.endDate?this.today:this.period.startDate);
    },
    get periodDirty(){return !!this.config&&this.baseline!==JSON.stringify(this.config);},
    get dayDirty(){return !!this.dayDraft&&this.dayBaseline!==JSON.stringify(this.dayDraft);},
    get dirty(){return this.periodDirty||this.dayDirty;},get daily(){return this.mode==='day';},
    get activeConfig(){return this.daily?this.dayDraft:this.config;},
    get activeDirty(){return this.daily?this.dayDirty:this.periodDirty||this.inherited;},
    get conflicts(){return problems(this.activeConfig);},get shifts(){return sessionRows(this.activeConfig);},
    get dates(){return this.period?dateRange(this.period.startDate,this.period.endDate):[];},
    get roster(){
        const cfg=this.activeConfig;if(!cfg)return [];
        const rows=this.contributors.map(c=>({id:c.id,name:c.nama,linked:c.login_enabled,person:cfg.karyawan.find(p=>Number(p.contributor_id)===Number(c.id))}));
        cfg.karyawan.filter(p=>!this.contributors.some(c=>Number(c.id)===Number(p.contributor_id))).forEach(p=>rows.push({id:'legacy:'+p.name,name:p.name,linked:false,person:p}));
        return rows.filter(p=>p.name.toLowerCase().includes(this.search.toLowerCase()));
    },
    get unlinked(){return this.activeConfig?.karyawan.filter(p=>!p.contributor_id)||[];},
    get dayHours(){return(this.activeConfig?.karyawan||[]).reduce((s,p)=>s+workHours(p,this.activeConfig),0);},
    get filledSlots(){return this.shifts.reduce((n,s)=>n+this.shiftMembers(s.id).length,0);},
    get summary(){return this.config?aggregate(this.dates,d=>this.effectiveDate(d),this.config.hourly_rate):{rows:[]};},
    get calendarCells(){
        if(!this.dates.length)return [];
        const offset=(new Date(this.dates[0]+'T12:00:00Z').getUTCDay()+6)%7;
        return [...Array.from({length:offset},(_,i)=>({key:'blank'+i})),...this.dates.map(date=>{
            const cfg=this.effectiveDate(date),hours=cfg.karyawan.reduce((s,p)=>s+workHours(p,cfg),0);
            return {key:date,date,day:Number(date.slice(-2)),month:date.slice(5,7),hours,custom:!!this.days[date],absence:cfg.karyawan.filter(p=>['izin','sakit'].includes(p.status)).length};
        })];
    },
    effectiveDate(date){if(this.dayDirty&&date===this.selectedDate)return this.dayDraft;return this.days[date]?.config||patternForDate(this.config,date);},
    selectDate(date){this.selectedDate=date;this.dayDraft=clone(this.days[date]?.config||patternForDate(this.config,date));this.dayBaseline=JSON.stringify(this.dayDraft);this.dayRevision=this.days[date]?.revision||0;},
    async confirmDiscard(message){const r=await window.Swal.fire({title:'Perubahan belum disimpan',text:message,icon:'question',showCancelButton:true,confirmButtonText:'Buang perubahan',cancelButtonText:'Tetap mengedit'});return r.isConfirmed;},
    async openDate(date){
        if(this.saving||this.loading)return;
        if(this.periodDirty){this.error='Simpan atau batalkan pola periode dahulu sebelum merevisi harian.';return;}
        if(this.dayDirty&&!(await this.confirmDiscard('Perubahan tanggal ini akan dibuang.')))return;
        this.selectDate(date);this.mode='day';this.view='board';this.clearFeedback();
    },
    async switchMode(mode){if(this.saving||mode===this.mode)return;if(mode==='day'&&this.periodDirty){this.error='Simpan atau batalkan pola periode dahulu sebelum merevisi harian.';return;}if(mode==='period'&&this.dayDirty){this.error='Simpan atau batalkan revisi harian dahulu sebelum mengubah pola periode.';return;}this.mode=mode;this.clearFeedback();},
    async movePeriod(offset){
        if(this.saving||this.loading)return;if(this.dirty&&!(await this.confirmDiscard('Perubahan pola dan harian akan dibuang saat berganti periode.')))return;
        const anchor=new Date(this.period.startDate+'T12:00:00Z');anchor.setUTCMonth(anchor.getUTCMonth()+offset);const start=anchor.toISOString().slice(0,10),end=new Date(anchor);end.setUTCMonth(end.getUTCMonth()+1);end.setUTCDate(21);
        this.loading=true;this.clearFeedback();try{await this.loadPeriod(start+'_'+end.toISOString().slice(0,10));}catch(e){this.error=e.message;}finally{this.loading=false;}
    },
    async reload(){
        if(this.dirty&&!(await this.confirmDiscard('Muat ulang akan membuang seluruh perubahan yang belum disimpan.')))return;
        this.loading=true;this.clearFeedback();try{await this.loadPeriod(this.period?.id||'current');}catch(e){this.error=e.message;}finally{this.loading=false;}
    },
    shiftMembers(id){return this.activeConfig?members(this.activeConfig,id):[];},personKey(p){return p.contributor_id||'legacy:'+p.name;},
    ensurePerson(id){
        const cfg=this.activeConfig;let p=cfg.karyawan.find(p=>String(this.personKey(p))===String(id));
        if(!p){const c=this.contributors.find(c=>String(c.id)===String(id));if(!c)return null;p={contributor_id:c.id,name:c.nama,short:c.nama.split(' ')[0],role:'Reguler',alat:'Alat 1',sessions:[],status:'scheduled',note:''};cfg.karyawan.push(p);}return p;
    },
    assign(id,target,source=0){
        if(this.saving||this.loading||!this.activeConfig)return;target=Number(target);source=Number(source);this.clearFeedback();
        const existing=this.activeConfig.karyawan.find(p=>String(this.personKey(p))===String(id));
        if(existing?.sessions.includes(target)&&target>0){this.error='Anggota ini sudah ada di shift tersebut.';return;}
        if(target>0&&this.shiftMembers(target).length>=3){this.error=`Shift ${target} sudah berisi 3 orang. Pilih shift lain.`;return;}
        if(target>0&&!this.shifts.some(s=>s.id===target))return;
        const p=this.ensurePerson(id);if(!p)return;
        if(target>0&&p.status!=='scheduled'){this.error='Ubah status anggota menjadi Terjadwal sebelum menambahkan shift.';return;}
        if(source)p.sessions=p.sessions.filter(s=>s!==source);if(target>0)p.sessions=[...p.sessions,target].sort((a,b)=>a-b);
        this.saved=target>0?`${p.name} ditempatkan di Shift ${target}. Simpan untuk menerapkan.`:`${p.name} dilepas dari Shift ${source}.`;
    },
    startDrag(e,id,source=0){this.drag={id,source};e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',JSON.stringify(this.drag));},
    drop(e,target){e.preventDefault();if(this.drag)this.assign(this.drag.id,target,this.drag.source);this.drag=null;this.dropTarget=null;},
    setStatus(id,status){
        if(!this.daily||this.saving)return;this.clearFeedback();const p=this.ensurePerson(id);if(!p)return;
        if(status==='scheduled'&&p.status!=='scheduled'&&p.sessions.some(s=>this.shiftMembers(s).length>=3)){this.error='Shift asal sudah penuh. Lepas shift anggota ini dahulu sebelum menjadwalkan kembali.';return;}p.status=status;
    },
    clearAssignments(id){const p=this.activeConfig.karyawan.find(p=>String(this.personKey(p))===String(id));if(p&&!this.saving){p.sessions=[];this.clearFeedback();}},
    removePerson(id){if(this.saving)return;this.activeConfig.karyawan=this.activeConfig.karyawan.filter(p=>String(this.personKey(p))!==String(id));this.clearFeedback();},
    linkPerson(oldId,newId){
        if(!newId||this.saving)return;if(this.activeConfig.karyawan.some(p=>Number(p.contributor_id)===Number(newId))){this.error='Kontributor ini sudah ada di pola.';return;}
        const c=this.contributors.find(p=>Number(p.id)===Number(newId)),p=this.activeConfig.karyawan.find(p=>this.personKey(p)===oldId);if(c&&p){p.contributor_id=c.id;p.name=c.nama;this.clearFeedback();}
    },
    addShift(){if(this.saving||this.shifts.length>=12)return;this.activeConfig.session_durations.push(120);this.activeConfig.sessions_count=this.activeConfig.session_durations.length;this.clearFeedback();},
    async removeShift(){
        if(this.saving||this.shifts.length<=1)return;const last=this.shifts.length;
        if(this.activeConfig.karyawan.some(p=>p.sessions.includes(last))&&!(await this.confirmDiscard(`Penugasan pada Shift ${last} akan dilepas.`)))return;
        this.activeConfig.session_durations.pop();this.activeConfig.sessions_count--;this.chosenShift=Math.min(this.chosenShift,this.activeConfig.sessions_count);this.activeConfig.karyawan.forEach(p=>p.sessions=p.sessions.filter(s=>s!==last));this.clearFeedback();
    },
    async discard(){if(!(await this.confirmDiscard('Rancangan aktif akan kembali ke versi tersimpan.')))return;if(this.daily)this.dayDraft=JSON.parse(this.dayBaseline);else{this.config=JSON.parse(this.baseline);if(!this.dayDirty)this.selectDate(this.selectedDate);}this.clearFeedback();},
    async save(){
        if(this.saving||this.loading||!this.activeDirty||this.conflicts.length)return;this.saving=true;this.clearFeedback();
        try{
            const payload=clone(this.activeConfig),result=await this.request(this.daily?'/api/shifts/days/'+this.selectedDate:'/api/shifts/config',{method:this.daily?'PUT':'POST',body:JSON.stringify(this.daily?{config:payload,expected_revision:this.dayRevision}:{periodId:this.period.id,config:payload,expected_revision:this.revision})});
            if(this.daily){const day=result.day;this.days[this.selectedDate]={config:prepareConfig(day.config,this.contributors),revision:day.revision};this.selectDate(this.selectedDate);}
            else{this.config=prepareConfig(result.config,this.contributors);this.baseline=JSON.stringify(this.config);this.revision=result.revision;this.inherited=false;if(!this.dayDirty)this.selectDate(this.selectedDate);}
            this.saved=this.daily?'Revisi harian tersimpan dan tersedia di akun worker.':'Pola periode tersimpan dan tersedia di akun worker.';
        }catch(e){this.error=e.message;}finally{this.saving=false;}
    },
    async resetDate(){
        if(this.saving||!this.daily)return;const r=await window.Swal.fire({title:'Kembali ke pola periode?',text:'Revisi, izin, sakit, dan catatan tanggal ini akan dihapus.',icon:'question',showCancelButton:true,confirmButtonText:'Gunakan pola periode',cancelButtonText:'Batal'});
        if(!r.isConfirmed)return;this.saving=true;this.clearFeedback();try{if(this.dayRevision)await this.request('/api/shifts/days/'+this.selectedDate,{method:'DELETE',body:JSON.stringify({expected_revision:this.dayRevision})});delete this.days[this.selectedDate];this.selectDate(this.selectedDate);this.saved='Tanggal ini kembali mengikuti pola periode.';}catch(e){this.error=e.message;}finally{this.saving=false;}
    },
    get narrative(){if(!this.period)return '';const s=this.summary;return `Ringkasan jadwal ${this.period.name}\n${this.dirty||this.inherited?'RANCANGAN — belum seluruhnya disimpan.\n':''}${s.activeDays} hari aktif · ${this.number(s.hours)} jam kerja tim · ${this.number(s.production)} jam video.\nIzin/sakit: ${s.absences} orang-hari. Estimasi lolos QA ${this.number(s.production*this.config.qa_yield_rate/100)} jam.\nEstimasi biaya kapasitas penuh ${this.money(s.cost)} (bukan gaji aktual).\n\n`+s.rows.map(p=>`${p.name}: ${p.days} hari, ${this.number(p.hours)} jam kerja, ${this.number(p.production)} jam video; izin ${p.izin}, sakit ${p.sakit}.`).join('\n');},
    async copySummary(){try{await navigator.clipboard.writeText(this.narrative);this.saved='Ringkasan disalin.';}catch{this.error='Pilih dan salin teks ringkasan secara manual.';}},
    clearFeedback(){this.error='';this.saved='';},dateLabel(date){return date?new Date(date+'T12:00:00Z').toLocaleDateString('id-ID',{weekday:'short',day:'numeric',month:'short',year:'numeric',timeZone:'UTC'}):'';},
    initials(name){return name.split(' ').filter(Boolean).slice(0,2).map(p=>p[0]).join('');},number(n){return new Intl.NumberFormat('id-ID',{maximumFractionDigits:2}).format(n||0);},money(n){return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n||0);},hours(p){return workHours(p,this.activeConfig);},
});
