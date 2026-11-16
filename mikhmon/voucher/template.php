<style>
  .voucher-card {
    width: 226px;
    background: #ffffff;
    border: 1.5px solid #18181b;
    border-radius: 8px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: #18181b;
    margin: 3px;
    display: inline-block;
    vertical-align: top;
    box-sizing: border-box;
    overflow: hidden;
  }
  .v-header {
    padding: 8px 10px 7px;
    border-bottom: 1px solid #e4e4e7;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .v-brand {
    display: flex;
    align-items: center;
    gap: 7px;
  }
  .v-brand img {
    height: 24px;
    width: auto;
    object-fit: contain;
  }
  .v-hotspot-name {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    color: #09090b;
    line-height: 1.2;
  }
  .v-num {
    font-size: 10px;
    font-weight: 600;
    color: #71717a;
    font-family: monospace;
  }
  .v-body {
    padding: 9px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .v-cred-area {
    flex: 1;
    min-width: 0;
  }
  .v-label {
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #71717a;
    margin-bottom: 3px;
  }
  .v-code-box {
    background: #f4f4f5;
    border: 1px solid #d4d4d8;
    border-radius: 5px;
    padding: 6px 8px;
    text-align: center;
    font-size: 16px;
    font-weight: 800;
    letter-spacing: 1.2px;
    color: #09090b;
    font-family: "Consolas", "Courier New", monospace;
  }
  .v-up-grid {
    display: flex;
    gap: 6px;
  }
  .v-up-col {
    flex: 1;
    min-width: 0;
  }
  .v-up-box {
    background: #f4f4f5;
    border: 1px solid #d4d4d8;
    border-radius: 5px;
    padding: 5px 4px;
    text-align: center;
    font-size: 12.5px;
    font-weight: 700;
    color: #09090b;
    font-family: "Consolas", "Courier New", monospace;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .qrcode {
    height: 62px;
    width: 62px;
    display: block;
    flex-shrink: 0;
  }
  .v-footer {
    padding: 7px 10px 8px;
    border-top: 1px dashed #d4d4d8;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
  }
  .v-meta {
    text-align: left;
    line-height: 1.3;
  }
  .v-paket {
    font-size: 10.5px;
    font-weight: 700;
    color: #27272a;
  }
  .v-login-url {
    font-size: 9px;
    color: #71717a;
  }
  .v-price {
    font-size: 12.5px;
    font-weight: 800;
    color: #09090b;
    white-space: nowrap;
  }
</style>

<div class="voucher-card">
  <!-- Header -->
  <div class="v-header">
    <div class="v-brand">
      <img src="<?= $logo; ?>" alt="Logo">
      <span class="v-hotspot-name"><?= $hotspotname; ?></span>
    </div>
    <span class="v-num">#<?= $num; ?></span>
  </div>

  <!-- Body: Kode Voucher & QR -->
  <div class="v-body">
    <div class="v-cred-area">
      <?php if($usermode == "vc"){ ?>
        <div class="v-label">Kode Voucher</div>
        <div class="v-code-box"><?= $username; ?></div>
      <?php }elseif($usermode == "up"){ ?>
        <div class="v-up-grid">
          <div class="v-up-col">
            <div class="v-label">Username</div>
            <div class="v-up-box"><?= $username; ?></div>
          </div>
          <div class="v-up-col">
            <div class="v-label">Password</div>
            <div class="v-up-box"><?= $password; ?></div>
          </div>
        </div>
      <?php } ?>
    </div>

    <?php if($qr == "yes"){ ?>
      <div><?= $qrcode ?></div>
    <?php } ?>
  </div>

  <!-- Footer: Paket, URL & Harga -->
  <div class="v-footer">
    <div class="v-meta">
      <div class="v-paket"><?= $validity; ?> <?= $timelimit; ?> <?= $datalimit; ?></div>
      <div class="v-login-url">Login: http://<?= $dnsname; ?></div>
    </div>
    <div class="v-price"><?= $price; ?></div>
  </div>
</div>