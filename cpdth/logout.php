<?php
    // เคลียร์ access_token cookie และ cookie consent
    setcookie('access_token', '', time() - 3600, '/');
    setcookie('cpdth_cookie_consent', '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Logging out...</title>
    <script>
        // ล้างค่า access_token ออกจาก localStorage
        localStorage.removeItem('access_token');
        // ล้างค่า cookie consent
        document.cookie = "cpdth_cookie_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        // เด้งไปหน้า login
        window.location.replace("login");
    </script>
</head>
<body>
</body>
</html>
