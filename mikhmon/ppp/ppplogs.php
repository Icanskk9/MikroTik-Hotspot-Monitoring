<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
}
?>

<style>
  #mhm-ppplogs {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Floating Toast Alert */
  #mhm-ppplogs .lx-toast {
    position: fixed;
    top: 66px;
    right: 22px;
    z-index: 10050;
    min-width: 280px;
    max-width: 390px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-left: 4px solid #16a34a;
    border-radius: 10px;
    padding: 12px 14px;
    box-shadow: 0 14px 32px -8px rgba(9, 9, 11, 0.18);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    animation: lxToastSlide 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    transition: opacity 0.22s ease, transform 0.22s ease;
  }
  #mhm-ppplogs .lx-toast.is-error {
    border-left-color: #dc2626;
  }
  #mhm-ppplogs .lx-toast-icon {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    background: #f0fdf4;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
  }
  #mhm-ppplogs .lx-toast.is-error .lx-toast-icon {
    background: #fef2f2;
    color: #dc2626;
  }
  #mhm-ppplogs .lx-toast-text {
    font-size: 12.5px;
    font-weight: 500;
    color: #18181b;
    line-height: 1.4;
    flex: 1;
  }
  #mhm-ppplogs .lx-toast-close {
    border: none;
    background: transparent;
    color: #a1a1aa;
    font-size: 15px;
    cursor: pointer;
    padding: 2px 4px;
    line-height: 1;
  }
  #mhm-ppplogs .lx-toast-close:hover {
    color: #18181b;
  }
  @keyframes lxToastSlide {
    from { opacity: 0; transform: translateX(16px); }
    to   { opacity: 1; transform: translateX(0); }
  }

  /* Custom Confirm Modal */
  #mhm-ppplogs .lx-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9, 9, 11, 0.45);
    backdrop-filter: blur(2px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  #mhm-ppplogs .lx-modal-backdrop.is-open {
    display: flex;
  }
  #mhm-ppplogs .lx-modal-card {
    width: 100%;
    max-width: 420px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(9, 9, 11, 0.22);
    overflow: hidden;
    animation: lxModalIn 0.16s ease-out;
  }
  #mhm-ppplogs .lx-confirm-body {
    padding: 22px 24px 18px 24px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
  }
  #mhm-ppplogs .lx-confirm-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
  }
  #mhm-ppplogs .lx-confirm-title {
    margin: 0 0 6px 0;
    font-size: 15px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.015em;
  }
  #mhm-ppplogs .lx-confirm-desc {
    margin: 0;
    font-size: 12.5px;
    color: #52525b;
    line-height: 1.55;
  }
  #mhm-ppplogs .lx-confirm-tag {
    display: inline-block;
    margin-top: 9px;
    padding: 3px 9px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    border-radius: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 600;
    color: #18181b;
  }
  #mhm-ppplogs .lx-confirm-foot {
    padding: 13px 22px;
    background: #fafafa;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
  }
  @keyframes lxModalIn {
    from { opacity: 0; transform: translateY(6px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }

  /* Top Bar */
  #mhm-ppplogs .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-ppplogs .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-ppplogs .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-ppplogs .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #52525b;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 2px 9px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-ppplogs .lx-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  /* Buttons */
  #mhm-ppplogs .lx-btn {
    height: 34px;
    padding: 0 13px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: #18181b !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    white-space: nowrap;
  }
  #mhm-ppplogs .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
  }
  #mhm-ppplogs .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-ppplogs .lx-btn.is-primary:hover {
    background: #27272a;
  }
  #mhm-ppplogs .lx-btn.is-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-ppplogs .lx-btn.is-danger:hover {
    background: #fee2e2;
    border-color: #fca5a5;
  }
  #mhm-ppplogs .lx-btn.is-danger-solid {
    background: #dc2626;
    border-color: #dc2626;
    color: #ffffff !important;
  }
  #mhm-ppplogs .lx-btn.is-danger-solid:hover {
    background: #b91c1c;
    border-color: #b91c1c;
  }
  #mhm-ppplogs .lx-btn.is-live {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #15803d !important;
  }
  #mhm-ppplogs .lx-live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #a1a1aa;
    display: inline-block;
  }
  #mhm-ppplogs .lx-btn.is-live .lx-live-dot {
    background: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
    animation: lxPulse 1.5s infinite;
  }

  /* KPI Summary Strip */
  #mhm-ppplogs .lx-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    padding: 18px 24px 0 24px;
    background: #fafafa;
  }
  @media (max-width: 900px) {
    #mhm-ppplogs .lx-stats-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  @media (max-width: 480px) {
    #mhm-ppplogs .lx-stats-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
  #mhm-ppplogs .lx-stat-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 13px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-ppplogs .lx-stat-label {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    margin-bottom: 4px;
  }
  #mhm-ppplogs .lx-stat-val {
    font-size: 20px;
    font-weight: 700;
    color: #09090b;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
  }
  #mhm-ppplogs .lx-stat-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }

  /* Main Body Area */
  #mhm-ppplogs .lx-body {
    padding: 16px 24px 22px 24px;
    background: #fafafa;
  }

  /* Flapping Detector Box */
  #mhm-ppplogs .lx-flap-box {
    margin-bottom: 16px;
    background: #ffffff;
    border: 1px solid #fde68a;
    border-left: 4px solid #f59e0b;
    border-radius: 10px;
    padding: 16px 18px;
    box-shadow: 0 1px 2px rgba(245, 158, 11, 0.06);
  }
  #mhm-ppplogs .lx-flap-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
  }
  #mhm-ppplogs .lx-flap-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #92400e;
  }
  #mhm-ppplogs .lx-flap-desc {
    font-size: 12px;
    color: #78350f;
    margin: 0 0 12px 0;
    line-height: 1.55;
  }
  #mhm-ppplogs .lx-flap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 10px;
  }
  #mhm-ppplogs .lx-flap-item {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 10px 12px;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
  #mhm-ppplogs .lx-flap-item:hover {
    background: #fef3c7;
    border-color: #f59e0b;
    transform: translateY(-1px);
  }
  #mhm-ppplogs .lx-flap-item.is-selected {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff;
  }
  #mhm-ppplogs .lx-flap-item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  #mhm-ppplogs .lx-flap-user {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    font-weight: 600;
    color: #18181b;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  #mhm-ppplogs .lx-flap-item.is-selected .lx-flap-user {
    color: #ffffff;
  }
  #mhm-ppplogs .lx-flap-badge {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 5px;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
  }
  #mhm-ppplogs .lx-flap-hint {
    font-size: 11px;
    color: #92400e;
    line-height: 1.4;
  }
  #mhm-ppplogs .lx-flap-item.is-selected .lx-flap-hint {
    color: #d4d4d8;
  }

  /* Table Container & Toolbar */
  #mhm-ppplogs .lx-table-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    overflow: hidden;
  }
  #mhm-ppplogs .lx-toolbar {
    padding: 12px 16px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #ffffff;
  }
  #mhm-ppplogs .lx-tabs {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f4f4f5;
    padding: 3px;
    border-radius: 8px;
    flex-wrap: wrap;
  }
  #mhm-ppplogs .lx-tab {
    border: none;
    background: transparent;
    padding: 5px 11px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #52525b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
  }
  #mhm-ppplogs .lx-tab:hover {
    color: #09090b;
  }
  #mhm-ppplogs .lx-tab.is-active {
    background: #ffffff;
    color: #09090b;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.06);
  }
  #mhm-ppplogs .lx-tab-num {
    font-size: 10.5px;
    padding: 0 5px;
    border-radius: 4px;
    background: #e4e4e7;
    color: #3f3f46;
    font-variant-numeric: tabular-nums;
  }
  #mhm-ppplogs .lx-tab.is-active .lx-tab-num {
    background: #18181b;
    color: #ffffff;
  }

  /* Search Input */
  #mhm-ppplogs .lx-toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  #mhm-ppplogs .lx-search {
    position: relative;
    width: 250px;
    max-width: 100%;
  }
  #mhm-ppplogs .lx-search i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #a1a1aa;
    pointer-events: none;
  }
  #mhm-ppplogs #logSearchInput {
    width: 100% !important;
    height: 33px !important;
    padding: 0 12px 0 32px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    color: #09090b !important;
    box-shadow: none !important;
    margin: 0 !important;
  }
  #mhm-ppplogs #logSearchInput:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }

  /* Table */
  #mhm-ppplogs .lx-table-wrap {
    width: 100%;
    overflow-x: auto;
    max-height: 55vh;
  }
  #mhm-ppplogs .lx-table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    white-space: nowrap;
    text-align: left;
    background: #ffffff !important;
  }
  #mhm-ppplogs .lx-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #fafafa !important;
    color: #71717a !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 11px 16px !important;
    border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-ppplogs .lx-table tbody td {
    padding: 10px 16px !important;
    border-bottom: 1px solid #f4f4f5 !important;
    font-size: 12.5px !important;
    color: #27272a !important;
    vertical-align: middle;
  }
  #mhm-ppplogs .lx-table tbody tr:hover {
    background-color: #fafafa !important;
  }

  /* Pills & Badges */
  #mhm-ppplogs .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    color: #52525b;
    font-variant-numeric: tabular-nums;
  }
  #mhm-ppplogs .lx-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
  }
  #mhm-ppplogs .lx-status-badge.is-connect {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }
  #mhm-ppplogs .lx-status-badge.is-disconnect {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
  }
  #mhm-ppplogs .lx-status-badge.is-error {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
  }
  #mhm-ppplogs .lx-status-badge.is-info {
    background: #f4f4f5;
    color: #52525b;
    border: 1px solid #e4e4e7;
  }
  #mhm-ppplogs .lx-user-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 600;
    color: #18181b;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 2px 8px;
    border-radius: 6px;
    cursor: pointer;
    display: inline-block;
  }
  #mhm-ppplogs .lx-user-pill:hover {
    background: #18181b;
    color: #ffffff;
    border-color: #18181b;
  }
  #mhm-ppplogs .lx-pill-topic {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 500;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    color: #71717a;
  }
  #mhm-ppplogs .lx-log-term { color: #dc2626; font-weight: 500; }
  #mhm-ppplogs .lx-log-login { color: #15803d; font-weight: 500; }
  #mhm-ppplogs .lx-log-err { color: #b45309; font-weight: 500; }

  /* Footer */
  #mhm-ppplogs .lx-footer {
    padding: 12px 24px;
    background: #ffffff;
    border-top: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 11.5px;
    color: #71717a;
  }

  @keyframes lxPulse {
    0% { opacity: 1; }
    50% { opacity: 0.4; }
    100% { opacity: 1; }
  }
