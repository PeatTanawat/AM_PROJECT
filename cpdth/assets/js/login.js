document.getElementById('user_password').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        Login();
    }
});

document.getElementById('user_email').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        Login();
    }
});

const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#user_password');

togglePassword.addEventListener('click', function (e) {
    // toggle the type attribute
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    // toggle the eye / eye slash icon
    this.classList.toggle('bi-eye-fill');
    this.classList.toggle('bi-eye-slash-fill');
});

function Login() {
    const user_email = $("#user_email").val();
    const user_password = $("#user_password").val();

    if (user_email == "" || user_password == "") {
        Swal.fire({
            title: "แจ้งเตือน",
            html: '<span class="fw-bold text-danger">กรุณากรอก Email และ Password</span>',
            icon: "warning",
            showConfirmButton: false,
            allowOutsideClick: false,
            timer: 2000,
            timerProgressBar: true,
        });
        return false;
    }

    $.ajax({
        beforeSend: function() {
            Swal.fire({
                title: 'กำลังประมวลผล...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        type: "POST",
        url: "core.php",
        data: {
            request_state: "login",
            request_function: "login",
            username: user_email,
            password: user_password,
        },
        dataType: "json",
        success: function (response) {
            if (response.result == 1) {
                const token = response.data.access_token;

                // บันทึก JWT ลงใน localStorage และ Cookie
                localStorage.setItem('access_token', token);
                document.cookie = "access_token=" + token + "; path=/; max-age=25200; SameSite=Lax";

                if (response.data.id_card_expired) {
                    localStorage.setItem('show_id_card_expired_toast', '1');
                } else {
                    localStorage.removeItem('show_id_card_expired_toast');
                }

                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-success">'+response.msg+'</span>',
                    icon: "success",
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didClose: () => {
                        window.location.replace("index");
                    }
                });
            } else {
                if (response.data && response.data.unverified) {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-warning">' + response.msg + '</span>',
                        icon: "warning",
                        showConfirmButton: true,
                        confirmButtonText: "ไปยังหน้ายืนยันอีเมล",
                        showCancelButton: true,
                        cancelButtonText: "ปิด",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "verify-email.php?email=" + encodeURIComponent(response.data.email);
                        }
                    });
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">'+response.msg+'</span>',
                        icon: "warning",
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
            }
        },
        error: function(jqXHR, exception) {
            let msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                msg = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                msg = 'Time out error.';
            } else if (exception === 'abort') {
                msg = 'Ajax request aborted.';
            } else {
                msg = 'Uncaught Error.\n' + jqXHR.responseText;
            }
            Swal.fire({
                title: "แจ้งเตือน",
                html: "พบปัญหาการบันทึก กรุณาติดต่อผู้ดูแลระบบ<br>"+ msg,
                icon: "error",
                showConfirmButton: true,
            });
        }
    });
}
