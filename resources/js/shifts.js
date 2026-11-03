window.shiftsData = () => ({
    sidebarOpen: window.innerWidth >= 768 && localStorage.getItem('aiku_sidebar_open') !== 'false',
    tab: 'tim', search: '', loading: true, saving: false, error: '', saved: '', baseline: '', contributors: [], addName: '',
    month: new Intl.DateTimeFormat('en-CA', {timeZone:'Asia/Makassar',year:'numeric',month:'2-digit'}).format(new Date()).replace('/', '-'),
    config: {start_time:'08:00',charging_duration_min:60,session_duration_min:120,sessions_count:5,tools_count:3,hp_per_tool:2,hourly_rate:50000,day_off:'monday',qa_yield_rate:90,karyawan:[]},
    async initData() {
        try {
            const [settings, people] = await Promise.all([fetch('/api/shifts/config'),fetch('/api/contributors')]);
            if (!settings.ok || !people.ok) throw new Error('Pengaturan shift belum berhasil dimuat. Muat ulang halaman.');
            const data = await settings.json(); this.contributors = await people.json();
            this.config = {...this.config,...data.config};
            this.config.karyawan = this.config.karyawan.map(k => ({...k,sessions:(k.sessions||[]).map(Number)}));
            const now = new Date(); this.month = new Intl.DateTimeFormat('sv-SE',{timeZone:'Asia/Makassar',year:'numeric',month:'2-digit'}).format(now);
            this.baseline = JSON.stringify(this.config);
            window.addEventListener('beforeunload',event=>{if(this.dirty){event.preventDefault();event.returnValue='';}});
            window.addEventListener('resize',()=>{if(window.innerWidth<768)this.sidebarOpen=false;});
            this.$watch('sidebarOpen',v=>localStorage.setItem('aiku_sidebar_open',String(v)));
        } catch(e) { this.error=e.message; } finally {this.loading=false;}
    },
    get dirty() {return this.baseline !== '' && JSON.stringify(this.config)!==this.baseline;},
    get tools() {return Array.from({length:Number(this.config.tools_count)||0},(_,i)=>'Alat '+(i+1));},
    get sessions() {
        const [h,m]=this.config.start_time.split(':').map(Number);
        return Array.from({length:Number(this.config.sessions_count)||0},(_,i)=>{
            const start=h*60+m+i*(Number(this.config.session_duration_min)+Number(this.config.charging_duration_min));
            const end=start+Number(this.config.session_duration_min);
            const fmt=n=>String(Math.floor(n/60)%24).padStart(2,'0')+':'+String(n%60).padStart(2,'0')+(n>=1440?' +1 hari':'');
            return {id:i+1,start:fmt(start),end:fmt(end)};
        });
    },
    get visibleWorkers() {return this.config.karyawan.map((person,index)=>({person,index})).filter(row=>row.person.name.toLowerCase().includes(this.search.toLowerCase()));},
    get availablePeople() {const used=new Set(this.config.karyawan.map(k=>k.name));return this.contributors.filter(k=>!used.has(k.nama));},
    namesFor(worker) {return [...new Set([worker.name,...this.contributors.map(c=>c.nama)])].sort((a,b)=>a.localeCompare(b,'id'));},
    toggleSession(worker,id) {worker.sessions=worker.sessions.includes(id)?worker.sessions.filter(s=>s!==id):[...worker.sessions,id].sort((a,b)=>a-b);this.saved='';},
    hours(worker) {return worker.sessions.length*Number(this.config.session_duration_min)/60;},
    production(worker) {return this.hours(worker)*Number(this.config.hp_per_tool);},
    peopleAt(session,tool) {return this.config.karyawan.filter(k=>k.alat===tool&&k.sessions.includes(session));},
    get conflicts() {
        const messages=[]; const names=new Set();
        this.config.karyawan.forEach(k=>{
            if(names.has(k.name))messages.push(k.name+' terdaftar lebih dari sekali.'); names.add(k.name);
            if(!this.tools.includes(k.alat))messages.push('Pilih alat yang tersedia untuk '+k.name+'.');
            if(k.sessions.some(s=>s<1||s>Number(this.config.sessions_count)))messages.push('Perbarui sesi '+k.name+' sesuai jumlah sesi.');
        });
        this.sessions.forEach(s=>this.tools.forEach(tool=>{const people=this.peopleAt(s.id,tool);if(people.length>1)messages.push(tool+', sesi '+s.id+': '+people.map(k=>k.name).join(' dan ')+'.');}));
        return messages;
    },
    get dailyHours() {return this.config.karyawan.reduce((n,k)=>n+this.production(k),0);},
    get assignedSlots() {return this.config.karyawan.reduce((n,k)=>n+k.sessions.length,0);},
    get activeDays() {
        if(!/^\d{4}-\d{2}$/.test(this.month))return 0;
        const [year,month]=this.month.split('-').map(Number), days=new Date(year,month,0).getDate();
        const off=['sunday','monday','tuesday','wednesday','thursday','friday','saturday'].indexOf(this.config.day_off);
        let active=0;for(let day=1;day<=days;day++)if(new Date(year,month-1,day).getDay()!==off)active++;
        return active;
    },
    get monthlyProduction() {return this.dailyHours*this.activeDays;},
    get monthlyCost() {return this.monthlyProduction*Number(this.config.hourly_rate);},
    number(value) {return new Intl.NumberFormat('id-ID',{maximumFractionDigits:2}).format(value||0);},
    money(value) {return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(value||0);},
    initials(name) {return name.split(' ').filter(Boolean).slice(0,2).map(s=>s[0]).join('');},
    addWorker() {
        if(!this.addName)return;
        this.config.karyawan.push({name:this.addName,short:this.addName.split(' ')[0],role:'Reguler',sessions:[],alat:this.tools[0]||'Alat 1',daily_work_hrs:0,daily_prod_hrs:0});
        this.addName='';this.search='';this.saved='';
    },
    async removeWorker(index) {
        const result=await window.Swal.fire({title:'Hapus dari jadwal?',text:this.config.karyawan[index].name+' akan dilepas dari jadwal ini. Data kontributor dan payroll tetap tersimpan.',icon:'question',showCancelButton:true,confirmButtonText:'Hapus dari jadwal',cancelButtonText:'Batal',confirmButtonColor:'#009B7E'});
        if(result.isConfirmed)this.config.karyawan.splice(index,1);
    },
    async discard() {
        const result=await window.Swal.fire({title:'Batalkan perubahan?',text:'Jadwal akan kembali ke versi yang terakhir disimpan.',icon:'question',showCancelButton:true,confirmButtonText:'Batalkan perubahan',cancelButtonText:'Lanjut mengedit'});
        if(result.isConfirmed){this.config=JSON.parse(this.baseline);this.saved='';this.error='';}
    },
    async saveConfig() {
        if(this.saving||this.loading||!this.dirty||this.conflicts.length)return;
        this.saving=true;this.error='';this.saved='';
        try {
            const payload=JSON.parse(JSON.stringify(this.config));
            payload.karyawan=payload.karyawan.map(k=>({...k,daily_work_hrs:this.hours(k),daily_prod_hrs:this.production(k)}));
            const response=await fetch('/api/shifts/config',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
            const result=await response.json();if(!response.ok||!result.success)throw new Error(result.message||'Jadwal belum tersimpan.');
            this.config=result.config||payload;this.baseline=JSON.stringify(this.config);this.saved='Semua perubahan sudah tersimpan.';
        }catch(e){this.error=e.message;}finally{this.saving=false;}
    },
});
