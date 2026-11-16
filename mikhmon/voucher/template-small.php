<table class="voucher" style="width:160px; max-width:160px; table-layout:fixed; background:#ffffff; border:1.5px solid #18181b; border-radius:8px; border-collapse:separate; border-spacing:0; overflow:hidden; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color:#18181b; box-sizing:border-box;">
  <tbody>
    <!-- Header Nama Hotspot & Nomor Urut -->
    <tr>
      <td style="padding:6px 7px; background:#fafafa; border-bottom:1px solid #d4d4d8;">
        <table style="width:100%; table-layout:fixed; border-collapse:collapse;">
          <tbody>
            <tr>
              <td style="text-align:left; font-size:10px; font-weight:700; color:#09090b; letter-spacing:-0.01em; line-height:1.2; word-wrap:break-word;">
                <?= $hotspotname; ?>
              </td>
              <td style="width:36px; text-align:right; vertical-align:top; padding-left:3px;">
                <span id="num" style="display:inline-block; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:9px; font-weight:600; color:#52525b; background:#f4f4f5; border:1px solid #d4d4d8; padding:1px 4px; border-radius:4px;">
                  <?= "[$num]"; ?>
                </span>
              </td>
            </tr>
          </tbody>
        </table>
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
                <td style="padding:5px 4px; background:#fafafa; border:1.5px solid #18181b; border-radius:6px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:13.5px; font-weight:700; color:#09090b; letter-spacing:0.03em; overflow:hidden; text-overflow:ellipsis;">
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
                  <div style="padding:4px 3px; background:#fafafa; border:1.5px solid #18181b; border-radius:6px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:11px; font-weight:700; color:#09090b; overflow:hidden; text-overflow:ellipsis;">
                    <?= $username; ?>
                  </div>
                </td>
                <td style="padding-left:2px;">
                  <div style="padding:4px 3px; background:#fafafa; border:1.5px solid #18181b; border-radius:6px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:11px; font-weight:700; color:#09090b; overflow:hidden; text-overflow:ellipsis;">
                    <?= $password; ?>
                  </div>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </td>
    </tr>

    <!-- Footer Paket, Kuota & Harga -->
    <tr>
      <td style="padding:4px 6px 6px; text-align:center;">
        <div style="padding:4px 4px; background:#f4f4f5; border:1px solid #d4d4d8; border-radius:5px; font-size:9.5px; font-weight:700; color:#09090b; line-height:1.3; word-wrap:break-word;">
          <?= trim("$validity $timelimit $datalimit $price"); ?>
        </div>
      </td>
    </tr>
  </tbody>
</table>