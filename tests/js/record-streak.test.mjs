import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.window={};
globalThis.document={querySelector:()=>null};
await import('../../resources/js/record-streak.js');
const summary={current:3,longest:4,attendance_id:10,celebrated:false,days:[]};
const response=value=>({ok:true,json:async()=>value});

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
