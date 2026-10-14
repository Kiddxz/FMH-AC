<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) start_secure_session();

function ui_role_urls(string $role): array {
    return [
        'search' => 'search.php',
        'messages' => 'messages.php',
        'notifications' => 'notifications.php',
        'calendar' => $role==='superadmin' ? 'calendar.php' : 'appointments.php'.($role==='assistant' ? '?view=calendar' : ''),
        'profile' => 'profile.php',
    ];
}
function ui_badges(mysqli $conn, string $role, int $uid): array {
    $messages = 0; $notifications = 0;
    if ($uid) {
        $st=$conn->prepare('SELECT COUNT(*) c FROM user_messages WHERE recipient_id=? AND is_read=0');
        if($st){$st->bind_param('i',$uid);$st->execute();$messages=(int)($st->get_result()->fetch_assoc()['c']??0);$st->close();}
    }
    if($role==='patient'){
        $st=$conn->prepare('SELECT COUNT(*) c FROM notifications WHERE patient_id=? AND is_read=0');
        if($st){$st->bind_param('i',$uid);$st->execute();$notifications=(int)($st->get_result()->fetch_assoc()['c']??0);$st->close();}
    }elseif($role==='assistant'||$role==='dentist'){
        // Stored staff notifications (new bookings, cancellations, walk-ins) that this user has not opened yet.
        $st=$conn->prepare("SELECT COUNT(*) c FROM staff_notifications WHERE user_id=? AND is_read=0");
        if($st){$st->bind_param('i',$uid);$st->execute();$notifications=(int)($st->get_result()->fetch_assoc()['c']??0);$st->close();}
    }elseif($role==='superadmin'){
        $r=$conn->query("SELECT COUNT(*) c FROM contact_messages WHERE status='new'");$notifications=$r?(int)($r->fetch_assoc()['c']??0):0;
    }
    return ['messages'=>$messages,'notifications'=>$notifications];
}
function ui_header(string $title, string $role=''): void {
    global $conn;
    $name=e($_SESSION['first_name']??'User'); $last=e($_SESSION['last_name']??'');
    $r=$role ?: ($_SESSION['role']??''); $uid=(int)($_SESSION['user_id']??0);
    $urls=ui_role_urls($r); $badges=ui_badges($conn,$r,$uid);
    $GLOBALS['ui_message_badge']=$badges['messages'];
    // Patients, assistants and dentists keep notifications and their profile in the top bar; messaging lives in the
    // floating chat button. The search bar and calendar shortcut are only kept for the super admin portal.
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0d5f68"><title><?=e($title)?> | SupremeDent</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="../assets/css/revamp.css"><link rel="stylesheet" href="../assets/css/supremedent_refined.css"><link rel="stylesheet" href="../assets/css/ui_fixes.css"><link rel="stylesheet" href="../assets/css/revisions_2026_10.css?v=20261002"><link rel="stylesheet" href="../assets/css/revision2_2026_10.css?v=20261009"></head><body class="app-page sd-refined role-<?=e($r)?>"><header class="app-topbar"><a class="app-brand" href="dashboard.php" aria-label="SupremeDent dashboard"><img src="../assets/images/logo.png" alt="SupremeDent"><span><strong>SupremeDent</strong><small>DENTAL CLINIC</small></span></a><div class="topbar-tools"><?php if($r==='superadmin'):?><form class="topbar-search" action="<?=e($urls['search'])?>" method="get"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" placeholder="Search" aria-label="Search SupremeDent" value="<?=e($_GET['q']??'')?>"><button type="submit" aria-label="Search"><i class="fa-solid fa-arrow-right"></i></button></form><?php endif;?><a class="topbar-icon" id="sdBell" href="<?=e($urls['notifications'])?>" title="Notifications" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span id="sdBellBadge" <?=$badges['notifications']>0?'':'hidden'?>><?=min(99,$badges['notifications'])?></span></a><?php if($r==='superadmin'):?><a class="topbar-icon" href="<?=e($urls['calendar'])?>" title="Calendar" aria-label="Calendar"><i class="fa-regular fa-calendar-days"></i></a><?php endif;?><a class="profile-link topbar-profile" href="<?=e($urls['profile'])?>" title="Open profile settings" aria-label="Open profile settings"><span class="user-identity"><strong><?=$name?><?=$last!==''?' '.$last:''?></strong><small><?=e(ucfirst($r))?></small></span><span class="user-avatar" aria-hidden="true"><?=e(strtoupper(substr($name,0,1)))?></span></a></div></header><div class="app-shell">
<?php }
function ui_sidebar(string $role,string $active): void { global $conn; $menus=[]; $icons=['Dashboard'=>'fa-chart-pie','Appointments'=>'fa-calendar-days','Calendar'=>'fa-calendar','Dental Records'=>'fa-file-medical','Dental Chart'=>'fa-cube','Treatment History'=>'fa-clock-rotate-left','Treatment Plans'=>'fa-clipboard-list','Notifications'=>'fa-bell','Dental Condition Map'=>'fa-tooth','Patient Concerns'=>'fa-tooth','Profile'=>'fa-user-gear','Patients'=>'fa-users','Register Patient'=>'fa-user-plus','Dental Charts'=>'fa-teeth','Create Chart'=>'fa-file-circle-plus','3D Odontogram'=>'fa-cube','Laboratory'=>'fa-flask','Patient History'=>'fa-notes-medical','Reports'=>'fa-chart-column','Users'=>'fa-users-gear','Roles & Permissions'=>'fa-shield-halved','Activity Logs'=>'fa-list-check','Website Content'=>'fa-globe','Inquiries'=>'fa-envelope','System Monitoring'=>'fa-heart-pulse','Maintenance'=>'fa-screwdriver-wrench','Backup & Recovery'=>'fa-database','System Updates'=>'fa-arrows-rotate','Email / OTP Test'=>'fa-paper-plane'];
    // Pages that belong to a sidebar entry without being that entry's own file.
    $aliases=['patient'=>['dental_chart.php'=>'appointments.php','tooth_concerns.php'=>'appointments.php','chart_view.php'=>'dental_records.php'],'dentist'=>['chart_editor.php'=>'dental_charts.php','charts.php'=>'dental_charts.php','chart_view.php'=>'dental_charts.php','appointment_view.php'=>'appointments.php'],'assistant'=>['charts.php'=>'chart_editor.php','appointment_view.php'=>'appointments.php','calendar.php'=>'appointments.php']];
    $active=$aliases[$role][$active]??$active;
    if($role==='patient') $menus=[['dashboard.php','Dashboard'],['appointments.php','Appointments'],['dental_records.php','Dental Records'],['treatment_history.php','Treatment History'],['treatment_plans.php','Treatment Plans']];
    elseif($role==='assistant') $menus=[['dashboard.php','Dashboard'],['appointments.php','Appointments'],['patients.php','Patients'],['chart_editor.php','Create Chart'],['register_patient.php','Register Patient']];
    elseif($role==='dentist') { $menus=[['dashboard.php','Dashboard'],['appointments.php','Appointments'],['patients.php','Patients'],['dental_charts.php','Dental Charts'],['treatment_plans.php','Treatment Plans']]; $settings=clinic_settings($conn); if(($settings['laboratory_enabled']??'0')==='1')$menus[]=['laboratory.php','Laboratory']; $menus=array_merge($menus,[['patient_history.php','Patient History'],['reports.php','Reports']]); }
    else $menus=[['dashboard.php','Dashboard'],['calendar.php','Appointments'],['users.php','Users'],['roles.php','Roles & Permissions'],['activity_logs.php','Activity Logs'],['website_content.php','Website Content'],['inquiries.php','Inquiries'],['mail_test.php','Email / OTP Test'],['monitoring.php','System Monitoring'],['maintenance.php','Maintenance'],['backup.php','Backup & Recovery'],['updates.php','System Updates']]; ?>
<aside class="app-sidebar"><a class="sidebar-caption sidebar-home" href="dashboard.php"><span><?=e(strtoupper($role))?> PORTAL</span></a><nav><?php foreach($menus as $m): $isActive=basename($m[0])===$active; ?><a class="<?=$isActive?'active':''?>" <?=$isActive?'aria-current="page"':''?> href="<?=e($m[0])?>"><i class="fa-solid <?=$icons[$m[1]]??'fa-circle'?>" aria-hidden="true"></i><span><?=e($m[1])?></span></a><?php endforeach; ?></nav><div class="sidebar-bottom"><a class="sidebar-logout" href="../auth/logout.php?portal=<?=e($role)?>"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Logout</span></a></div></aside><main class="app-main">
<?php }
function ui_footer(): void {
    $uid=(int)($_SESSION['user_id']??0);
    $role=(string)($_SESSION['role']??'');
?></main></div><?php if($uid && in_array($role,['patient','assistant','dentist','superadmin'],true)):?>
<button type="button" class="sd-chat-fab" id="sdChatFab" aria-label="Open messages" aria-haspopup="dialog" aria-controls="sdChatPanel"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><span class="sd-chat-badge" id="sdChatBadge" <?=($GLOBALS['ui_message_badge']??0)>0?'':'hidden'?>><?=min(99,(int)($GLOBALS['ui_message_badge']??0))?></span></button>
<div class="sd-chat-panel" id="sdChatPanel" role="dialog" aria-modal="false" aria-labelledby="sdChatTitle" hidden data-role="<?=e($role)?>" data-csrf="<?=e(csrf_token())?>" data-api="../api/messages.php?portal=<?=e($role)?>">
    <div class="sd-chat-head"><div><span class="eyebrow">MESSAGES</span><h2 id="sdChatTitle">Message Center</h2><p></p></div><div class="sd-chat-head-actions"><button type="button" class="btn btn-primary" id="sdChatNew"><i class="fa-solid fa-pen-to-square"></i> New Message</button><button type="button" class="sd-chat-close" id="sdChatClose" aria-label="Close messages"><i class="fa-solid fa-xmark"></i></button></div></div>
    <div class="sd-chat-body">
        <aside class="sd-chat-list" aria-label="Conversation history"><h3>Conversations</h3><div id="sdChatConversations" class="sd-chat-conversations"><div class="sd-chat-empty">Loading…</div></div></aside>
        <section class="sd-chat-thread" id="sdChatThread" aria-live="polite"><div class="sd-chat-placeholder"><i class="fa-regular fa-comments"></i><p>Select a conversation or start a new message.</p></div></section>
    </div>
    <div class="sd-chat-compose" id="sdChatCompose" hidden>
        <form class="sd-compose-card" id="sdComposeForm" novalidate>
            <button type="button" class="sd-compose-close" id="sdComposeClose" aria-label="Close new message"><i class="fa-solid fa-xmark"></i></button>
            <div class="sd-compose-top"><div><h3 id="sdComposeTitle">New Message</h3><p class="muted"></p></div><div class="sd-compose-switch" role="group" aria-label="Choose who to message"><button type="button" id="sdComposePrev" aria-label="Previous recipient type"><i class="fa-solid fa-chevron-left"></i></button><button type="button" id="sdComposeNext" aria-label="Next recipient type"><i class="fa-solid fa-chevron-right"></i></button></div></div>
            <div class="sd-compose-tabs" id="sdComposeTabs"></div>
            <label for="sdComposeRecipient">Recipient</label><select id="sdComposeRecipient" name="recipient_id" required></select>
            <label for="sdComposeSubject">Subject</label><input id="sdComposeSubject" name="subject" maxlength="180" required>
            <label for="sdComposeMessage">Message</label><textarea id="sdComposeMessage" name="message" maxlength="3000" required></textarea>
            <div class="sd-compose-error" id="sdComposeError" role="alert"></div>
            <button type="submit" class="btn btn-primary" id="sdComposeSend"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
        </form>
    </div>
</div>
<script src="../assets/js/message_widget.js?v=20261010" defer></script>
<?php require_once __DIR__ . '/live.php'; global $conn; $liveState = sd_live_state($conn, $role, $uid); ?>
<script src="../assets/js/live_updates.js?v=20261009" data-version="<?=e($liveState['version'])?>" data-api="../api/live.php?portal=<?=e($role)?>"></script>
<?php endif;?></body></html><?php }
function flash(string $type,string $msg): void { echo '<div class="flash '.e($type).'">'.e($msg).'</div>'; }
