<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
// hide all error
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  include ('./include/version.php');

  $btnmenuactive = "font-weight: bold;background-color: #f9f9f9; color: #000000";
  if ($hotspot == "dashboard" || substr(end(explode("/", $url)), 0, 8) == "?session") {
    $shome = "active";
    $mpage = $_dashboard;
  } elseif ($ipmenu == "addresses") {
    $mip = "active";
    $sipaddr = "active";
    $mpage = "IP Addresses & Interface";
    $ipsubmenu = "menu-open";
  } elseif ($ipmenu == "hotspot-setup") {
    $mip = "active";
    $siphotsetup = "active";
    $mpage = "Hotspot Setup Wizard";
    $ipsubmenu = "menu-open";
  } elseif ($hotspot == "quick-print" || $hotspot == "list-quick-print") {
    $squick = "active";
    $mpage = $_quick_print;   
  } elseif ($hotspot == "users" || $userbyprofile != "" || $hotspot == "export-users" || $removehotspotuserbycomment != "" || $removehotspotuser != "" || $removehotspotusers != "" || $disablehotspotuser || $enablehotspotuser != "") {
    $susersl = "active";
    $susers = "active";
    $mpage = $_users;
    $umenu = "menu-open";
  } elseif ($hotspotuser == "add") {
    $sadduser = "active";
    $mpage = $_users;
    $susers = "active";
    $umenu = "menu-open";
  } elseif ($hotspotuser == "generate") {
    $sgenuser = "active";
    $mpage = $_users;
    $susers = "active";
    $umenu = "menu-open";
  } elseif ($userbyname != "" || $resethotspotuser != "") {
    $susers = "active";
    $mpage = $_users;
    $umenu = "menu-open";
  } elseif ($hotspot == "user-profiles") {
    $suserprofiles = "active";
    $suserprof = "active";
    $mpage = $_user_profile;
    $upmenu = "menu-open";
  } elseif ($hotspot == "active" || $removeuseractive != "") {
    $sactive = "active";
    $mpage = $_hotspot_active;
    $hamenu = "menu-open";
  } elseif ($hotspot == "top-bandwidth") {
    $stopbw = "active";
    $mpage = "Top Bandwidth User";
    $hamenu = "menu-open";
  } elseif ($hotspot == "hosts" || $hotspot == "hostp" || $hotspot == "hosta" || $removehost != "") {
    $shosts = "active";
    $mpage = $_hosts;
    $hmenu = "menu-open";
  } elseif ($hotspot == "dhcp-leases") {
    $slease = "active";
    $mpage = $_dhcp_leases;
  } elseif ($hotspot == "siteblock") {
    $ssiteblock = "active";
    $mpage = "Blokir Situs";
  } elseif ($minterface == "traffic-monitor") {
    $strafficmonitor = "active";
    $mpage = $_traffic_monitor;  
  } elseif ($hotspot == "ipbinding" || $hotspot == "binding" || $removeipbinding != "" || $enableipbinding != "" || $disableipbinding != "") {
    $sipbind = "active";
    $mpage = $_ip_bindings;
    $ibmenu = "menu-open";
  } elseif ($hotspot == "template-editor") {
    $ssett = "active";
    $teditor = "active";
    $mpage = $_template_editor;
    $settmenu = "menu-open";
  } elseif ($hotspot == "uplogo") {
    $ssett = "active";
    $uplogo = "active";
    $mpage = $_upload_logo;
    $settmenu = "menu-open";
  } elseif ($hotspot == "cookies" || $removecookie != "") {
    $scookies = "active";
    $mpage = $_hotspot_cookies;
    $cmenu = "menu-open";
  } elseif ($hotspot == "log") {
    $log = "active";
    $slog = "active";
    $mpage = $_hotspot_log;
    $lmenu = "menu-open";
  } elseif ($report == "userlog") {
    $log = "active";
    $sulog = "active";
    $mpage = $_user_log;
    $lmenu = "menu-open";
  } elseif ($ppp == "secrets" || $ppp == "addsecret" || $enablesecr != "" || $disablesecr != "" || $removesecr != "" || $secretbyname != "") {
    $mppp = "active";
    $ssecrets = "active";
    $mpage = !empty($_ppp_secrets) ? $_ppp_secrets : "PPP Secrets";
    $pppmenu = "menu-open";
  } elseif ($ppp == "profiles" || $removepprofile != "" || $ppp == "add-profile" || $ppp == "edit-profile") {
    $mppp = "active";
    $spprofile = "active";
    $mpage = !empty($_ppp_profiles) ? $_ppp_profiles : "PPP Profiles";
    $pppmenu = "menu-open";
  } elseif ($ppp == "active" || $removepactive != "") {
    $mppp = "active";
    $spactive = "active";
    $mpage = !empty($_ppp_active) ? $_ppp_active : "PPP Active";
    $pppmenu = "menu-open";
  } elseif ($ppp == "servers") {
    $mppp = "active";
    $spservers = "active";
    $mpage = "PPPoE Servers";
    $pppmenu = "menu-open";
  } elseif ($ppp == "firewall") {
    $mppp = "active";
    $spfirewall = "active";
    $mpage = "Firewall & NAT PPPoE";
    $pppmenu = "menu-open";
  } elseif ($ppp == "logs") {
    $mppp = "active";
    $splogs = "active";
    $mpage = "PPP Connection Logs";
    $pppmenu = "menu-open";
  } elseif ($ppp == "export") {
    $mppp = "active";
    $spexport = "active";
    $mpage = "Export & Daftar IP ONT";
    $pppmenu = "menu-open";
  } elseif ($ppp == "ont-monitor") {
    $mppp = "active";
    $spont = "active";
    $mpage = "ONT Status & Ping Monitor";
    $pppmenu = "menu-open";
  } elseif ($sys == "exam-mode") {
    $sysmenu = "active";
    $sexammode = "active";
    $mpage = "Mode Ujian / ANBK";
    $schmenu = "menu-open";
  } elseif ($sys == "terminal") {
    $sterminal = "active";
    $mpage = "Terminal";
  } elseif ($sys == "qos") {
    $sqos = "active";
    $mpage = "Manajemen Bandwidth & QoS";
  } elseif ($sys == "netwatch") {
    $sysmenu = "active";
    $snetwatch = "active";
    $mpage = "AP Monitor (Netwatch)";
    $schmenu = "menu-open";
  } elseif ($sys == "scheduler" || $enablesch != "" || $disablesch != "" || $removesch != "") {
    $sysmenu = "active";
    $ssch = "active";
    $mpage = $_system_scheduler;
    $schmenu = "menu-open";
  } elseif ($report == "selling" || $report == "resume-report") {
    $sselling = "active";
    $mpage = $_report;
  } elseif ($userprofile == "add") {
    $suserprof = "active";
    $sadduserprof = "active";
    $mpage = $_user_profile;
    $upmenu = "menu-open";
  } elseif ($userprofilebyname != "") {
    $suserprof = "active";
    $mpage = $_user_profile;
    $upmenu = "menu-open";
  } elseif ($hotspot == "users-by-profile") {
    $susersbp = "active";
    $mpage = $_vouchers;
  } elseif ($userbyname != "") {
    $mpage = $_users;
    $susers = "active";
  } elseif ($hotspot == "about") {
    $mpage = $_about;
    $sabout = "active";
  } elseif ($id == "sessions" || $id == "remove" || $router == "new") {
    $ssesslist = "active";
    $mpage = $_admin_settings;
  } elseif ($id == "settings" && $session == "new") {
    $snsettings = "active";
    $mpage = $_add_router;
  } elseif ($id == "settings" || $id == "connect") {
    $ssettings = "active";
    $mpage = $_session_settings;
  } elseif ($id == "about") {
    $sabout = "active";
    $mpage = $_about;
  } elseif ($id == "uplogo") {
    $suplogo = "active";
    $mpage = $_upload_logo;
  } elseif ($id == "editor") {
    $seditor = "active";
    $mpage = $_template_editor;
  }
}

