const clone = value => JSON.parse(JSON.stringify(value));
export default function attachCalendar(component) {
    return Object.defineProperties(component, Object.getOwnPropertyDescriptors({
        tab:'kalender',selectedDate:'',dayDraft:null,dayBaseline:'',dayRevision:0,daySearch:'',dayAddName:'',dateSchedules:{},calendarLoading:false,calendarReady:false,
        today:new Intl.DateTimeFormat('sv-SE',{timeZone:'Asia/Makassar',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date()),
        get patternDirty(){return this.baseline!==''&&JSON.stringify(this.config)!==this.baseline;},
        get dayDirty(){return this.dayDraft!==null&&this.dayBaseline!==''&&JSON.stringify(this.dayDraft)!==this.dayBaseline;},
        get dirty(){return this.patternDirty||this.dayDirty;},
        get dateMode(){return ['kalender','jadwal'].includes(this.tab);},
        get activeDirty(){return this.dateMode?this.dayDirty:this.patternDirty;},
        get activeConflicts(){return this.dateMode?this.dayConflicts:this.conflicts;},
        get summaryConfig(){return this.dateMode&&this.dayDraft?this.dayDraft:this.config;},
        get dailyHours(){return this.summaryConfig.karyawan.reduce((sum,p)=>sum+this.workHours(p,this.summaryConfig)*Number(this.summaryConfig.hp_per_tool),0);},
        get assignedSlots(){return this.summaryConfig.karyawan.reduce((sum,p)=>sum+p.sessions.length,0);},
        get monthLabel(){return new Date(this.month+'-01T12:00:00').toLocaleDateString('id-ID',{month:'long',year:'numeric'});},
        get selectedLabel(){return this.selectedDate?new Date(this.selectedDate+'T12:00:00').toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'}):'';},
        workHours(person,cfg){return person.sessions.length*Number(cfg.session_duration_min)/60;},
        baseForDate(date){
            const cfg=clone(this.baseline?JSON.parse(this.baseline):this.config);
            const off=['sunday','monday','tuesday','wednesday','thursday','friday','saturday'].indexOf(cfg.day_off);
            if(new Date(date+'T12:00:00').getDay()===off)cfg.karyawan.forEach(p=>p.sessions=[]);
            return cfg;
        },
        configForDate(date){return this.dateSchedules[date]?.config||this.baseForDate(date);},
        get monthDates(){
            if(!/^\d{4}-\d{2}$/.test(this.month))return [];
            const[y,m]=this.month.split('-').map(Number);
            return Array.from({length:new Date(y,m,0).getDate()},(_,i)=>this.month+'-'+String(i+1).padStart(2,'0'));
        },
        get calendarCells(){
            const dates=this.monthDates;if(!dates.length)return [];
            const offset=(new Date(dates[0]+'T12:00:00').getDay()+6)%7;
            const cells=Array.from({length:offset},(_,i)=>({key:'blank-'+i,date:null}));
            dates.forEach(date=>{
                const cfg=this.configForDate(date),people=cfg.karyawan.filter(p=>p.sessions.length);
                cells.push({key:date,date,day:Number(date.slice(-2)),count:people.length,custom:!!this.dateSchedules[date],names:people.slice(0,2).map(p=>p.short||p.name.split(' ')[0]),hours:people.reduce((sum,p)=>sum+this.workHours(p,cfg)*cfg.hp_per_tool,0)});
            });
            while(cells.length%7)cells.push({key:'end-'+cells.length,date:null});
            return cells;
        },
        async loadCalendar(month){
            this.calendarLoading=true;this.calendarReady=false;
            try{
                const response=await fetch('/api/shifts/days?month='+encodeURIComponent(month));
                if(!response.ok)throw new Error('Kalender belum berhasil dimuat. Coba muat ulang.');
                const result=await response.json();this.dateSchedules=result.days||{};this.calendarReady=true;
            }finally{this.calendarLoading=false;}
        },
        async confirmDayDiscard(){
            if(!this.dayDirty)return true;
            const result=await window.Swal.fire({title:'Perubahan tanggal belum disimpan',text:'Simpan dulu jadwal tanggal ini, atau buang perubahan untuk berpindah tanggal.',icon:'question',showCancelButton:true,confirmButtonText:'Buang dan pindah',cancelButtonText:'Tetap di sini'});
            return result.isConfirmed;
        },
        async openDate(date,force=false){
            if(!force&&!(await this.confirmDayDiscard()))return;
            this.selectedDate=date;this.dayDraft=clone(this.configForDate(date));this.dayBaseline=JSON.stringify(this.dayDraft);
            this.dayRevision=this.dateSchedules[date]?.revision||0;this.daySearch='';this.dayAddName='';this.saved='';this.error='';
        },
        async changeMonth(value){
            if(!/^\d{4}-\d{2}$/.test(value)||!(await this.confirmDayDiscard()))return;
            this.month=value;this.dayDraft=null;this.dayBaseline='';
            try{await this.loadCalendar(value);await this.openDate(value+'-01',true);}catch(e){this.error=e.message;}
        },
        async moveMonth(offset){const[y,m]=this.month.split('-').map(Number);const date=new Date(y,m-1+offset,1);await this.changeMonth(date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0'));},
        async goToday(){if(this.today.slice(0,7)!==this.month)await this.changeMonth(this.today.slice(0,7));if(this.month===this.today.slice(0,7))await this.openDate(this.today);},
        get dayWorkers(){return(this.dayDraft?.karyawan||[]).map((person,index)=>({person,index})).filter(row=>row.person.name.toLowerCase().includes(this.daySearch.toLowerCase()));},
        get dayTools(){return Array.from({length:Number(this.dayDraft?.tools_count)||0},(_,i)=>'Alat '+(i+1));},
        get daySessions(){
            if(!this.dayDraft)return [];
            const[h,m]=this.dayDraft.start_time.split(':').map(Number);
            const fmt=n=>String(Math.floor(n/60)%24).padStart(2,'0')+':'+String(n%60).padStart(2,'0')+(n>=1440?' +'+Math.floor(n/1440)+' hari':'');
            return Array.from({length:Number(this.dayDraft.sessions_count)||0},(_,i)=>{const start=h*60+m+i*(Number(this.dayDraft.session_duration_min)+Number(this.dayDraft.charging_duration_min));return{id:i+1,start:fmt(start),end:fmt(start+Number(this.dayDraft.session_duration_min))};});
        },
        dayPeopleAt(session,tool){return(this.dayDraft?.karyawan||[]).filter(p=>p.alat===tool&&p.sessions.includes(session));},
        get dayConflicts(){
            if(!this.dayDraft)return [];
            const errors=[],slots=new Set(),names=new Set();
            this.dayDraft.karyawan.forEach(p=>{
                if(names.has(p.name))errors.push(p.name+' terdaftar lebih dari sekali.');names.add(p.name);
                if(!this.dayTools.includes(p.alat))errors.push('Pilih alat yang tersedia untuk '+p.name+'.');
                p.sessions.forEach(s=>{if(s<1||s>this.dayDraft.sessions_count)errors.push('Sesi '+p.name+' tidak tersedia.');const key=p.alat+':'+s;if(slots.has(key))errors.push(p.alat+', sesi '+s+' dipakai lebih dari satu orang.');slots.add(key);});
            });return [...new Set(errors)];
        },
        get dayAvailablePeople(){const used=new Set((this.dayDraft?.karyawan||[]).map(p=>p.name));return this.contributors.filter(p=>!used.has(p.nama));},
        addDayWorker(){if(!this.dayAddName||!this.dayDraft)return;this.dayDraft.karyawan.push({name:this.dayAddName,short:this.dayAddName.split(' ')[0],role:'Reguler',alat:this.dayTools[0],sessions:[]});this.dayAddName='';this.daySearch='';},
        markDayOff(){if(this.dayDraft)this.dayDraft.karyawan.forEach(p=>p.sessions=[]);},
        async saveDay(){
            if(!this.calendarReady||!this.dayDraft||!this.dayDirty||this.dayConflicts.length||this.saving)return;
            this.saving=true;this.error='';this.saved='';
            try{
                const response=await fetch('/api/shifts/days/'+this.selectedDate,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({config:this.dayDraft,expected_revision:this.dayRevision})});
                const result=await response.json();if(!response.ok)throw new Error(result.message||'Jadwal tanggal belum tersimpan.');
                const day=result.day;this.dateSchedules[this.selectedDate]={config:day.config,revision:day.revision};
                this.dayDraft=clone(day.config);this.dayRevision=day.revision;this.dayBaseline=JSON.stringify(this.dayDraft);this.saved='Jadwal '+this.selectedLabel+' sudah tersimpan.';
            }catch(e){this.error=e.message;}finally{this.saving=false;}
        },
        async resetDate(){
            if(!this.dayRevision){this.dayDraft=this.baseForDate(this.selectedDate);this.dayBaseline=JSON.stringify(this.dayDraft);return;}
            const result=await window.Swal.fire({title:'Kembali ke pola rutin?',text:'Jadwal khusus tanggal ini akan dihapus. Tanggal lain tetap sama.',icon:'question',showCancelButton:true,confirmButtonText:'Gunakan pola rutin',cancelButtonText:'Batal'});
            if(!result.isConfirmed)return;this.saving=true;
            try{
                const response=await fetch('/api/shifts/days/'+this.selectedDate,{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({expected_revision:this.dayRevision})});
                if(!response.ok)throw new Error('Tanggal belum bisa dikembalikan ke pola rutin.');
                delete this.dateSchedules[this.selectedDate];await this.openDate(this.selectedDate,true);this.saved='Tanggal ini kembali mengikuti pola rutin.';
            }catch(e){this.error=e.message;}finally{this.saving=false;}
        },
        async saveCurrent(){return this.dateMode?this.saveDay():this.saveConfig();},
        get monthlyRoster(){
            const rows=new Map();
            this.monthDates.forEach(date=>{const cfg=this.configForDate(date);cfg.karyawan.forEach(p=>{const row=rows.get(p.name)||{name:p.name,work:0,production:0,days:0};row.work+=this.workHours(p,cfg);row.production+=this.workHours(p,cfg)*cfg.hp_per_tool;if(p.sessions.length)row.days++;rows.set(p.name,row);});});
            return [...rows.values()];
        },
        get activeDays(){return this.monthDates.filter(date=>this.configForDate(date).karyawan.some(p=>p.sessions.length)).length;},
        get monthlyProduction(){return this.monthlyRoster.reduce((sum,p)=>sum+p.production,0);},
        get monthlyCost(){return this.monthlyProduction*Number(this.config.hourly_rate);},
    }));
}
