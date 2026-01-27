<?php
// api.php - V73.0 (Logic Fixes & Layout)
/*
 * WAR COMMAND - Alliance Tool
 * Copyright (c) 2026 [Denise W.]
 * * LIZENZ: NUR FÜR DEN PRIVATEN GEBRAUCH. KEINE KOMMERZIELLE NUTZUNG.
 * VERKAUF ODER VERMIETUNG DIESER SOFTWARE IST STRENGSTENS UNTERSAGT.
 * * LICENSE: PRIVATE USE ONLY. NO COMMERCIAL USE ALLOWED.
 * SELLING OR RENTING THIS SOFTWARE IS STRICTLY PROHIBITED.
 */

ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(['lifetime'=>86400,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();

// HINWEIS: Diesen Key in einer Produktionsumgebung ändern!
// Er dient als Master-Login, falls DB-User defekt sind.
$master_key = "ChangeMeMasterKey!"; 
$response = ['status'=>'error', 'message'=>'Unbekannter Fehler'];

function getSetting($pdo, $key) {
    try { $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = ?"); $stmt->execute([$key]); return $stmt->fetchColumn() ?: ''; } catch(Exception $e) { return ''; }
}
function getUserRole($pdo) {
    if (!isset($_SESSION['user_id'])) return false;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?"); $stmt->execute([$_SESSION['user_id']]); return $stmt->fetchColumn() ?: false;
}
function sendDiscord($data, $url) {
    if (empty($url) || strpos($url, 'discord.com') === false) return null;
    if (strpos($url, '?wait=true') === false) $url .= '?wait=true';
    
    $cols = [
        'conquer'=>15548997, 'abandon'=>9807270, 'protect'=>3447003, 
        'defend'=>15105570, 'extend_protect'=>3447003,
        'bagger'=>16711680, 'msg_allianz'=>3447003, 'msg_r4'=>10181046, 'reminder'=>16776960 
    ];
    
    $embed = ["color" => $cols[$data['act']] ?? 0, "footer" => ["text" => "By " . ($data['user'] ?? 'System')]];
    
    if ($data['act'] === 'bagger') { 
        $embed['title'] = "🚜 BAGGER ALARM"; 
        $embed['description'] = "**ACHTUNG!** Bagger Aktivitäten gemeldet!\nSofort prüfen!"; 
    } 
    elseif ($data['act'] === 'msg_allianz') { $embed['title'] = "📢 ALLIANZ NACHRICHT"; $embed['description'] = $data['message']; }
    elseif ($data['act'] === 'msg_r4') { $embed['title'] = "🔒 R4 NACHRICHT"; $embed['description'] = $data['message']; }
    elseif ($data['act'] === 'reminder') {
        $embed['title'] = "⏰ TIMER ERINNERUNG";
        $embed['description'] = "Timer **{$data['name']}** läuft in **{$data['mins']} Minuten** ab!";
        $embed["fields"] = [ ["name"=>"Server","value"=>$data['server'],"inline"=>true], ["name"=>"Pos","value"=>$data['coord'],"inline"=>true] ];
    }
    else {
        $titles = [
            'conquer'=>'⚔️ EROBERN', 'abandon'=>'🏳️ AUFGEBEN', 
            'protect'=>'🛡️ SCHUTZ', 'defend'=>'🔥 VERTEIDIGUNG',
            'extend_protect' => '🛡️ SCHUTZ VERLÄNGERN'
        ];
        $embed['title'] = $titles[$data['act']] ?? 'INFO';
        
        $fields = [ 
            ["name"=>"Ziel","value"=>$data['name'],"inline"=>true], 
            ["name"=>"Server","value"=>$data['server'],"inline"=>true], 
            ["name"=>"Pos","value"=>$data['coord'],"inline"=>true], 
            ["name"=>"Ende","value"=>date("d.m H:i",time()+($data['d']*86400)+($data['h']*3600)+($data['m']*60)),"inline"=>true] 
        ];
        if(!empty($data['alliance'])) {
            array_splice($fields, 1, 0, [["name"=>"Allianz", "value"=>$data['alliance'], "inline"=>true]]);
        }
        $embed["fields"] = $fields;
    }
    
    $ch = curl_init($url); 
    curl_setopt_array($ch, [CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>json_encode(["username"=>"War Command", "embeds"=>[$embed]], JSON_UNESCAPED_UNICODE),CURLOPT_HTTPHEADER=>['Content-Type:application/json'],CURLOPT_RETURNTRANSFER=>1]);
    $res = curl_exec($ch); curl_close($ch); return json_decode($res, true)['id'] ?? null;
}
function deleteDiscordMsg($msgId, $webhookUrl) {
    if (!$msgId || empty($webhookUrl)) return;
    $ch = curl_init(strtok($webhookUrl, '?') . "/messages/$msgId"); curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST=>"DELETE",CURLOPT_RETURNTRANSFER=>1]); curl_exec($ch); curl_close($ch);
}

try {
    if (!file_exists('db_setup.php')) throw new Exception("db_setup.php fehlt!");
    require 'db_setup.php';
    if (!isset($pdo)) throw new Exception("Datenbankverbindung fehlgeschlagen");

    $input = json_decode(file_get_contents('php://input'), true);
    $act = $_GET['action'] ?? '';
    $response = ['status'=>'success']; 

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if(rand(1,5) == 1) { 
            $hooks = ['main' => getSetting($pdo, 'discord_webhook'), 'bagger' => getSetting($pdo, 'bagger_webhook'), 'allianz' => getSetting($pdo, 'allianz_webhook'), 'r4' => getSetting($pdo, 'r4_webhook')];
            
            $timers = $pdo->query("SELECT * FROM timers WHERE end_time > NOW() AND (rem_60 = 0 OR rem_30 = 0)")->fetchAll();
            foreach($timers as $t) {
                $diff = strtotime($t['end_time']) - time(); 
                $mins = floor($diff / 60);
                if($t['rem_60'] == 0 && $mins <= 60 && $mins > 30) {
                    sendDiscord(['act'=>'reminder', 'mins'=>60, 'name'=>$t['target_name'], 'server'=>$t['server_id'], 'coord'=>$t['coord']], $hooks['main']);
                    $pdo->prepare("UPDATE timers SET rem_60=1 WHERE id=?")->execute([$t['id']]);
                }
                if($t['rem_30'] == 0 && $mins <= 30) {
                    sendDiscord(['act'=>'reminder', 'mins'=>30, 'name'=>$t['target_name'], 'server'=>$t['server_id'], 'coord'=>$t['coord']], $hooks['main']);
                    $pdo->prepare("UPDATE timers SET rem_30=1 WHERE id=?")->execute([$t['id']]);
                }
            }

            $expired = $pdo->query("SELECT id, discord_msg_id, action_type FROM timers WHERE end_time < DATE_SUB(NOW(), INTERVAL 30 MINUTE)")->fetchAll();
            if ($expired) {
                foreach ($expired as $t) {
                    $u = $hooks['main']; if($t['action_type']==='bagger')$u=$hooks['bagger']; if($t['action_type']==='msg_allianz')$u=$hooks['allianz']; if($t['action_type']==='msg_r4')$u=$hooks['r4'];
                    try { deleteDiscordMsg($t['discord_msg_id'], $u); } catch(Exception $e){}
                    $pdo->prepare("DELETE FROM timers WHERE id=?")->execute([$t['id']]);
                }
            }
        }
        
        $role = isset($_SESSION['user_id']) ? getUserRole($pdo) : false;

        if ($act === 'backup_db') {
            if ($role === 'R5') {
                ob_clean(); header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="wc_backup.sql"');
                $tables = []; $q = $pdo->query("SHOW TABLES"); while($r = $q->fetch(PDO::FETCH_NUM)) $tables[] = $r[0];
                foreach($tables as $t) {
                    $c = $pdo->query("SHOW CREATE TABLE $t")->fetch(PDO::FETCH_NUM); echo "DROP TABLE IF EXISTS `$t`;\n" . $c[1] . ";\n\n";
                    $rows = $pdo->query("SELECT * FROM $t");
                    while($r = $rows->fetch(PDO::FETCH_ASSOC)) { echo "INSERT INTO `$t` (" . implode(",", array_map(function($k){return "`$k`";}, array_keys($r))) . ") VALUES (" . implode(",", array_map(function($v)use($pdo){return $pdo->quote($v);}, array_values($r))) . ");\n"; }
                }
                exit; 
            } else { throw new Exception("Access Denied"); }
        }
        if ($act === 'get_stats') { $response = ['stats' => $pdo->query("SELECT server_name, rank, CAST(power AS CHAR) as power, alliance_name FROM server_stats ORDER BY server_name, rank")->fetchAll()]; }
        elseif ($act === 'get_settings') {
            if ($role === 'R5') $response = ['discord_webhook' => getSetting($pdo, 'discord_webhook'), 'bagger_webhook' => getSetting($pdo, 'bagger_webhook'), 'allianz_webhook' => getSetting($pdo, 'allianz_webhook'), 'r4_webhook' => getSetting($pdo, 'r4_webhook')];
            else $response = ['error'=>'Unauthorized'];
        }
        elseif ($act === 'get_event_data') {
            if(in_array($role, ['R1','R2','R3','R4','R5'])) {
                $t = ($_GET['type']==='marshall')?'marshall_stats':'duel_stats';
                $data = $pdo->query("SELECT * FROM $t ORDER BY import_date DESC, score DESC LIMIT 15000")->fetchAll();
                $weeks = $pdo->query("SELECT * FROM week_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
                $response = ['data' => $data, 'week_settings' => $weeks];
            } else $response = ['data'=>[]];
        }
        elseif ($act === 'get_unique_names') {
            if(in_array($role, ['R4','R5'])) {
                $q = "SELECT DISTINCT player_name FROM duel_stats UNION SELECT DISTINCT player_name FROM marshall_stats ORDER BY player_name ASC";
                $response = ['names' => $pdo->query($q)->fetchAll(PDO::FETCH_COLUMN)];
            } else $response = ['names'=>[]];
        }
        elseif ($act === 'debug_check') {
            if(in_array($role, ['R4','R5'])) {
                try {
                    $d = ['duel' => $pdo->query("SELECT COUNT(*) FROM duel_stats")->fetchColumn(), 'marshall' => $pdo->query("SELECT COUNT(*) FROM marshall_stats")->fetchColumn()];
                    $response = ['status'=>'success', 'info'=>$d];
                } catch(Exception $e) { $response = ['status'=>'error', 'info'=>$e->getMessage()]; }
            } else $response = ['error'=>'Auth required'];
        }
        else {
            $response = [
                'timers' => $pdo->query("SELECT *, TIMESTAMPDIFF(SECOND, NOW(), end_time) as rem FROM timers WHERE action_type NOT IN ('bagger', 'msg_allianz', 'msg_r4') ORDER BY end_time ASC")->fetchAll(),
                'servers' => $pdo->query("SELECT name FROM servers ORDER BY name ASC")->fetchAll(),
                'auth' => !!$role, 'username' => $_SESSION['username']??'', 'role' => $role, 'isAdmin' => ($role==='R5')
            ];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($act === 'login') {
            $u = trim($input['username']); $p = $input['password'];
            if ($p === $master_key) {
                $user = $pdo->query("SELECT * FROM users WHERE role='R5' LIMIT 1")->fetch();
                if($user) { $_SESSION['user_id']=$user['id']; $_SESSION['username']=$user['username']; $response=['status'=>'success','role'=>$user['role']]; }
                else { $pdo->prepare("INSERT IGNORE INTO users (username, password, role) VALUES ('admin', ?, 'R5')")->execute([password_hash($master_key, PASSWORD_DEFAULT)]); $user = $pdo->query("SELECT * FROM users WHERE username='admin'")->fetch(); $_SESSION['user_id']=$user['id']; $_SESSION['username']=$user['username']; $response=['status'=>'success','role'=>'R5']; }
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?"); $stmt->execute([$u]); $user = $stmt->fetch();
                if ($user && password_verify($p, $user['password'])) { $_SESSION['user_id']=$user['id']; $_SESSION['username']=$u; $response=['status'=>'success','role'=>$user['role']]; } else $response=['status'=>'error','error'=>'Login failed'];
            }
        }
        elseif ($act === 'logout') { session_destroy(); $response=['status'=>'success']; }
        elseif ($act === 'bagger_alarm') {
            $url = getSetting($pdo, 'bagger_webhook'); $data = ['act'=>'bagger', 'user'=>'Anonymous']; $msgId = sendDiscord($data, $url);
            $pdo->prepare("INSERT INTO timers (target_name, action_type, end_time, discord_msg_id) VALUES ('Bagger', 'bagger', DATE_ADD(NOW(), INTERVAL 5 MINUTE), ?)")->execute([$msgId]);
            $response = ['status'=>'success'];
        }
        else {
            $role = getUserRole($pdo);
            if (!$role) throw new Exception("Auth required");

            if ($act === 'send_custom_msg') {
                $type = $input['type']; 
                if ($type === 'r4' && !in_array($role, ['R4', 'R5'])) throw new Exception("Unauthorized for R4");
                $url = getSetting($pdo, $type . '_webhook');
                if ($url) {
                    $msgId = sendDiscord(['act'=>'msg_'.$type, 'user'=>$_SESSION['username'], 'message'=>trim($input['msg'])], $url);
                    if($msgId) $pdo->prepare("INSERT INTO timers (target_name, action_type, end_time, discord_msg_id) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), ?)")->execute([$type==='r4'?'R4':'Ally', 'msg_'.$type, $msgId]);
                    $response = ['status'=>'success'];
                }
            }
            elseif ($act === 'set_week_status' && in_array($role, ['R4','R5'])) {
                $pdo->prepare("REPLACE INTO week_settings (week_id, is_save_week) VALUES (?, ?)")->execute([$input['week_id'], $input['is_save'] ? 1 : 0]);
                $response = ['status'=>'success'];
            }
            elseif ($act === 'delete_event_data' && in_array($role,['R4','R5'])) {
                $t = ($input['type']==='marshall')?'marshall_stats':'duel_stats';
                $d = new DateTime($input['date']); $mo = clone $d; $mo->modify('-'.($d->format('N')-1).' days'); $su = clone $mo; $su->modify('+6 days');
                $pdo->prepare("DELETE FROM $t WHERE import_date BETWEEN ? AND ?")->execute([$mo->format('Y-m-d'), $su->format('Y-m-d')]);
                $response = ['status'=>'success'];
            }
            elseif ($act === 'update_event_name' && in_array($role, ['R4','R5'])) {
                $t = ($input['type'] === 'marshall') ? 'marshall_stats' : 'duel_stats';
                $pdo->prepare("UPDATE $t SET player_name = ? WHERE id = ?")->execute([trim($input['newName']), $input['id']]);
                $response = ['status' => 'success'];
            }
            elseif ($act === 'import_data' && in_array($role,['R4','R5'])) {
                $t=($input['type']==='marshall')?'marshall_stats':'duel_stats';
                $pdo->beginTransaction(); $pdo->prepare("DELETE FROM $t WHERE import_date=?")->execute([$input['date']]);
                $stmt=$pdo->prepare("INSERT INTO $t (player_name,score,import_date) VALUES (?,?,?)");
                $check=$pdo->prepare("SELECT id FROM users WHERE username=?");
                $addUser=$pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'R2')");
                $defPass = password_hash("1234", PASSWORD_DEFAULT);
                $l = preg_split("/\r\n|\n|\r/", $input['csv']); $c=0;
                foreach($l as $line) {
                    if(!trim($line)) continue; $p=(strpos($line,';;;')!==false)?explode(';;;',$line):preg_split('/[\t]+/',$line);
                    if(count($p)>=2 && trim($p[0])) { 
                        $stmt->execute([trim($p[0]),preg_replace('/[^0-9]/','',$p[1]),$input['date']]); $c++;
                        if($input['type']==='duel') { $check->execute([trim($p[0])]); if(!$check->fetch()) $addUser->execute([trim($p[0]), $defPass]); }
                    }
                }
                $pdo->commit(); $response=['status'=>'success', 'count'=>$c];
            }
            elseif ($act === 'add_timer') {
                if(!in_array($role, ['R4','R5'])) throw new Exception("Unauthorized");
                $d=intval($input['d']??0); $h=intval($input['h']??0); $m=intval($input['m']??0); if($d==0&&$h==0&&$m==0)$h=24;
                
                $alliance = $input['alliance'] ?? '';
                $color = (!empty($input['color'])) ? $input['color'] : null;

                $msgId = sendDiscord(['act'=>$input['act'], 'user'=>$_SESSION['username'], 'name'=>$input['name'], 'server'=>$input['server'], 'coord'=>strtoupper($input['coord']), 'd'=>$d, 'h'=>$h, 'm'=>$m, 'alliance'=>$alliance], getSetting($pdo, 'discord_webhook'));
                
                $pdo->prepare("INSERT INTO timers (server_id,target_name,alliance,coord,x,y,action_type,discord_msg_id,color,end_time) VALUES (?,?,?,?,?,?,?,?,?,DATE_ADD(NOW(),INTERVAL ? SECOND))")
                    ->execute([$input['server'],$input['name'],$alliance,strtoupper($input['coord']),$input['x'],$input['y'],$input['act'],$msgId,$color,($d*86400)+($h*3600)+($m*60)]);
                $response=['status'=>'success'];
            }
            elseif ($act === 'delete_timer') { 
                if(!in_array($role, ['R4','R5'])) throw new Exception("Unauthorized");
                $stmt = $pdo->prepare("SELECT discord_msg_id FROM timers WHERE id=?"); $stmt->execute([$input['id']]); $msgId = $stmt->fetchColumn();
                if($msgId) try { deleteDiscordMsg($msgId, getSetting($pdo, 'discord_webhook')); } catch(Exception $e){}
                $pdo->prepare("DELETE FROM timers WHERE id=?")->execute([$input['id']]); $response=['status'=>'success']; 
            }
            elseif ($act === 'save_stats') {
                if(!in_array($role, ['R4','R5'])) throw new Exception("Unauthorized");
                $stmt=$pdo->prepare("INSERT INTO server_stats (server_name,rank,power,alliance_name) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE power=?, alliance_name=?");
                $pdo->beginTransaction(); foreach($input['data'] as $r) $stmt->execute([$r['server'],$r['rank'],$r['power'],$r['name'],$r['power'],$r['name']]); $pdo->commit(); $response=['status'=>'success'];
            }
            elseif ($act === 'save_settings' && $role === 'R5') {
                foreach(['discord_webhook','bagger_webhook','allianz_webhook','r4_webhook'] as $k) $pdo->prepare("REPLACE INTO settings (key_name, value) VALUES (?, ?)")->execute([$k, $input[$k]]);
                $response=['status'=>'success'];
            }
            elseif ($act === 'change_own_password') { 
                 $stmt=$pdo->prepare("SELECT password FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]);
                 if(password_verify($input['old_pass'], $stmt->fetchColumn()) || $input['old_pass'] === $master_key) { 
                     $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($input['new_pass'],PASSWORD_DEFAULT),$_SESSION['user_id']]); $response=['status'=>'success']; 
                 } else $response=['status'=>'error','error'=>'Wrong password'];
            }
            elseif (in_array($role,['R4','R5'])) {
                if($act==='get_users') $response=['users'=>$pdo->query("SELECT id,username,role FROM users ORDER BY role DESC")->fetchAll()];
                if($act==='add_user') { $pdo->prepare("INSERT INTO users (username,password,role) VALUES (?,?,?)")->execute([$input['username'],password_hash($input['password'],PASSWORD_DEFAULT),$input['role']]); $response=['status'=>'success']; }
                if($act==='delete_user' && $input['id']!=$_SESSION['user_id']) { $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$input['id']]); $response=['status'=>'success']; }
                if($act==='update_user_role' && $input['id']!=$_SESSION['user_id']) { $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$input['role'],$input['id']]); $response=['status'=>'success']; }
                if($act==='add_server') try{$pdo->prepare("INSERT INTO servers (name) VALUES (?)")->execute([$input['name']]); $response=['status'=>'success'];}catch(E $e){}
                if($act==='delete_server') { $pdo->prepare("DELETE FROM servers WHERE name=?")->execute([$input['name']]); $response=['status'=>'success']; }
            }
        }
    }
} catch (Exception $e) { $response = ['status'=>'error','error'=>$e->getMessage()]; }
ob_end_clean(); 
echo json_encode($response);
?>