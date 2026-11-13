<?php
/**
 * SILAP – Logout Handler
 */
session_start();

// Hapus semua data sesi PHP
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Mengeluarkan Sesi...</title>
  <script>
    // Bersihkan sesi di LocalStorage browser
    try {
      localStorage.removeItem("silap_user_session");
      localStorage.removeItem("silap_current_user");
      localStorage.removeItem("jawara_user_session");
      localStorage.removeItem("jawara_current_user");
    } catch(e) {}
    // Alihkan kembali ke halaman login
    window.location.href = "login.php?msg=logout_berhasil";
  </script>
</head>
<body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8fafc;color:#64748b;">
  <p>Sedang keluar dari sistem SILAP...</p>
</body>
</html>
