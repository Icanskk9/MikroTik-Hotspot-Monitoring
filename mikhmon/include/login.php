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
?>

<div class="login-screen">
  <div class="login-card">
    <!-- Logo Sekolah -->
    <img src="img/logo-sekolah.png" alt="Logo SMK Negeri 1 Siantar" class="login-school-logo">

    <!-- Judul & Subjudul -->
    <h1 class="login-title">MikroTik Hotspot Monitoring</h1>
    <p class="login-subtitle">Please enter your login details.</p>

    <!-- Login Form -->
    <form autocomplete="off" action="" method="post">
      <div class="login-field">
        <label for="_username">Username</label>
        <input type="text" name="user" id="_username" placeholder="Masukkan username" required="1" autofocus>
      </div>

      <div class="login-field">
        <label for="_password">Password</label>
        <div class="password-input-wrap">
          <input type="password" name="pass" id="_password" placeholder="••••••••••••" required="1">
          <button type="button" class="toggle-pass-btn" onclick="togglePassVisibility()" tabindex="-1" aria-label="Show or hide password">
            <i id="passEyeIcon" class="fa fa-eye-slash"></i>
          </button>
        </div>
      </div>

      <input class="btn-login-submit" type="submit" name="login" value="Login">

      <div class="login-error-wrap">
        <?= $error; ?>
      </div>
    </form>
  </div>
</div>

<script>
function togglePassVisibility() {
  var passInput = document.getElementById("_password");
  var eyeIcon = document.getElementById("passEyeIcon");
  if (passInput.type === "password") {
    passInput.type = "text";
    eyeIcon.classList.remove("fa-eye-slash");
    eyeIcon.classList.add("fa-eye");
  } else {
    passInput.type = "password";
    eyeIcon.classList.remove("fa-eye");
    eyeIcon.classList.add("fa-eye-slash");
  }
}
</script>

</div>
</body>
</html>