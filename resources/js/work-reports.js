const today = () => new Intl.DateTimeFormat('sv-SE',{timeZone:'Asia/Makassar',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
const duration = value => {const mins=Number(value)||0;return Math.floor(mins/60)+' jam '+mins%60+' menit';};
const labels = {putih:'Putih',pink:'Pink',biru:'Biru',hijau:'Hijau',hitam:'Hitam',pink_st:'Pink ST'};
const phoneNames = phones => (phones||[]).map(phone=>labels[phone]||phone).join(', ');
const dateLabel = date => new Date(date+'T12:00:00').toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});
window.dailyWorkReportData = () => ({
    date:today(),month:today().slice(0,7),hours:0,minutes:0,phones:[],options:labels,revision:0,reports:[],loading:true,saving:false,error:'',message:'',baseline:'',
    duration,phoneNames,dateLabel,
    get totalMinutes(){return Number(this.hours||0)*60+Number(this.minutes||0);},
    get dirty(){return this.baseline!==''&&JSON.stringify([this.hours,this.minutes,[...this.phones].sort()])!==this.baseline;},
    async init(){await this.load(true);window.addEventListener('beforeunload',event=>{if(this.dirty){event.preventDefault();event.returnValue='';}});},
    async load(reset=false){
        this.loading=true;this.error='';
        try{
            const response=await fetch('/api/me/work-reports?month='+this.month,{headers:{Accept:'application/json'}});
            if(!response.ok)throw new Error('Laporan belum berhasil dimuat.');
            const data=await response.json();this.reports=data.reports;this.options=data.phone_options;
            if(reset){const report=data.today_report;this.date=data.today;this.hours=Math.floor((report?.total_duration_minutes||0)/60);this.minutes=(report?.total_duration_minutes||0)%60;this.phones=[...(report?.phones||[])];this.revision=report?.revision||0;this.baseline=JSON.stringify([this.hours,this.minutes,[...this.phones].sort()]);}
        }catch(e){this.error=e.message;}finally{this.loading=false;}
    },
    async reload(){
        if(this.dirty&&!window.confirm('Muat ulang dan buang perubahan laporan yang belum disimpan?'))return;
        this.message='';await this.load(true);
    },
    async submit(){
        if(this.loading||this.saving)return;this.saving=true;this.error='';this.message='';
        try{
            const response=await fetch('/api/me/work-report',{method:'PUT',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
                body:JSON.stringify({work_date:this.date,total_duration_minutes:this.totalMinutes,phones:this.phones,expected_revision:this.revision})});
            const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join(' ')||data.message||'Laporan belum tersimpan.');
            const report=data.report;this.revision=report.revision;this.hours=Math.floor(report.total_duration_minutes/60);this.minutes=report.total_duration_minutes%60;this.phones=[...report.phones];
            this.baseline=JSON.stringify([this.hours,this.minutes,[...this.phones].sort()]);this.message='Laporan '+dateLabel(this.date)+' berhasil disimpan.';
            await this.load(false);
        }catch(e){this.error=e.message;}finally{this.saving=false;}
    },
});
window.adminWorkReportsData = () => ({
    date:today(),rows:[],search:'',loading:true,error:'',total:0,duration,phoneNames,dateLabel,
    async init(){await this.load();},
    async load(){this.loading=true;this.error='';try{const response=await fetch('/api/work-reports?date='+this.date);if(!response.ok)throw new Error('Rekap laporan belum berhasil dimuat.');const data=await response.json();this.rows=data.reports;this.total=data.total_duration_minutes;}catch(e){this.error=e.message;}finally{this.loading=false;}},
    get filtered(){return this.rows.filter(row=>row.contributor_name.toLowerCase().includes(this.search.toLowerCase()));},
});
