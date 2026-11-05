export const statusLabel = status => ({success:'Rekaman terverifikasi',protected:'Libur, streak aman',broken:'Streak putus',pending:'Menunggu verifikasi',open:'Masih bisa diselesaikan',upcoming:'Belum dimulai',not_started:'Belum aktif'}[status]||'Belum dimulai');

window.recordStreakData = () => ({
    data:null,loading:false,error:'',timer:null,popupOpen:false,queuedCelebration:false,
    statusLabel,
    init(){
        this.refresh(true);
        this.timer=setInterval(()=>{if(!document.hidden)this.refresh(false);},60000);
    },
    destroy(){clearInterval(this.timer);},
    async refresh(celebrate=false){
        if(this.loading){this.queuedCelebration ||= celebrate;return;}
        this.loading=true;this.error='';
        try{
            const response=await fetch('/api/me/streak',{headers:{Accept:'application/json'}});
            if(!response.ok)throw new Error('Progres Daily Record belum tersedia. Coba lagi.');
            const result=await response.json();
            if(!result.streak||!Array.isArray(result.streak.days))throw new Error('Progres belum tersedia.');
            this.data=result.streak;
            if(celebrate&&this.data.attendance_id&&!this.data.celebrated){
                const claim=await fetch('/api/me/streak/celebrate',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:'{}'});
                if(claim.ok){const payload=await claim.json();this.data=payload.streak;if(payload.show_popup){this.data.celebrated=true;this.openPopup(true);}}
            }
        }catch{this.error='Progres Daily Record belum tersedia. Coba lagi.';}finally{
            this.loading=false;
            if(this.queuedCelebration){this.queuedCelebration=false;this.refresh(true);}
        }
    },
    openPopup(afterAttendance=false){
        if(!this.data||this.popupOpen||!window.Swal)return;
        this.popupOpen=true;
        const reduced=window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        const content=document.createElement('div');content.className='record-streak-popup-content'+(this.data.active?' is-lit':'');
        const art=document.createElement('div');art.className='record-streak-art';
        const flame=this.$el.closest('[data-record-streak]')?.querySelector('[data-streak-flame]')?.cloneNode(true);if(flame)art.append(flame);
        if(!reduced){const mascot=document.createElement('img');mascot.src='/assets/object/Gif%20Terimakasih.webp';mascot.alt='Karakter AIKu menyemangati harimu';mascot.width=180;mascot.height=135;art.append(mascot);}
        content.append(art);
        const number=document.createElement('strong');number.className='record-streak-popup-number';number.textContent=this.data.current;content.append(number);
        const subtitle=document.createElement('p');subtitle.className='record-streak-popup-subtitle';subtitle.textContent='hari kerja beruntun';content.append(subtitle);
        const week=document.createElement('div');week.className='record-streak-week';
        this.data.days.forEach(day=>{const cell=document.createElement('div');cell.className='record-streak-day '+day.status+(day.today?' is-today':'');const label=document.createElement('span');label.textContent=day.label;const mark=document.createElement('b');mark.textContent=day.status==='success'?'✓':day.status==='protected'?'−':day.status==='broken'?'×':day.status==='pending'?'◷':'·';cell.title=day.date+' · '+statusLabel(day.status);cell.append(label,mark);week.append(cell);});content.append(week);
        const message=document.createElement('p');message.className='record-streak-popup-message';message.textContent=this.data.message;content.append(message);
        const note=document.createElement('p');note.className='record-streak-popup-note';note.textContent='Libur menjaga streak. Izin, sakit, atau hari kerja tanpa absen mereset api. Target '+this.data.target_minutes+' menit rekaman + laporan terverifikasi.';content.append(note);
        const title=afterAttendance?'Absen tercatat!':this.data.active?'Jaga apimu tetap menyala':'Mulai lagi, satu hari berarti';
        Promise.resolve(window.Swal.fire({title,html:content,confirmButtonText:'Lanjutkan hariku',confirmButtonColor:'#315d24',width:400,
            customClass:{popup:'record-streak-popup',title:'record-streak-popup-title',confirmButton:'record-streak-confirm'},
            showCloseButton:true,closeButtonAriaLabel:'Tutup progres streak',returnFocus:true})).finally(()=>{this.popupOpen=false;});
    },
});