</style>

<div id="mhm-ppplogs">
  <!-- Floating Toast Container -->
  <div id="lxLogsToastContainer"></div>

  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">PPP Connection Logs &amp; Flapping Detector</h3>
      <span class="lx-count" id="logCountBadge">Memuat log...</span>
    </div>

    <div class="lx-actions">
      <button type="button" id="btnAutoRefresh" onclick="toggleAutoRefresh();" class="lx-btn" title="Refresh otomatis setiap 10 detik">
        <span class="lx-live-dot"></span>
        <span id="autoRefreshLabel">Auto: Off</span>
      </button>

      <button type="button" id="btnClearLogs" onclick="clearRouterLogs();" class="lx-btn is-danger" title="Kosongkan buffer log memori MikroTik dan reset Flapping Detector">
        <i class="fa fa-trash-o"></i>
        <span>Bersihkan Log</span>
      </button>

      <button type="button" onclick="exportLogsCsv();" class="lx-btn" title="Unduh log dalam format CSV">
        <i class="fa fa-download" style="color:#71717a;"></i>
        <span>Export CSV</span>
      </button>

      <button type="button" onclick="loadPppLogs(false);" class="lx-btn is-primary" title="Muat Ulang Log">
        <i class="fa fa-refresh" id="refreshIcon" style="font-size:11.5px; color:#d4d4d8;"></i>
        <span>Refresh Log</span>
      </button>
    </div>
  </div>

  <!-- 4 Summary Metric Cards -->
  <div class="lx-stats-grid">
    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Total Aktivitas PPPoE</div>
        <div class="lx-stat-val" id="statTotal">0</div>
      </div>
      <div class="lx-stat-icon" style="background:#f4f4f5; color:#18181b;">
        <i class="fa fa-list-ul"></i>
      </div>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Koneksi Masuk (Login)</div>
        <div class="lx-stat-val" id="statConnect" style="color:#15803d;">0</div>
      </div>
      <div class="lx-stat-icon" style="background:#f0fdf4; color:#16a34a;">
        <i class="fa fa-plug"></i>
      </div>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Terputus (Disconnect)</div>
        <div class="lx-stat-val" id="statDisconnect" style="color:#dc2626;">0</div>
      </div>
      <div class="lx-stat-icon" style="background:#fef2f2; color:#dc2626;">
        <i class="fa fa-chain-broken"></i>
      </div>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Gagal Auth / Error</div>
        <div class="lx-stat-val" id="statError" style="color:#b45309;">0</div>
      </div>
      <div class="lx-stat-icon" style="background:#fffbeb; color:#d97706;">
        <i class="fa fa-exclamation-circle"></i>
      </div>
    </div>
  </div>

  <div class="lx-body">
    <!-- Flapping Detector Banner -->
    <div id="flappingCard" class="lx-flap-box" style="display:none;">
      <div class="lx-flap-header">
        <div class="lx-flap-title">
          <i class="fa fa-exclamation-triangle" style="color:#d97706; font-size:15px;"></i>
          <span>Deteksi Dini Jalur FO / ONT Bermasalah (Akun Sering Flapping)</span>
        </div>
        <span style="font-size:11px; color:#92400e; font-weight:500;">Klik kartu akun di bawah untuk memfilter log spesifik</span>
      </div>
      <p class="lx-flap-desc">
        Akun berikut tercatat mengalami putus-nyambung berulang kali. Periksa redaman optik RX Power pada ONT (ideal -15 dBm s/d -24 dBm), kebersihan konektor patchcord SC/APC, serta stabilitas adaptor daya ONT.
      </p>
      <div id="flappingTagsContainer" class="lx-flap-grid"></div>
    </div>

    <!-- Table Card -->
    <div class="lx-table-card">
      <div class="lx-toolbar">
        <div class="lx-tabs">
          <button type="button" class="lx-tab is-active" data-filter="all" onclick="setStatusFilter('all', this);">
            <span>Semua</span>
            <span class="lx-tab-num" id="tabCountAll">0</span>
          </button>
          <button type="button" class="lx-tab" data-filter="disconnect" onclick="setStatusFilter('disconnect', this);">
            <span>Terputus</span>
            <span class="lx-tab-num" id="tabCountDisconnect">0</span>
          </button>
          <button type="button" class="lx-tab" data-filter="connect" onclick="setStatusFilter('connect', this);">
            <span>Terhubung</span>
            <span class="lx-tab-num" id="tabCountConnect">0</span>
          </button>
          <button type="button" class="lx-tab" data-filter="error" onclick="setStatusFilter('error', this);">
            <span>Gagal Auth</span>
            <span class="lx-tab-num" id="tabCountError">0</span>
          </button>
        </div>

        <div class="lx-toolbar-right">
          <button type="button" id="btnResetUserFilter" onclick="clearUserFilter();" class="lx-btn" style="display:none; height:33px; background:#18181b; color:#ffffff !important; border-color:#18181b;">
            <i class="fa fa-times" style="color:#ffffff;"></i>
            <span id="activeUserLabel">User: -</span>
          </button>

          <div class="lx-search">
            <i class="fa fa-search"></i>
            <input id="logSearchInput" type="text" class="form-control" placeholder="Cari user, IP, atau pesan log..." onkeyup="applyAllFilters();">
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div id="logLoadingState" style="padding:44px 20px; text-align:center; color:#71717a; font-size:13px;">
        <i class="fa fa-circle-o-notch fa-spin" style="font-size:22px; margin-bottom:8px; display:block; color:#18181b;"></i>
        Menarik log aktivitas PPPoE dari MikroTik...
      </div>

      <!-- Table Logs -->
      <div class="lx-table-wrap" id="logTableWrap" style="display:none;">
        <table class="table lx-table" id="logDataTable">
          <thead>
            <tr>
              <th style="width:140px;">Waktu</th>
              <th style="width:125px;">Status</th>
              <th style="width:165px;">Akun / User</th>
              <th style="width:155px;">Topik</th>
              <th>Pesan Log RouterOS</th>
            </tr>
          </thead>
          <tbody id="logTableBody"></tbody>
        </table>

        <div id="emptyFilterState" style="display:none; padding:36px 20px; text-align:center; color:#71717a; font-size:12.5px;">
          Tidak ada catatan log yang sesuai dengan filter atau kata kunci pencarian saat ini.
        </div>
      </div>
    </div>
  </div>

  <div class="lx-footer">
    <div>Menampilkan hingga 250 aktivitas log PPPoE terbaru &bull; Klik nama akun untuk memfilter riwayat klien</div>
    <div id="lastUpdatedLabel">RouterOS v6 &amp; v7 Compatible</div>
  </div>

  <!-- Modal Konfirmasi Bersihkan Log (Pengganti Browser Confirm) -->
  <div class="lx-modal-backdrop" id="modalConfirmClearLogs" onclick="if(event.target===this) closeClearLogsModal();">
    <div class="lx-modal-card" role="dialog" aria-modal="true">
      <div class="lx-confirm-body">
        <div class="lx-confirm-icon">
          <i class="fa fa-trash-o"></i>
        </div>
        <div>
          <h4 class="lx-confirm-title">Bersihkan Buffer Log Router?</h4>
          <p class="lx-confirm-desc">
            Seluruh riwayat log memori di MikroTik akan dikosongkan dan hitungan peringatan <strong>Flapping Detector</strong> akan di-reset dari awal.
          </p>
          <span class="lx-confirm-tag">/system logging memory</span>
        </div>
      </div>
      <div class="lx-confirm-foot">
        <button type="button" class="lx-btn" id="btnCancelClearModal" onclick="closeClearLogsModal();">Batal</button>
        <button type="button" class="lx-btn is-danger-solid" id="btnConfirmClearModal" onclick="executeClearRouterLogs();">Ya, Bersihkan Log</button>
      </div>
    </div>
  </div>
