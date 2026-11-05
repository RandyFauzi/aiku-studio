<section class="record-streak-card" data-record-streak x-data="recordStreakData()"
         @attendance-recorded.window="refresh(true)" @report-status-updated.window="refresh(false)"
         @visibilitychange.document="if(!document.hidden)refresh(false)" aria-labelledby="record-streak-heading">
    <div class="record-streak-top">
        <div class="record-streak-flame" :class="data?.active ? 'is-lit' : 'is-resting'">
            @include('portal.flame')
        </div>
        <div class="record-streak-copy">
            <h2 id="record-streak-heading">Daily Record</h2>
            <p class="record-streak-number"><strong x-text="loading&&!data?'…':(data?.current??0)"></strong><span>hari kerja<br>beruntun</span></p>
        </div>
        <button type="button" class="record-streak-detail" @click="openPopup()" :disabled="!data||loading" aria-label="Lihat detail streak">↗</button>
    </div>
    <p class="record-streak-message" role="status" x-text="error||data?.message||'Memuat progres rekamanmu…'"></p>
    <div class="record-streak-week" aria-label="Progres tujuh hari terakhir">
        <template x-for="day in data?.days||[]" :key="day.date">
            <div class="record-streak-day" :class="[day.status,day.today?'is-today':'']" :title="day.date+' · '+statusLabel(day.status)">
                <span x-text="day.label"></span>
                <b :aria-label="day.date+': '+statusLabel(day.status)" x-text="day.status==='success'?'✓':day.status==='protected'?'−':day.status==='broken'?'×':day.status==='pending'?'◷':'·'"></b>
            </div>
        </template>
    </div>
    <div class="record-streak-bottom"><span x-text="data?'Terbaik '+data.longest+' hari · target '+data.target_minutes+' menit':'Daily Record'"></span><button type="button" @click="refresh(false)" :disabled="loading" x-text="error?'Coba lagi':'Perbarui'"></button></div>
    <p x-show="data?.pending_days" class="record-streak-note" x-text="(data?.pending_days||0)+' hari menunggu verifikasi. Angka final belum bertambah.'"></p>
</section>
