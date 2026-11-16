<style>
  .qrcode {
    height: 120px;
    width: 120px;
    display: block;
    margin: 0 auto;
  }
</style>

<table class="voucher" style="width:180px; max-width:180px; table-layout:fixed; background:#ffffff; border:1.5px solid #18181b; border-radius:8px; border-collapse:separate; border-spacing:0; overflow:hidden; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color:#18181b; box-sizing:border-box;">
  <tbody>
    <!-- Header Logo, Nama Hotspot & Timestamp -->
    <tr>
      <td style="padding:7px 6px 6px; background:#fafafa; border-bottom:1px solid #d4d4d8; text-align:center; word-wrap:break-word;">
        <img src="<?= $logo; ?>" alt="logo" style="height:26px; width:auto; max-width:130px; object-fit:contain; border:0; display:block; margin:0 auto 4px;">
        <div style="font-size:11px; font-weight:700; color:#09090b; letter-spacing:-0.01em; line-height:1.25;">
          <?= $hotspotname; ?>
        </div>
        <div style="margin-top:3px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:8.5px; font-weight:500; color:#52525b;">
          <?= date("Y-m-d h:i:sa") ?>
        </div>
      </td>
    </tr>

    <!-- Body Kode Voucher / User & Password -->
    <tr>
      <td style="padding:7px 6px 5px;">
        <table style="width:100%; table-layout:fixed; border-collapse:collapse; text-align:center;">
          <tbody>
            <?php if ($usermode == "vc") { ?>
              <tr>
                <td style="font-size:8px; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:#52525b; padding-bottom:3px;">
                  Kode Voucher
                </td>
              </tr>
              <tr>
                <td style="padding:5px 4px; background:#fafafa; border:1.5px solid #18181b; border-radius:6px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:14px; font-weight:700; color:#09090b; letter-spacing:0.03em; overflow:hidden; text-overflow:ellipsis;">
                  <?= $username; ?>
                </td>
              </tr>
            <?php } elseif ($usermode == "up") { ?>
              <tr>
                <td style="width:50%; font-size:7.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#52525b; padding-bottom:3px; padding-right:2px;">
                  Username
                </td>
                <td style="width:50%; font-size:7.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#52525b; padding-bottom:3px; padding-left:2px;">
                  Password
                </td>
              </tr>
              <tr>
                <td style="padding-right:2px;">
                  <div style="padding:5px 3px; background:#fafafa; border:1.5px solid #18181b; border-radius:6px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:11.5px; font-weight:700; color:#09090b; overflow:hidden; text-overflow:ellipsis;">
                    <?= $username; ?>
                  </div>
                </td>
                <td style="padding-left:2px;">
                  <div style="padding:5px 3px; background:#fafafa; border:1.5px solid #18181b; border-radius:6px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:11.5px; font-weight:700; color:#09090b; overflow:hidden; text-overflow:ellipsis;">
                    <?= $password; ?>
                  </div>
                </td>
              </tr>
            <?php } ?>

            <!-- QR Code (Jika aktif) -->
            <?php if ($qr == "yes") { ?>
              <tr>
                <td colspan="2" style="padding-top:7px;">
                  <div style="display:inline-block; padding:4px; background:#ffffff; border:1px solid #d4d4d8; border-radius:6px;">
                    <?= $qrcode ?>
                  </div>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </td>
    </tr>

    <!-- Footer Paket, Harga & URL Login -->
    <tr>
      <td style="padding:4px 6px 7px; text-align:center;">
        <div style="padding:4px 4px; background:#f4f4f5; border:1px solid #d4d4d8; border-radius:5px; font-size:10.5px; font-weight:700; color:#09090b; line-height:1.3; word-wrap:break-word;">
          <?= trim("$validity $timelimit $datalimit $price"); ?>
        </div>
        <div style="margin-top:5px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:8px; font-weight:600; color:#3f3f46; line-height:1.25; word-break:break-all;">
          Login: http://<?= $dnsname; ?>
        </div>
      </td>
    </tr>
  </tbody>
</table>