if($idleto != "disable"){
  $didleto = 'display:inline-flex;';
}else{
  $didleto = 'display:none;';
}
?>
<style>
  /* Luruskan garis vertikal pembatas antara brand atas & sidebar */
  #navbar,
  #navbar .custom-brand,
  #sidenav {
    box-sizing: border-box !important;
  }

  /* Kunci batas bawah sidebar & aktifkan scroll vertikal agar menu About selalu terjangkau */
  #sidenav {
    bottom: 0 !important;
    height: auto !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    padding-bottom: 28px !important;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
  }

  #sidenav::-webkit-scrollbar {
    width: 5px;
  }

  #sidenav::-webkit-scrollbar-track {
    background: transparent;
  }

  #sidenav::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
  }

  #sidenav::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.35);
  }

  #sidenav .menu-about-bottom {
    margin-bottom: 12px !important;
  }

  @media (min-width: 769px) {
    #navbar .custom-brand {
      width: 240px !important;
      min-width: 240px !important;
      max-width: 240px !important;
    }

    #sidenav:not([style*="width: 0"]) {
      width: 240px !important;
    }
  }

  /* Ikon panah menghadap kanan saat panel belum diklik */
  #sidenav .dropdown-btn .fa-caret-down {
    display: inline-block !important;
    transform: rotate(-90deg) !important;
    transition: transform 0.2s ease !important;
  }

  /* Ikon panah berputar ke bawah saat panel diklik / aktif */
  #sidenav .dropdown-btn.active .fa-caret-down {
    transform: rotate(0deg) !important;
  }
