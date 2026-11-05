import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.window={};
globalThis.document={querySelector:()=>null};
const {connectedDays}=await import('../../resources/js/record-streak.js');
const summary={current:3,longest:4,attendance_id:10,celebrated:false,days:[]};
const response=value=>({ok:true,json:async()=>value});

test('connected bar bridges holidays without turning them into successful workdays',()=>{
    const source=['success','protected','protected','success','open'].map(status=>({status}));
    const days=connectedDays(source);
    assert.deepEqual(days.map(d=>d.connected),[true,true,true,true,false]);
    assert.equal(days[0].joinLeft,false);assert.equal(days[3].joinRight,false);
    assert.equal(days[1].status,'protected');assert.equal(source[0].connected,undefined);
});

test('unverified days and reset days separate the bar into distinct runs',()=>{
    for(const separator of ['broken','pending','open','not_started']){
        const days=connectedDays(['success','success',separator,'success','success'].map(status=>({status})));
        assert.equal(days[1].joinRight,false);assert.equal(days[2].connected,false);assert.equal(days[3].joinLeft,false);
    }
    assert.ok(connectedDays([{status:'success'}]).every(d=>!d.connected));
});

test('holidays at the start of the visible week retain an existing earlier streak',()=>{
    const days=connectedDays(['protected','protected','success','open'].map(status=>({status})),5);
    assert.deepEqual(days.map(d=>d.connected),[true,true,true,false]);
    assert.equal(connectedDays([{status:'protected'},{status:'success'}],1)[0].connected,false);
    assert.ok(connectedDays([{status:'protected'},{status:'protected'}],3).every(d=>d.connected));
});

test('Perfect Streak requires three confirmed working days and disappears after reset',()=>{
    const component=window.recordStreakData();
    for(const current of [0,1,2]){component.data={current};assert.equal(component.perfectStreak,false);}
    component.data={current:3,days:[{status:'success'},{status:'protected'},{status:'success'}]};
    assert.equal(component.perfectStreak,true);assert.ok(component.perfectMessage.includes('3 hari kerja'));
    component.data={current:0};assert.equal(component.perfectStreak,false);assert.equal(component.perfectMessage,'');
});

test('check-in celebration is requested once and refresh does not replay it',async()=>{
    let claimed=false,popups=0,posts=0;
    globalThis.fetch=async(url,options={})=>{
        if(options.method==='POST'){posts++;claimed=true;return response({show_popup:true,streak:{...summary}});}
        return response({streak:{...summary,celebrated:claimed}});
    };
    const component=window.recordStreakData();component.openPopup=()=>popups++;
    await component.refresh(true);await component.refresh(true);await component.refresh(false);
    assert.equal(posts,1);assert.equal(popups,1);assert.equal(component.data.current,3);
});

test('check-in arriving during a refresh is not lost',async()=>{
    let release,gets=0,posts=0,popups=0;
    globalThis.fetch=async(url,options={})=>{
        if(options.method==='POST'){posts++;return response({show_popup:true,streak:{...summary}});}
        gets++;if(gets===1)return new Promise(resolve=>release=()=>resolve(response({streak:{...summary,attendance_id:null}})));
        return response({streak:{...summary}});
    };
    const component=window.recordStreakData();component.openPopup=()=>popups++;
    const loading=component.refresh(false);await component.refresh(true);release();await loading;
    await new Promise(resolve=>setImmediate(resolve));
    assert.equal(gets,2);assert.equal(posts,1);assert.equal(popups,1);
});

test('no attendance means no celebration and API failures retain last known count',async()=>{
    let posts=0;globalThis.fetch=async(url,options={})=>{if(options.method==='POST')posts++;return response({streak:{...summary,attendance_id:null}});};
    const component=window.recordStreakData();await component.refresh(true);assert.equal(posts,0);
    globalThis.fetch=async()=>{throw new Error('offline')};await component.refresh(true);
    assert.equal(component.data.current,3);assert.ok(component.error);assert.equal(component.loading,false);
});

test('another browser winning the server claim does not show a duplicate popup',async()=>{
    globalThis.fetch=async(url,options={})=>options.method==='POST'?response({show_popup:false,streak:{...summary,celebrated:true}}):response({streak:{...summary}});
    const component=window.recordStreakData();let popups=0;component.openPopup=()=>popups++;
    await component.refresh(true);assert.equal(popups,0);
});
