<?php
setcookie('bo_access_token', '', time() - 3600, '/');
setcookie('access_token', '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>Logout</title>
</head>
<body>
    <script>
        async function doLogout() {
            try {
                // ยกเลิก Push Subscription (ถ้ามี)
                if ('serviceWorker' in navigator) {
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();
                    if (subscription) {
                        // ส่งคำขอลบข้อมูลออกจากเซิร์ฟเวอร์
                        await fetch('../delete_subscription.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ endpoint: subscription.endpoint })
                        });
                        // ยกเลิกฝั่ง client
                        await subscription.unsubscribe();
                    }
                }
            } catch (err) {
                console.error("Unsubscribe error: ", err);
            }

            // ออกจากระบบ: ลบ access token ที่เก็บไว้ใน localStorage และ cookie แล้วกลับไปหน้า login
            localStorage.removeItem("bo_access_token");
            document.cookie = "bo_access_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
            window.location.replace("../index");
        }

        doLogout();
    </script>
</body>
</html>
