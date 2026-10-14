<?php
/**
 * Consistent appointment calendar matching the patient booking calendar layout.
 * Inputs: $calendarRole ('assistant'|'dentist'), $calendarEvents and optional $calendarBlocks.
 * The selected-day side panel keeps appointment status explicit before approval.
 */
$calendarRole = ($calendarRole ?? '') === 'dentist' ? 'dentist' : 'assistant';
$calendarEvents = $calendarEvents ?? [];
$calendarBlocks = $calendarBlocks ?? [];
$calendarPayload = json_encode($calendarEvents, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$blockPayload = json_encode($calendarBlocks, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES);
?>
<section class="card sd-staff-calendar-card" id="schedule-calendar" aria-label="Clinic appointment calendar">
    <div class="card-heading"><div><h2>Appointment Calendar</h2><p class="muted">Monday–Saturday only. Sundays are closed (red). <?= $calendarRole==='dentist' ? 'Pending requests are visible but not confirmed on your schedule.' : 'Online requests appear as Pending until approved and assigned to a dentist.' ?></p></div></div>
    <div class="sd-team-toolbar"><label class="sd-team-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" id="teamCalendarSearch" placeholder="Search patient or service" aria-label="Search appointments"></label><button class="btn btn-secondary" type="button" id="teamCalendarToday">Today</button><button class="btn btn-secondary" type="button" id="teamCalendarRefresh" title="Refresh the latest bookings">Refresh</button></div>
    <div class="sd-team-layout">
        <div class="booking-calendar sd-team-booking-calendar">
            <div class="calendar-head"><button type="button" class="calendar-nav" id="teamCalendarPrev" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button><strong id="teamCalendarTitle" aria-live="polite"></strong><button type="button" class="calendar-nav" id="teamCalendarNext" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button></div>
            <div class="calendar-week sd-team-week"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
            <div class="calendar-grid sd-team-grid" id="teamCalendarGrid"></div>
            <div class="sd-team-legend"><span><i class="sd-dot-approved"></i> Confirmed</span><span><i class="sd-dot-pending"></i> Pending</span><span><i class="sd-dot-closed"></i> Sunday closed</span><span><i class="sd-dot-unavailable"></i> Unavailable</span></div>
        </div>
        <aside class="sd-team-details" aria-label="Selected date appointments"><h3 id="teamSelectedDate">Select a date</h3><p class="muted" id="teamDayCount">Click a calendar date to view patient appointments and their statuses.</p><div class="sd-team-day-list" id="teamDayEvents" aria-live="polite"></div></aside>
    </div>
</section>
<script>
(()=>{'use strict';
 const role=<?=json_encode($calendarRole)?>;
 let events=<?=$calendarPayload ?: '[]'?>;
 let blocks=<?=$blockPayload ?: '[]'?>;
 const csrf=<?=json_encode(function_exists('csrf_token') ? csrf_token() : '', JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
 const $=id=>document.getElementById(id), grid=$('teamCalendarGrid'), list=$('teamDayEvents'), search=$('teamCalendarSearch');
 const pad=n=>String(n).padStart(2,'0'), key=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
 let current=new Date(),selected=key(current);
 current=new Date(current.getFullYear(),current.getMonth(),1);
 const dateEvents=k=>events.filter(e=>e.appointment_date===k && (!search.value.trim()||[e.first_name,e.last_name,e.appointment_type,e.reason,e.email,e.dentist_first,e.dentist_last].join(' ').toLowerCase().includes(search.value.trim().toLowerCase()))).sort((a,b)=>a.appointment_time.localeCompare(b.appointment_time));
 const dateBlocks=k=>blocks.filter(b=>b.unavailable_date===k);
 const name=e=>[e.first_name,e.last_name].filter(Boolean).join(' ');
 const time=t=>{const [h,m]=t.split(':').map(Number);return new Date(2000,0,1,h,m).toLocaleTimeString([],{hour:'numeric',minute:'2-digit'});};
 const el=(tag,cls,txt)=>{const node=document.createElement(tag);if(cls)node.className=cls;if(txt!==undefined)node.textContent=txt;return node;};
 function render(){
  $('teamCalendarTitle').textContent=current.toLocaleDateString([],{month:'long',year:'numeric'});
  grid.replaceChildren();
  const first=new Date(current.getFullYear(),current.getMonth(),1),days=new Date(current.getFullYear(),current.getMonth()+1,0).getDate();
  for(let i=0;i<first.getDay();i++)grid.append(el('span','sd-team-day empty'));
  for(let d=1;d<=days;d++){
   const date=new Date(current.getFullYear(),current.getMonth(),d),k=key(date),sunday=date.getDay()===0,rows=dateEvents(k),absences=dateBlocks(k);
   const btn=el('button','sd-team-day'+(sunday?' sunday':'')+(k===selected?' selected':'')+(rows.length?' has-events':''));
   btn.type='button';btn.setAttribute('aria-label',date.toLocaleDateString([],{weekday:'long',month:'long',day:'numeric'})+(sunday?', clinic closed':'')+', '+rows.length+' appointments');
   btn.append(el('b','sd-team-date-number',String(d)));
   if(sunday)btn.append(el('span','sd-team-closed-label','Closed'));
   if(absences.length)btn.append(el('span','sd-team-unavailable','Unavailable'));
   rows.slice(0,2).forEach(e=>btn.append(el('span','sd-team-event '+e.status,time(e.appointment_time)+' '+name(e))));
   if(rows.length>2)btn.append(el('small','sd-team-more','+'+(rows.length-2)+' more'));
   btn.addEventListener('click',()=>{selected=k;render();details();});
   grid.append(btn);
  }
 }
 function details(){
  const date=new Date(selected+'T12:00:00'),rows=dateEvents(selected),absences=dateBlocks(selected);
  $('teamSelectedDate').textContent=date.toLocaleDateString([],{weekday:'long',month:'long',day:'numeric',year:'numeric'});
  $('teamDayCount').textContent=(date.getDay()===0?'Clinic closed on Sunday. ':'')+rows.length+' appointment'+(rows.length===1?'':'s')+' on this date.';
  list.replaceChildren();
  absences.forEach(b=>{const card=el('div','sd-team-detail sd-team-block');card.append(el('strong',null,b.staff_name?b.staff_name+' unavailable':'Dentist unavailable'));card.append(el('p',null,(+b.all_day?'All day':b.start_time.slice(0,5)+'–'+b.end_time.slice(0,5))+(b.reason?' • '+b.reason:'')));list.append(card);});
  rows.forEach(e=>{
    const card=el('div','sd-team-detail'),top=el('div','sd-team-detail-top'),info=el('div');
    info.append(el('strong',null,name(e)));info.append(el('span','sd-team-detail-time',time(e.appointment_time)+' • '+e.appointment_type));
    const status=el('span','sd-team-status '+e.status,e.status.charAt(0).toUpperCase()+e.status.slice(1));top.append(info,status);card.append(top);
    if(e.status==='pending')card.append(el('small','sd-team-notice',role==='dentist'?'Pending assistant approval • Not assigned to a dentist':'Awaiting assistant approval'));
    if(role==='assistant' && e.dentist_first)card.append(el('small','sd-team-notice','Dentist: '+e.dentist_first+' '+(e.dentist_last||'')));
    if(e.reason)card.append(el('p','sd-team-reason',e.reason));
    const acts=el('div','sd-team-actions');
    const view=el('a','btn btn-secondary',role==='assistant'?'View patient':'View patient');view.href='patient_view.php?id='+encodeURIComponent(e.patient_id);acts.append(view);
    if(role==='assistant'&&e.status==='pending'){
      const review=el('a','btn btn-primary','Review request');review.href='appointments.php#appointment-request-'+encodeURIComponent(e.id);acts.append(review);
    }
    if(role==='dentist'&&e.status==='approved'){
      const form=el('form');form.method='post';['csrf_token','action','appointment_id'].forEach((field,i)=>{const input=el('input');input.type='hidden';input.name=field;input.value=[csrf,'complete',e.id][i];form.append(input)});
      const complete=el('button','btn btn-primary','Mark completed');complete.type='submit';form.append(complete);acts.append(form);
    }
    card.append(acts);list.append(card);
  });
  if(!rows.length&&!absences.length)list.append(el('div','sd-team-empty',date.getDay()===0?'The clinic is closed on Sundays.':'No appointments scheduled for this date.'));
 }
 $('teamCalendarPrev').addEventListener('click',()=>{current=new Date(current.getFullYear(),current.getMonth()-1,1);render()});
 $('teamCalendarNext').addEventListener('click',()=>{current=new Date(current.getFullYear(),current.getMonth()+1,1);render()});
 $('teamCalendarToday').addEventListener('click',()=>{const now=new Date();current=new Date(now.getFullYear(),now.getMonth(),1);selected=key(now);render();details();});
 search.addEventListener('input',()=>{render();details();});
 let refreshing=false;
 async function refresh(){
  if(refreshing)return;refreshing=true;
  const button=$('teamCalendarRefresh');button.disabled=true;
  try{
   const response=await fetch('../api/calendar_feed.php?portal='+encodeURIComponent(role),{credentials:'same-origin',cache:'no-store'});
   if(!response.ok)throw new Error('Failed to refresh');
   const data=await response.json();
   if(Array.isArray(data.events)&&Array.isArray(data.blocks)){
     events=data.events;blocks=data.blocks;render();details();
     button.title='Schedule updated';
   }
  }catch(err){button.title='Could not update. Try Refresh again.';}
  finally{refreshing=false;button.disabled=false;}
 }
 $('teamCalendarRefresh').addEventListener('click',refresh);
 window.setInterval(()=>{if(!document.hidden)refresh()},30000);
 window.addEventListener('focus',refresh);
 render();details();
})();
</script>