</style>

<span style="display:none;" id="idto"><?= $idleto ;?></span>

<?php if ($id != "") { ?>

<div id="navbar" class="navbar">
  <div class="navbar-left">
    <a id="brand" href="javascript:void(0)">MIKHMON</a>
    <a class="custom-brand" href="javascript:void(0)">
      <img src="img/logo-sekolah.png" alt="Logo Sekolah" class="brand-logo">
      <span class="brand-text">
        <span class="brand-title">MikroTik</span>
        <span class="brand-sub">Hotspot Monitoring</span>
      </span>
    </a>
    <a id="openNav" class="navbar-hover" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
    <a id="closeNav" class="navbar-hover" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
    <a id="cpage" href="javascript:void(0)"><?= $mpage; ?></a>
  </div>
  <div class="navbar-right">
    <span class="idle-badge" title="Idle Timeout" style="<?= $didleto; ?>"><i class="fa fa-clock-o mr-1"></i> <span id="timer"></span></span>
    <select class="slang ses">
      <option><?= $language ?></option>
      <?php 
        $fileList = glob('lang/*');
        foreach($fileList as $filename){
          if(is_file($filename)){
            $filename = substr(explode("/",$filename)[1],0,-4);
            if($filename != "isocodelang"){
              echo '<option value="'.$url.'&setlang=' . $filename . '">'. $isocodelang[$filename]. '</option>'; 
            }   
          }
        }
      ?>
    </select>
    <select class="stheme ses">
      <option><?= $_theme ?></option>
      <?php for ($i = 0; $i < count($mtheme); $i++) {
        echo '<option value="'.$url.'&set-theme='.$mtheme[$i].'">'.ucfirst($mtheme[$i]).'</option>';
      } ?>
    </select>
    <a id="logout" href="./admin.php?id=logout"><i class="fa fa-sign-out mr-1"></i> <?= $_logout ?></a>
  </div>
</div>

<div id="sidenav" class="sidenav">
<?php if (($id == "settings" && $session == "new") || ($id == "settings" && $router == "new")) {
} else if ($id == "settings" || $id == "editor" || $id == "uplogo" || $id == "connect"){ ?>  
  <div class="sidebar-identity">
    <small>Session</small>
    <h3 id="MikhmonSession"><?= $session; ?></h3>
  </div>
  <a class="connect menu <?= $shome; ?>" id="<?= $session; ?>&c=settings"><i class='fa fa-tachometer'></i> <?= $_dashboard ?></a>
  <a href="./admin.php?id=settings&session=<?= $session; ?>" class="menu <?= $ssettings; ?>" title="Mikhmon Settings"><i class='fa fa-gear'></i> <?= $_session_settings ?></a>
  <a href="./admin.php?id=uplogo&session=<?= $session; ?>" class="menu <?= $suplogo; ?>"><i class="fa fa-upload"></i> <?= $_upload_logo ?></a>
  <a href="./admin.php?id=editor&template=default&session=<?= $session; ?>" class="menu <?= $seditor; ?>"><i class="fa fa-edit"></i> <?= $_template_editor ?></a>
  <div class="menu spa"></div>
<?php } ?>  
  <a href="./admin.php?id=sessions" class="menu <?= $ssesslist; ?>"><i class="fa fa-server"></i> <?= $_admin_settings ?></a>
  <a href="./admin.php?id=settings&router=new-<?= rand(1111,9999) ?>" class="menu <?= $snsettings ?>"><i class="fa fa-plus"></i> <?= $_add_router ?></a>
  <a href="./admin.php?id=about" class="menu menu-about-bottom <?= $sabout; ?>"><i class="fa fa-info-circle"></i> <?= $_about ?></a>
</div>

<script>
$(document).ready(function(){
  $(".connect").click(function(){
    notify("<?= $_connecting ?>");
    connect(this.id)
  });
  $(".stheme").change(function(){
    notify("<?= $_loading_theme ?>");
    stheme(this.value)
  });
  $(".slang").change(function(){
    notify("<?= $_loading ?>");
    stheme(this.value)
  });
});
</script>
<div id="notify"><div class="message"></div></div>
<div id="temp"></div>
<?php 
include('./info.php');
} else { ?>

<div id="navbar" class="navbar">
  <div class="navbar-left">
    <a id="brand" href="./?session=<?= $session; ?>">MIKHMON</a>
    <a class="custom-brand" href="./?session=<?= $session; ?>">
      <img src="img/logo-sekolah.png" alt="Logo Sekolah" class="brand-logo">
      <span class="brand-text">
        <span class="brand-title">MikroTik</span>
        <span class="brand-sub">Hotspot Monitoring</span>
      </span>
    </a>
    <a id="openNav" class="navbar-hover" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
    <a id="closeNav" class="navbar-hover" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
    <a id="cpage" href="javascript:void(0)"><?= $mpage; ?></a>
  </div>
  <div class="navbar-right">
    <span class="idle-badge" title="Idle Timeout" style="<?= $didleto; ?>"><i class="fa fa-clock-o mr-1"></i> <span id="timer"></span></span>
    <select class="connect optfa ses">
      <option id="MikhmonSession" value="<?= $session; ?>"><?= $hotspotname; ?></option>
      <?php
      foreach (file('./include/config.php') as $line) {
        $sesname = explode("'", $line)[1];
        if ($sesname != "" && $sesname != "mikhmon") {
          if($sesname == $session){
            echo '<option value="' . $sesname. '">'.$sesname. ' &#x2666;</option>';
          }else{
            echo '<option value="' . $sesname. '">'.$sesname. '</option>';
          }
        }
      }
      ?>
    </select>
    <select class="stheme ses">
      <option><?= $_theme ?></option>
      <?php for ($i = 0; $i < count($mtheme); $i++) {
        echo '<option value="'.$url.'&set-theme='.$mtheme[$i].'">'.ucfirst($mtheme[$i]).'</option>';
      } ?>
    </select>
    <a id="logout" href="./?hotspot=logout&session=<?= $session; ?>"><i class="fa fa-sign-out mr-1"></i> <?= $_logout ?></a>
  </div>
</div>

<div id="sidenav" class="sidenav">
  <div class="sidebar-identity">
    <small>Router Identity</small>
    <h3><?= $identity; ?></h3>
  </div>
  <a href="./?session=<?= $session; ?>" class="menu <?= $shome; ?>"><i class="fa fa-dashboard"></i> <?= $_dashboard ?></a>
  
  <!-- IP & Interface -->
  <div class="dropdown-btn <?= $mip; ?>"><i class="fa fa-globe"></i> IP &amp; Interface
    <i class="fa fa-caret-down"></i>
  </div>
  <div class="dropdown-container <?= $ipsubmenu; ?>">
    <a href="./?ip=addresses&session=<?= $session; ?>" class="<?= $sipaddr; ?>"><i class="fa fa-plug"></i> IP Addresses</a>
    <a href="./?ip=hotspot-setup&session=<?= $session; ?>" class="<?= $siphotsetup; ?>"><i class="fa fa-magic"></i> Hotspot Setup</a>
  </div>

  <!-- Hotspot -->
  <div class="dropdown-btn <?= $susers . $suserprof . $sactive . $stopbw . $shosts . $sipbind . $scookies; ?>"><i class="fa fa-wifi"></i> Hotspot
    <i class="fa fa-caret-down"></i>
  </div>
  <div class="dropdown-container <?= $umenu . $upmenu . $hamenu . $hmenu . $ibmenu . $cmenu; ?>">
    <!-- Users --> 
    <div class="dropdown-btn <?= $susers; ?>"><i class="fa fa-users"></i> <?= $_users ?>
      <i class="fa fa-caret-down"></i>
    </div>
    <div class="dropdown-container <?= $umenu; ?>">
      <a href="./?hotspot=users&profile=all&session=<?= $session; ?>" class="<?= $susersl; ?>"><i class="fa fa-list"></i> <?= $_user_list ?></a>
      <a href="./?hotspot-user=add&session=<?= $session; ?>" class="<?= $sadduser; ?>"><i class="fa fa-user-plus"></i> <?= $_add_user ?></a>
      <a href="./?hotspot-user=generate&session=<?= $session; ?>" class="<?= $sgenuser; ?>"><i class="fa fa-ticket"></i> <?= $_generate ?></a>        
    </div>
    <!-- Profile -->
    <div class="dropdown-btn <?= $suserprof; ?>"><i class="fa fa-pie-chart"></i> <?= $_user_profile ?>
      <i class="fa fa-caret-down"></i>
    </div>
    <div class="dropdown-container <?= $upmenu; ?>">
      <a href="./?hotspot=user-profiles&session=<?= $session; ?>" class="<?= $suserprofiles; ?>"><i class="fa fa-list"></i> <?= $_user_profile_list ?></a>
      <a href="./?user-profile=add&session=<?= $session; ?>" class="<?= $sadduserprof; ?>"><i class="fa fa-plus-square"></i> <?= $_add_user_profile ?></a>
    </div>
    <!-- Active -->
    <a href="./?hotspot=active&session=<?= $session; ?>" class="menu <?= $sactive; ?>"><i class="fa fa-wifi"></i> <?= $_hotspot_active ?></a>
    <!-- Top Bandwidth -->
    <a href="./?hotspot=top-bandwidth&session=<?= $session; ?>" class="menu <?= $stopbw; ?>"><i class="fa fa-tachometer"></i> Top Bandwidth</a>
    <!-- Hosts -->
    <a href="./?hotspot=hosts&session=<?= $session; ?>" class="menu <?= $shosts; ?>"><i class="fa fa-laptop"></i> <?= $_hosts ?></a>
    <!-- IP Bindings -->
    <a href="./?hotspot=ipbinding&session=<?= $session; ?>" class="menu <?= $sipbind; ?>"><i class="fa fa-address-book"></i> <?= $_ip_bindings ?></a>
    <!-- Cookies -->
    <a href="./?hotspot=cookies&session=<?= $session; ?>" class="menu <?= $scookies; ?>"><i class="fa fa-hourglass"></i> <?= $_hotspot_cookies ?></a>
  </div>

  <!-- PPPoE -->
  <div class="dropdown-btn <?= $mppp; ?>"><i class="fa fa-exchange"></i> PPPoE
    <i class="fa fa-caret-down"></i>
  </div>
  <div class="dropdown-container <?= $pppmenu; ?>">
    <a href="./?ppp=servers&session=<?= $session; ?>" class="<?= $spservers; ?>"><i class="fa fa-server"></i> PPPoE Servers</a>
    <a href="./?ppp=secrets&session=<?= $session; ?>" class="<?= $ssecrets; ?>"><i class="fa fa-key"></i> <?= !empty($_ppp_secrets) ? $_ppp_secrets : 'PPP Secrets'; ?></a>
    <a href="./?ppp=profiles&session=<?= $session; ?>" class="<?= $spprofile; ?>"><i class="fa fa-sliders"></i> <?= !empty($_ppp_profiles) ? $_ppp_profiles : 'PPP Profiles'; ?></a>
    <a href="./?ppp=active&session=<?= $session; ?>" class="<?= $spactive; ?>"><i class="fa fa-plug"></i> <?= !empty($_ppp_active) ? $_ppp_active : 'PPP Active'; ?></a>
    <a href="./?ppp=firewall&session=<?= $session; ?>" class="<?= $spfirewall; ?>"><i class="fa fa-shield"></i> Firewall &amp; NAT</a>
    <a href="./?ppp=logs&session=<?= $session; ?>" class="<?= $splogs; ?>"><i class="fa fa-history"></i> PPP Logs</a>  
    <a href="./?ppp=ont-monitor&session=<?= $session; ?>" class="<?= $spont; ?>"><i class="fa fa-heartbeat"></i> ONT Monitor</a> 
    <a href="./?ppp=export&session=<?= $session; ?>" class="<?= $spexport; ?>"><i class="fa fa-file-excel-o"></i> Export &amp; IP ONT</a> 
    <a href="./?ppp=dhcpalert&session=<?= $session; ?>" class="<?= isset($spdhcpalert) ? $spdhcpalert : ''; ?>"><i class="fa fa-shield"></i> Anti-NetCut DHCP</a>
  </div>

  <!-- Quick Print -->
  <a href="./?hotspot=quick-print&session=<?= $session; ?>" class="menu <?= $squick; ?>"><i class="fa fa-print"></i> <?= $_quick_print ?></a>
  <!-- Vouchers -->
  <a href="./?hotspot=users-by-profile&session=<?= $session; ?>" class="menu <?= $susersbp; ?>"><i class="fa fa-ticket"></i> <?= $_vouchers ?></a>
  
  <!-- Log -->
  <div class="dropdown-btn <?= $log; ?>"><i class="fa fa-align-justify"></i> <?= $_log ?>
    <i class="fa fa-caret-down"></i>
  </div>
  <div class="dropdown-container <?= $lmenu; ?>">
    <a href="./?hotspot=log&session=<?= $session; ?>" class="<?= $slog; ?>"><i class="fa fa-wifi"></i> <?= $_hotspot_log ?></a>
    <a href="./?report=userlog&idbl=<?= strtolower(date("M")) . date("Y"); ?>&session=<?= $session; ?>" class="<?= $sulog; ?>"><i class="fa fa-users"></i> <?= $_user_log ?></a>
  </div>

  <!-- System -->
  <div class="dropdown-btn <?= $sysmenu; ?>"><i class="fa fa-server"></i> <?= $_system ?>
    <i class="fa fa-caret-down"></i>
  </div>
  <div class="dropdown-container <?= $schmenu; ?>">
    <a href="./?system=exam-mode&session=<?= $session; ?>" class="<?= $sexammode; ?>"><i class="fa" style="display:inline-flex; align-items:center; justify-content:center; vertical-align:middle;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1.5" y="19.5" width="21" height="2.2" rx="1.1" fill="currentColor" stroke="none"/><path d="M7.5 9.5H5A1.5 1.5 0 0 0 3.5 11v8.5h17V13"/><rect x="10.2" y="1.5" width="3.6" height="1.8" rx="0.5" fill="currentColor" stroke="none"/><line x1="12" y1="3.3" x2="12" y2="4.5"/><circle cx="12" cy="8.5" r="4"/><polyline points="12 6.7 12 8.5 13.4 9.3"/><polyline points="6.2 13.2 7.2 14.2 9 12.4"/><line x1="10.8" y1="13.4" x2="13" y2="13.4"/><line x1="6.5" y1="16.2" x2="8.3" y2="18"/><line x1="8.3" y1="16.2" x2="6.5" y2="18"/><line x1="10.8" y1="17.1" x2="17" y2="17.1"/><path d="M19.8 7.8a1.2 1.2 0 0 1 1.7 1.7l-5.2 5.2-2.2.5.5-2.2 5.2-5.2z" fill="currentColor"/></svg></i> Mode Ujian / ANBK</a>
    <a href="./?system=netwatch&session=<?= $session; ?>" class="<?= $snetwatch; ?>"><i class="fa fa-wifi"></i> AP Monitor</a>
    <a href="./?system=scheduler&session=<?= $session; ?>" class="<?= $ssch; ?>"><i class="fa fa-clock-o"></i> <?= $_system_scheduler ?></a>
    <a href="./admin.php?id=reboot&session=<?= $session; ?>"><i class="fa fa-refresh"></i> <?= $_system_reboot ?></a>            
    <a href="./admin.php?id=shutdown&session=<?= $session; ?>"><i class="fa fa-power-off"></i> <?= $_system_off ?></a> 
  </div>

  <!-- DHCP Leases -->
  <a href="./?hotspot=dhcp-leases&session=<?= $session; ?>" class="menu <?= $slease; ?>"><i class="fa fa-sitemap"></i> <?= $_dhcp_leases ?></a>
  <!-- Blokir Situs -->
  <a href="./?hotspot=siteblock&session=<?= $session; ?>" class="menu <?= $ssiteblock; ?>"><i class="fa" style="display:inline-flex; align-items:center; justify-content:center; vertical-align:-0.15em;"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2.5" y="3.5" width="19" height="17" rx="2.5" stroke="currentColor" stroke-width="1.8"/><line x1="2.5" y1="8" x2="21.5" y2="8" stroke="currentColor" stroke-width="1.7"/><line x1="5" y1="5.75" x2="14" y2="5.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="17" cy="5.75" r="1" fill="currentColor"/><circle cx="19.5" cy="5.75" r="1" fill="currentColor"/><circle cx="12" cy="14.25" r="4.3" stroke="currentColor" stroke-width="1.7"/><line x1="10.3" y1="12.55" x2="13.7" y2="15.95" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><line x1="13.7" y1="12.55" x2="10.3" y2="15.95" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></i> Blokir Situs</a>
  <!-- Traffic Monitor -->
  <a href="./?interface=traffic-monitor&session=<?= $session; ?>" class="menu <?= $strafficmonitor; ?>"><i class="fa fa-area-chart"></i> <?= $_traffic_monitor ?></a>
  <!-- Manajemen Bandwidth / QoS -->
  <a href="./?system=qos&session=<?= $session; ?>" class="menu <?= $sqos; ?>"><i class="fa" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;vertical-align:-0.2em;line-height:1;"><svg viewBox="0 0 32 32" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.94 10.72A9 9 0 0 1 13.25 7.03M14.21 6.22A9 9 0 0 1 18.72 4.53M19.97 4.51A9 9 0 0 1 24.53 6.04M25.52 6.81A9 9 0 0 1 28.15 11.02" stroke="currentColor" stroke-width="3.5" stroke-linecap="butt"/><circle cx="19.5" cy="13.5" r="1.35" fill="currentColor"/><line x1="19.5" y1="13.5" x2="24.2" y2="10.4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M17.67 14.95A8.3 8.3 0 1 0 18.96 24.14" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/><path d="M16.35 21.75L19.0 24.3L21.35 21.75" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9.2" cy="23.0" r="1.3" fill="currentColor"/><path d="M9.6 19.7A3.3 3.3 0 0 1 12.5 22.6M9.9 17.4A5.6 5.6 0 0 1 14.8 22.3M10.2 15.1A7.9 7.9 0 0 1 17.1 22.0" stroke="currentColor" stroke-width="1.55" stroke-linecap="round"/></svg></i> Bandwidth &amp; QoS</a>
  <!-- Terminal -->
  <a href="./?system=terminal&session=<?= $session; ?>" class="menu <?= $sterminal; ?>"><i class="fa fa-terminal"></i> Terminal</a> 
  <!-- Report -->
  <a href="./?report=selling&idbl=<?= strtolower(date("M")) . date("Y"); ?>&session=<?= $session; ?>" class="menu <?= $sselling; ?>"><i class="fa fa-money"></i> <?= $_report ?></a>
  
  <!-- Settings -->
  <div class="dropdown-btn <?= $ssett; ?>"><i class="fa fa-gear"></i> <?= $_settings ?> 
    <i class="fa fa-caret-down"></i>
  </div>
  <div class="dropdown-container <?= $settmenu; ?>">
    <a href="./admin.php?id=settings&session=<?= $session; ?>" class="menu"><i class="fa fa-sliders"></i> <?= $_session_settings ?></a>
    <a href="./admin.php?id=sessions" class="menu"><i class="fa fa-server"></i> <?= $_admin_settings ?></a>
    <a href="./?hotspot=uplogo&session=<?= $session; ?>" class="menu <?= $uplogo; ?>"><i class="fa fa-upload"></i> <?= $_upload_logo ?></a>
    <a href="./?hotspot=template-editor&template=default&session=<?= $session; ?>" class="menu <?= $teditor; ?>"><i class="fa fa-edit"></i> <?= $_template_editor ?></a>          
  </div>
  
  <!-- About -->
  <a href="./?hotspot=about&session=<?= $session; ?>" class="menu menu-about-bottom <?= $sabout; ?>"><i class="fa fa-info-circle"></i> <?= $_about ?></a>
</div>

<script>
$(document).ready(function(){$(".connect").change(function(){
    notify("<?= $_connecting ?>");
    connect(this.value)
  });
  $(".stheme").change(function(){
    notify("<?= $_loading_theme ?>");
    stheme(this.value)
  });
});
</script>
<div id="notify"><div class="message"></div></div>
<div id="temp"></div>
<?php 
include('./include/info.php');
} ?>

<div id="main">  
<div id="loading" class="lds-dual-ring"></div>
<?php if($hotspot == 'template-editor' ||$id == 'editor'){
  echo '<div class="main-container">';
}else{
  echo '<div class="main-container" style="display:none">';
}
?>