</div>

<script>
  var pppSession = <?= json_encode($session); ?>;
  var allLogsData = [];
  var activeStatusFilter = 'all';
  var activeUserFilter = '';
  var autoRefreshTimer = null;

  function showLogsToast(message, isError) {
    var container = document.getElementById('lxLogsToastContainer');
    if (!container) return;

    var toast = document.createElement('div');
    toast.className = 'lx-toast' + (isError ? ' is-error' : '');
    toast.innerHTML =
      '<div class="lx-toast-icon"><i class="fa ' + (isError ? 'fa-exclamation' : 'fa-check') + '"></i></div>' +
      '<div class="lx-toast-text">' + escapeHtml(message) + '</div>' +
      '<button type="button" class="lx-toast-close">&times;</button>';

    var closeBtn = toast.querySelector('.lx-toast-close');
    var removeToast = function() {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(12px)';
      setTimeout(function() {
        if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
      }, 220);
    };

    closeBtn.onclick = removeToast;
    container.innerHTML = '';
    container.appendChild(toast);
    setTimeout(removeToast, 4200);
  }

  function clearRouterLogs() {
    var btnConfirm = document.getElementById('btnConfirmClearModal');
    var btnCancel  = document.getElementById('btnCancelClearModal');
    if (btnConfirm) {
      btnConfirm.disabled = false;
      btnConfirm.textContent = 'Ya, Bersihkan Log';
    }
    if (btnCancel) btnCancel.disabled = false;
    document.getElementById('modalConfirmClearLogs').classList.add('is-open');
  }

  function closeClearLogsModal() {
    document.getElementById('modalConfirmClearLogs').classList.remove('is-open');
  }

  function executeClearRouterLogs() {
    var btnClear   = document.getElementById('btnClearLogs');
    var btnConfirm = document.getElementById('btnConfirmClearModal');
    var btnCancel  = document.getElementById('btnCancelClearModal');

    if (btnClear) btnClear.disabled = true;
    if (btnCancel) btnCancel.disabled = true;
    if (btnConfirm) {
      btnConfirm.disabled = true;
      btnConfirm.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i> Membersihkan...';
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ppp/ppplogs_ajax.php?action=clear&session=' + encodeURIComponent(pppSession) + '&_t=' + Date.now(), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        if (btnClear) btnClear.disabled = false;
        if (btnCancel) btnCancel.disabled = false;
        if (btnConfirm) {
          btnConfirm.disabled = false;
          btnConfirm.textContent = 'Ya, Bersihkan Log';
        }
        closeClearLogsModal();

        if (xhr.status === 200) {
          clearUserFilter();
          loadPppLogs(false);
          showLogsToast('Buffer log memori MikroTik berhasil dibersihkan.', false);
        } else {
          showLogsToast('Gagal membersihkan log MikroTik (HTTP ' + xhr.status + ').', true);
        }
      }
    };
    xhr.send();
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeClearLogsModal();
    }
  });

  function loadPppLogs(silent) {
    var refreshIcon = document.getElementById('refreshIcon');
    if (refreshIcon) refreshIcon.classList.add('fa-spin');

    if (!silent) {
      document.getElementById('logLoadingState').style.display = '';
      document.getElementById('logTableWrap').style.display = 'none';
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ppp/ppplogs_ajax.php?session=' + encodeURIComponent(pppSession) + '&_t=' + Date.now(), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
        document.getElementById('logLoadingState').style.display = 'none';

        if (xhr.status === 200) {
          try {
            var res = JSON.parse(xhr.responseText);
            if (res && res.ok) {
              allLogsData = res.logs || [];
              var stats = res.stats || { total: allLogsData.length, connect: 0, disconnect: 0, error: 0 };

              document.getElementById('logCountBadge').textContent = stats.total + ' log PPPoE';
              document.getElementById('statTotal').textContent = stats.total;
              document.getElementById('statConnect').textContent = stats.connect;
              document.getElementById('statDisconnect').textContent = stats.disconnect;
              document.getElementById('statError').textContent = stats.error;

              document.getElementById('tabCountAll').textContent = stats.total;
              document.getElementById('tabCountDisconnect').textContent = stats.disconnect;
              document.getElementById('tabCountConnect').textContent = stats.connect;
              document.getElementById('tabCountError').textContent = stats.error;

              // Render Flapping Detector
              var flaps = res.flapping || [];
              if (flaps.length > 0) {
                var flapHtml = '';
                for (var f = 0; f < flaps.length; f++) {
                  var item = flaps[f];
                  var isSelected = (activeUserFilter === item.username) ? ' is-selected' : '';
                  flapHtml += '<div class="lx-flap-item' + isSelected + '" onclick="filterByUser(\'' + escapeAttr(item.username) + '\');">' +
                    '<div class="lx-flap-item-top">' +
                      '<span class="lx-flap-user"><i class="fa fa-router"></i> ' + escapeHtml(item.username) + '</span>' +
                      '<span class="lx-flap-badge">' + item.count + 'x putus</span>' +
                    '</div>' +
                    '<div class="lx-flap-hint">' + escapeHtml(item.hint) + ' &bull; Terakhir: ' + escapeHtml(item.last_time) + '</div>' +
                  '</div>';
                }
                document.getElementById('flappingTagsContainer').innerHTML = flapHtml;
                document.getElementById('flappingCard').style.display = '';
              } else {
                document.getElementById('flappingCard').style.display = 'none';
              }

              renderLogRows();
              document.getElementById('logTableWrap').style.display = '';

              var now = new Date();
              var hh = ('0' + now.getHours()).slice(-2);
              var mm = ('0' + now.getMinutes()).slice(-2);
              var ss = ('0' + now.getSeconds()).slice(-2);
              document.getElementById('lastUpdatedLabel').textContent = 'Diperbarui pukul ' + hh + ':' + mm + ':' + ss;
            } else {
              document.getElementById('logLoadingState').innerHTML = '<span style="color:#dc2626;">Gagal memuat log: ' + escapeHtml((res && res.error) ? res.error : 'Unknown') + '</span>';
              document.getElementById('logLoadingState').style.display = '';
            }
          } catch (e) {
            document.getElementById('logLoadingState').innerHTML = '<span style="color:#dc2626;">Error memproses respons JSON dari server.</span>';
            document.getElementById('logLoadingState').style.display = '';
          }
        } else {
          document.getElementById('logLoadingState').innerHTML = '<span style="color:#dc2626;">Gagal menghubungi server (HTTP ' + xhr.status + ').</span>';
          document.getElementById('logLoadingState').style.display = '';
        }
      }
    };
    xhr.send();
  }

  function renderLogRows() {
    var tbodyHtml = '';
    for (var i = 0; i < allLogsData.length; i++) {
      var lg = allLogsData[i];
      var type = lg.type || 'info';
      var uname = lg.username || '';

      var badgeHtml = '<span class="lx-status-badge is-info">Info</span>';
      var msgClass = '';
      if (type === 'disconnect') {
        badgeHtml = '<span class="lx-status-badge is-disconnect"><i class="fa fa-arrow-down" style="font-size:9px;"></i> Terputus</span>';
        msgClass = 'lx-log-term';
      } else if (type === 'connect') {
        badgeHtml = '<span class="lx-status-badge is-connect"><i class="fa fa-arrow-up" style="font-size:9px;"></i> Terhubung</span>';
        msgClass = 'lx-log-login';
      } else if (type === 'error') {
        badgeHtml = '<span class="lx-status-badge is-error"><i class="fa fa-warning" style="font-size:9px;"></i> Gagal Auth</span>';
        msgClass = 'lx-log-err';
      }

      var userHtml = uname !== ''
        ? '<span class="lx-user-pill" onclick="filterByUser(\'' + escapeAttr(uname) + '\');" title="Filter log akun ini">' + escapeHtml(uname) + '</span>'
        : '<span style="color:#a1a1aa; font-size:11.5px;">-</span>';

      tbodyHtml += '<tr data-type="' + escapeAttr(type) + '" data-user="' + escapeAttr(uname.toLowerCase()) + '">' +
        '<td><span class="lx-mono">' + escapeHtml(lg.time) + '</span></td>' +
        '<td>' + badgeHtml + '</td>' +
        '<td>' + userHtml + '</td>' +
        '<td><span class="lx-pill-topic">' + escapeHtml(lg.topics) + '</span></td>' +
        '<td class="' + msgClass + '">' + escapeHtml(lg.message) + '</td>' +
      '</tr>';
    }
    document.getElementById('logTableBody').innerHTML = tbodyHtml;
    applyAllFilters();
  }

  function setStatusFilter(filterType, btnEl) {
    activeStatusFilter = filterType;
    var tabs = document.querySelectorAll('#mhm-ppplogs .lx-tab');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].classList.remove('is-active');
    }
    if (btnEl) btnEl.classList.add('is-active');
    applyAllFilters();
  }

  function filterByUser(username) {
    if (activeUserFilter === username) {
      clearUserFilter();
      return;
    }
    activeUserFilter = username;
    var btnReset = document.getElementById('btnResetUserFilter');
    document.getElementById('activeUserLabel').textContent = 'Akun: ' + username;
    btnReset.style.display = '';
    highlightFlapSelection();
    applyAllFilters();
  }

  function clearUserFilter() {
    activeUserFilter = '';
    document.getElementById('btnResetUserFilter').style.display = 'none';
    highlightFlapSelection();
    applyAllFilters();
  }

  function highlightFlapSelection() {
    var items = document.querySelectorAll('#flappingTagsContainer .lx-flap-item');
    for (var i = 0; i < items.length; i++) {
      var userEl = items[i].querySelector('.lx-flap-user');
      var txt = userEl ? userEl.textContent.trim() : '';
      if (activeUserFilter !== '' && txt === activeUserFilter) {
        items[i].classList.add('is-selected');
      } else {
        items[i].classList.remove('is-selected');
      }
    }
  }

  function applyAllFilters() {
    var query = (document.getElementById('logSearchInput').value || '').toLowerCase().trim();
    var targetUser = activeUserFilter.toLowerCase();
    var rows = document.querySelectorAll('#logTableBody tr');
    var visibleCount = 0;

    for (var i = 0; i < rows.length; i++) {
      var rType = rows[i].getAttribute('data-type');
      var rUser = rows[i].getAttribute('data-user');
      var rText = rows[i].textContent.toLowerCase();

      var matchType = (activeStatusFilter === 'all' || rType === activeStatusFilter);
      var matchUser = (targetUser === '' || rUser === targetUser || rText.indexOf(targetUser) !== -1);
      var matchQuery = (query === '' || rText.indexOf(query) !== -1);

      if (matchType && matchUser && matchQuery) {
        rows[i].style.display = '';
        visibleCount++;
      } else {
        rows[i].style.display = 'none';
      }
    }

    var emptyEl = document.getElementById('emptyFilterState');
    if (emptyEl) {
      emptyEl.style.display = (visibleCount === 0) ? '' : 'none';
    }
  }

  function toggleAutoRefresh() {
    var btn = document.getElementById('btnAutoRefresh');
    var lbl = document.getElementById('autoRefreshLabel');
    if (autoRefreshTimer) {
      clearInterval(autoRefreshTimer);
      autoRefreshTimer = null;
      btn.classList.remove('is-live');
      lbl.textContent = 'Auto: Off';
    } else {
      btn.classList.add('is-live');
      lbl.textContent = 'Auto: 10d';
      autoRefreshTimer = setInterval(function() {
        if (!document.getElementById('mhm-ppplogs')) {
          clearInterval(autoRefreshTimer);
          autoRefreshTimer = null;
          return;
        }
        loadPppLogs(true);
      }, 10000);
    }
  }

  function exportLogsCsv() {
    if (!allLogsData || allLogsData.length === 0) return;
    var rows = [['Waktu', 'Status', 'Akun', 'Topik', 'Pesan']];
    var query = (document.getElementById('logSearchInput').value || '').toLowerCase().trim();
    var targetUser = activeUserFilter.toLowerCase();

    for (var i = 0; i < allLogsData.length; i++) {
      var lg = allLogsData[i];
      var type = lg.type || 'info';
      var uname = (lg.username || '').toLowerCase();
      var fullText = (lg.time + ' ' + lg.username + ' ' + lg.topics + ' ' + lg.message).toLowerCase();

      if (activeStatusFilter !== 'all' && type !== activeStatusFilter) continue;
      if (targetUser !== '' && uname !== targetUser && fullText.indexOf(targetUser) === -1) continue;
      if (query !== '' && fullText.indexOf(query) === -1) continue;

      rows.push([
        '"' + String(lg.time || '').replace(/"/g, '""') + '"',
        '"' + String(type).toUpperCase() + '"',
        '"' + String(lg.username || '-').replace(/"/g, '""') + '"',
        '"' + String(lg.topics || '').replace(/"/g, '""') + '"',
        '"' + String(lg.message || '').replace(/"/g, '""') + '"'
      ]);
    }

    var csvContent = 'data:text/csv;charset=utf-8,' + rows.map(function(e) { return e.join(','); }).join('\n');
    var encodedUri = encodeURI(csvContent);
    var link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', 'ppp-logs-' + pppSession + '.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function escapeAttr(str) {
    return escapeHtml(str).replace(/'/g, '&#39;');
  }

  // Muat otomatis saat dibuka
  loadPppLogs(false);
</script>