<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use App\Routing\Router;

$request_state = isset($_POST['request_state']) ? trim($_POST['request_state']) : '';
$request_function = isset($_POST['request_function']) ? trim($_POST['request_function']) : '';

error_log("Incoming request to core.php. Method: " . $_SERVER['REQUEST_METHOD'] . " | POST keys: " . implode(',', array_keys($_POST)) . " | State: $request_state | Func: $request_function | URI: " . $_SERVER['REQUEST_URI']);


$router = new Router();

$routes = [
    'register' => [
        'register' => __DIR__ . '/core/mainRegister/Register.php',
        'resend_verification' => __DIR__ . '/core/mainRegister/ResendVerification.php',
        'check_status' => __DIR__ . '/core/mainRegister/CheckStatus.php',
    ],
    'login' => [
        'login' => __DIR__ . '/core/mainLogin/Login.php',
        'forgot_password' => __DIR__ . '/core/mainLogin/ForgotPassword.php',
        'check_course_expiry' => __DIR__ . '/core/mainLogin/CheckCourseExpiry.php',
    ],
    'password' => [
        'edit_password' => __DIR__ . '/core/mainEditPassword/edit_password.php',
        'reset_password' => __DIR__ . '/core/mainEditPassword/ResetPasswordHandler.php',
    ],
    'profile' => [
        'get_profile' => __DIR__ . '/core/mainProfile/GetProfile.php',
        'save_identity_verify' => __DIR__ . '/core/mainProfile/SaveIdentityVerify.php',
        'edit_identity_verify' => __DIR__ . '/core/mainProfile/EditIdentityVerify.php',
        'save_change_phone' => __DIR__ . '/core/mainProfile/SaveChangePhone.php',
    ],
    'address' => [
        'get_address' => __DIR__ . '/core/mainAddress/GetAddress.php',
        'save_address' => __DIR__ . '/core/mainAddress/SaveAddress.php',
        'delete_address' => __DIR__ . '/core/mainAddress/DeleteAddress.php',
        'set_default_address' => __DIR__ . '/core/mainAddress/SetDefaultAddress.php',
        'get_thai_address' => __DIR__ . '/core/mainAddress/GetThaiAddressData.php',
    ],
    'my_course' => [
        'get_my_course' => __DIR__ . '/core/mainmy_course/Getmy_course.php',
    ],
    'course' => [
        'list' => __DIR__ . '/core/mainCourse/CourseList.php',
        'detail' => __DIR__ . '/core/mainCourse/CourseDetail.php',
        'get_index_courses' => __DIR__ . '/core/mainCourse/GetIndexCourses.php',
        'add_cart' => __DIR__ . '/core/mainCourse/AddCart.php',
    ],
    'cart' => [
        'get' => __DIR__ . '/core/mainCart/GetCart.php',
        'remove' => __DIR__ . '/core/mainCart/RemoveCartItem.php',
        'create_order' => __DIR__ . '/core/mainCart/CreateOrder.php',
        'mock_pay' => __DIR__ . '/core/mainCart/MockPay.php',
        'verify_coupon' => __DIR__ . '/core/mainCart/VerifyCoupon.php',
        'check_payment_status' => __DIR__ . '/core/mainCart/CheckPaymentStatus.php',
    ],
    'banner' => [
        'get_list' => __DIR__ . '/core/mainBanner/GetBannerList.php',
    ],
    'history' => [
        'get_history' => __DIR__ . '/core/mainHistory/GetHistory.php',
        'get_detail' => __DIR__ . '/core/mainHistory/GetDetail.php',
        'reupload_slip' => __DIR__ . '/core/mainHistory/ReuploadSlip.php',
    ],
    'study_course' => [
        'get_study_course' => __DIR__ . '/core/mainstudy_course/GetStudyCourse.php',
        'save_progress' => __DIR__ . '/core/mainstudy_course/SaveStudyProgress.php',
        'get_lesson' => __DIR__ . '/core/mainstudy_course/GetLesson.php',
        'request_otp' => __DIR__ . '/core/mainstudy_course/RequestOTP.php',
        'verify_otp' => __DIR__ . '/core/mainstudy_course/VerifyOTP.php',
        'verify_question' => __DIR__ . '/core/mainstudy_course/VerifyQuestion.php'
    ],
    'exam' => [
        'get_exam' => __DIR__ . '/core/mainExam/Getexam.php',
        'save_exam_result' => __DIR__ . '/core/mainExam/SaveExamResult.php',
    ],
    'tax_invoice' => [
        'get_etax_list' => __DIR__ . '/core/mainTaxInvoice/GetEtaxList.php',
        'export_etax' => __DIR__ . '/core/mainTaxInvoice/ExportEtax.php',
    ],
    'certificate' => [
        'get_certificates' => __DIR__ . '/core/mainCertificate/GetCertificates.php',
    ],
    'print' => [
        'print_etax' => __DIR__ . '/core/mainPrint/print_etax.php',
        'print_certificate' => __DIR__ . '/core/mainPrint/print_certificate.php',
    ],
    'setting' => [
        'get_setting' => __DIR__ . '/core/mainSettingWebsite/GetSetting.php',
    ],
    'index' => [
        'get_index_data' => __DIR__ . '/core/mainIndex/GetIndexData.php',
    ],
    'chat' => [
        'send_message' => __DIR__ . '/core/mainChat/SendMessage.php',
        'get_messages' => __DIR__ . '/core/mainChat/GetMessage.php',
    ],
    'mainReview' => [
        'get_review' => __DIR__ . '/core/mainReview/Get_Review.php',
    ],
    'line_auth' => [
        'check_login' => __DIR__ . '/core/mainLineAuth/CheckLogin.php',
        'link_line' => __DIR__ . '/core/mainLineAuth/LinkLine.php',
        'unlink_line' => __DIR__ . '/core/mainLineAuth/UnlinkLine.php',
    ],
];

foreach ($routes as $state => $actions) {
    foreach ($actions as $action => $file) {
        $router->post($state, $action, $file);
    }
}

$router->dispatch($request_state, $request_function);
