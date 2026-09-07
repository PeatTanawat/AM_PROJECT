<?php
date_default_timezone_set('Asia/Bangkok');

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Routing\Router;

$request_state    = isset($_POST['request_state']) ? trim($_POST['request_state']) : '';
$request_function = isset($_POST['request_function']) ? trim($_POST['request_function']) : '';

$router = new Router();

$routes = [
    'dashboard'                => [
        'get_dashboard'  => __DIR__ . '/core/dashboard/GetDashboard.php',
        'get_sms_credit' => __DIR__ . '/core/dashboard/GetSmsCredit.php',
    ],

    'list_user'                => [
        'user_profile'   => __DIR__ . '/core/listUser/UserProfile.php',
        'get_list_user'  => __DIR__ . '/core/listUser/GetListUser.php',
        'get_user'       => __DIR__ . '/core/listUser/GetUser.php',
        'update_user'    => __DIR__ . '/core/listUser/UpdateUser.php',
        'update_user_status' => __DIR__ . '/core/listUser/UpdateUserStatus.php',
        'delete_user'    => __DIR__ . '/core/listUser/DeleteUser.php',
        'login_as_user'  => __DIR__ . '/core/listUser/LoginAsUser.php',
        'export_user'    => __DIR__ . '/core/listUser/Export.php',
    ],
  
      'list_enrollment'          => [
        'get_list_enrollment' => __DIR__ . '/core/listEnrollment/GetListEnrollment.php',
        'add_enrollment'      => __DIR__ . '/core/listEnrollment/AddEnrollment.php',
        'get_enrollment'      => __DIR__ . '/core/listEnrollment/GetEnrollment.php',
        'update_enrollment'   => __DIR__ . '/core/listEnrollment/UpdateEnrollment.php',
        'unlock_enrollment'   => __DIR__ . '/core/listEnrollment/UnlockEnrollment.php',
        'cancel_enrollment'   => __DIR__ . '/core/listEnrollment/CancelEnrollment.php',
        'grant_enrollment'    => __DIR__ . '/core/listEnrollment/GrantEnrollment.php',
        'reset_learning'      => __DIR__ . '/core/listEnrollment/ResetLearning.php',
        'export_report'       => __DIR__ . '/core/listEnrollment/EnrollmentReport.php',
        'send_expiry_email'   => __DIR__ . '/core/listEnrollment/SendExpiryEmail.php',
        'send_all_expiry_emails' => __DIR__ . '/core/listEnrollment/SendAllExpiryEmails.php',
    ],
  
    'list_certificate'         => [
        'get_list_certificate' => __DIR__ . '/core/listCertificate/GetListCertificate.php',
        'get_certificate'      => __DIR__ . '/core/listCertificate/GetCertificate.php',
        'approve_certificate'  => __DIR__ . '/core/listCertificate/ApproveCertificate.php',
        'update_identity'      => __DIR__ . '/core/listCertificate/UpdateCertIdentity.php',
        'export_certificate'   => __DIR__ . '/core/listCertificate/ExportCertificate.php',
        'update_certificate'   => __DIR__ . '/core/listCertificate/UpdateCertificate.php',

    ],
    'list_etax'                => [
        'get_list_etax'  => __DIR__ . '/core/listEtax/GetListEtax.php',
        'send_email'     => __DIR__ . '/core/listEtax/SendEtaxEmail.php',
        'export_etax'    => __DIR__ . '/core/listEtax/ExportEtax.php',
    ],

    'list_etax_link'           => [
        'get_list'    => __DIR__ . '/core/listEtaxLink/GetListEtaxLink.php',
        'create'      => __DIR__ . '/core/listEtaxLink/CreateEtaxLink.php',
        'get'         => __DIR__ . '/core/listEtaxLink/GetEtaxLink.php',
        'export'      => __DIR__ . '/core/listEtaxLink/ExportEtaxLink.php',
        'toggle_link' => __DIR__ . '/core/listEtaxLink/ToggleLinkStatus.php',
        'delete'      => __DIR__ . '/core/listEtaxLink/DeleteEtaxLink.php',
    ],

    'report'                   => [
        'get_courses'   => __DIR__ . '/core/report/GetCourses.php',
        'export_report' => __DIR__ . '/core/report/ExportReport.php',
    ],

    'list_order'               => [
        'get_list_order'   => __DIR__ . '/core/listOrder/GetListOrder.php',
        'get_list_pending' => __DIR__ . '/core/listOrder/GetListPending.php',
        'get_order'        => __DIR__ . '/core/listOrder/GetOrder.php',
        'update_note'      => __DIR__ . '/core/listOrder/UpdateOrderNote.php',
        'update_address'   => __DIR__ . '/core/listOrder/UpdateOrderAddress.php',
        'confirm_payment'  => __DIR__ . '/core/listOrder/ConfirmPayment.php',
        'cancel_order'     => __DIR__ . '/core/listOrder/CancelOrder.php',
        'export_report'    => __DIR__ . '/core/listOrder/OrderReport.php',
    ],


    'list_admin'               => [
        'get_list_admin' => __DIR__ . '/core/listAdmin/GetListAdmin.php',
        'add_admin'      => __DIR__ . '/core/listAdmin/AddAdmin.php',
        'get_admin'      => __DIR__ . '/core/listAdmin/GetAdmin.php',
        'update_admin'   => __DIR__ . '/core/listAdmin/UpdateAdmin.php',
        'delete_admin'   => __DIR__ . '/core/listAdmin/DeleteAdmin.php',
        'get_menus'      => __DIR__ . '/core/listAdmin/GetMenus.php',
    ],

    'verify_history'           => [
        'get_list_history' => __DIR__ . '/core/verifyHistory/GetListHistory.php',
    ],

    'verify_request'           => [
        'get_list_verify' => __DIR__ . '/core/verifyRequest/GetListVerifyRequest.php',
        'get_verify'      => __DIR__ . '/core/verifyRequest/GetVerifyRequest.php',
        'update_verify'   => __DIR__ . '/core/verifyRequest/UpdateVerifyRequest.php',
    ],

    'list_notification'        => [
        'get_list' => __DIR__ . '/core/notification/GetList.php',
    ],

    'list_coupon'              => [
        'get_list_coupon' => __DIR__ . '/core/listCoupon/GetListCoupon.php',
        'add_coupon'      => __DIR__ . '/core/listCoupon/AddCoupon.php',
        'get_coupon'      => __DIR__ . '/core/listCoupon/GetCoupon.php',
        'update_coupon'   => __DIR__ . '/core/listCoupon/UpdateCoupon.php',
        'update_coupon_status' => __DIR__ . '/core/listCoupon/UpdateCouponStatus.php',
        'delete_coupon'   => __DIR__ . '/core/listCoupon/DeleteCoupon.php',
    ],

    'list_course'               => [
        'get_list_course'       => __DIR__ . '/core/listCourse/GetListCourse.php',
        'get_select_category'   => __DIR__ . '/core/listCourse/GetSelectCategory.php',
        'add_course'            => __DIR__ . '/core/listCourse/AddCourse.php',
        'get_course'            => __DIR__ . '/core/listCourse/GetCourse.php',
        'update_course'         => __DIR__ . '/core/listCourse/UpdateCourse.php',
        'delete_course'         => __DIR__ . '/core/listCourse/DeleteCourse.php',
    ],
    'listCourseCategory'               => [
        'get_list_category'       => __DIR__ . '/core/listCourseCategory/GetListCategory.php',
        'add_category'       => __DIR__ . '/core/listCourseCategory/AddCategory.php',
        'update_category'    => __DIR__ . '/core/listCourseCategory/UpdateCategory.php',
        'delete_category'    => __DIR__ . '/core/listCourseCategory/DeleteCategory.php',
    ],
    'listCourseType'               => [
        'get_list_type'           => __DIR__ . '/core/listCourseType/GetListType.php',
        'add_type'                => __DIR__ . '/core/listCourseType/AddType.php',
    ],
    'lesson'                    => [
        'get_list_lesson'       => __DIR__ . '/core/lesson/GetListLesson.php',
        'add_lesson'            => __DIR__ . '/core/lesson/AddLesson.php',
        'get_lesson'            => __DIR__ . '/core/lesson/GetLesson.php',
        'update_lesson'         => __DIR__ . '/core/lesson/UpdateLesson.php',
        'update_video'          => __DIR__ . '/core/lesson/UpdateVideo.php',
        'upload_video'          => __DIR__ . '/core/lesson/UploadVideo.php',
        'upload_video_local'    => __DIR__ . '/core/lesson/UploadVideoLocal.php',
        'create_upload'         => __DIR__ . '/core/lesson/CreateUpload.php',
        'finish_upload'         => __DIR__ . '/core/lesson/FinishUpload.php',
        'get_video_status'      => __DIR__ . '/core/lesson/GetVideoStatus.php',
        'delete_lesson'         => __DIR__ . '/core/lesson/DeleteLesson.php',
    ],
    'lesson_progress'           => [
        'get_progress'          => __DIR__ . '/core/lessonProgress/GetProgress.php',
        'save_progress'         => __DIR__ . '/core/lessonProgress/SaveProgress.php',
    ],
    'question'                  => [
        'get_list_question'     => __DIR__ . '/core/question/GetListQuestion.php',
        'add_question'          => __DIR__ . '/core/question/AddQuestion.php',
        'get_question'          => __DIR__ . '/core/question/GetQuestion.php',
        'update_question'       => __DIR__ . '/core/question/UpdateQuestion.php',
        'delete_question'       => __DIR__ . '/core/question/DeleteQuestion.php',
        'upload_question'       => __DIR__ . '/core/question/UploadQuestion.php',
    ],
    'lesson_file'               => [
        'get_list_lesson_file'  => __DIR__ . '/core/lessonFile/GetListLessonFile.php',
        'add_lesson_file'       => __DIR__ . '/core/lessonFile/AddLessonFile.php',
        'delete_lesson_file'    => __DIR__ . '/core/lessonFile/DeleteLessonFile.php',
    ],
    'exam'                      => [
        'get_list_exam'         => __DIR__ . '/core/exam/GetListExam.php',
        'add_exam'              => __DIR__ . '/core/exam/AddExam.php',
        'get_exam'              => __DIR__ . '/core/exam/GetExam.php',
        'update_exam'           => __DIR__ . '/core/exam/UpdateExam.php',
        'delete_exam'           => __DIR__ . '/core/exam/DeleteExam.php',
        'upload_exam'           => __DIR__ . '/core/exam/UploadExam.php',
    ],
    'list_banner'               => [
        'get_list_banner'       => __DIR__ . '/core/listBanner/GetListBanner.php',
        'add_banner'            => __DIR__ . '/core/listBanner/AddBanner.php',
        'get_banner'            => __DIR__ . '/core/listBanner/GetBanner.php',
        'update_banner'         => __DIR__ . '/core/listBanner/UpdateBanner.php',
        'delete_banner'         => __DIR__ . '/core/listBanner/DeleteBanner.php',
    ],

    'website_setting'           => [
        'get_setting'    => __DIR__ . '/core/websiteSetting/GetSetting.php',
        'update_setting' => __DIR__ . '/core/websiteSetting/UpdateSetting.php',
    ],

    'list_bank_setting'          => [
        'get_list_bank'      => __DIR__ . '/core/listBankSetting/GetListBankSetting.php',
        'get_bank_mapping'   => __DIR__ . '/core/listBankSetting/GetBankMapping.php',
        'add_bank'           => __DIR__ . '/core/listBankSetting/AddBank.php',
        'update_bank'        => __DIR__ . '/core/listBankSetting/UpdateBank.php',
        'update_bank_status' => __DIR__ . '/core/listBankSetting/UpdateBankStatus.php',
        'delete_bank'        => __DIR__ . '/core/listBankSetting/DeleteBank.php',
    ],

    'list_review'                => [
        'get_list_review' => __DIR__ . '/core/listReview/GetListReview.php',
        'add_review'       => __DIR__ . '/core/listReview/AddReview.php',
        'get_review'       => __DIR__ . '/core/listReview/GetReview.php',
        'update_review'    => __DIR__ . '/core/listReview/UpdateReview.php',
        'delete_review'    => __DIR__ . '/core/listReview/DeleteReview.php',
    ],

    'chat'                       => [
        'get_rooms'    => __DIR__ . '/core/chat/GetRooms.php',
        'get_messages' => __DIR__ . '/core/chat/GetMessages.php',
        'send_message' => __DIR__ . '/core/chat/SendMessage.php',
    ],

    'sidebar'                    => [
        'get_badges'   => __DIR__ . '/core/sidebarBadge/GetBadges.php',
    ],

    'list_address_setting'       => [
        'get_list_all_address'  => __DIR__ . '/core/listAddressSetting/GetListAllAddress.php',
        'get_list_province'     => __DIR__ . '/core/listAddressSetting/GetListProvince.php',
        'get_list_district'     => __DIR__ . '/core/listAddressSetting/GetListDistrict.php',
        'get_list_sub_district' => __DIR__ . '/core/listAddressSetting/GetListSubDistrict.php',
        'add_province'          => __DIR__ . '/core/listAddressSetting/AddProvince.php',
        'edit_province'         => __DIR__ . '/core/listAddressSetting/EditProvince.php',
        'delete_province'       => __DIR__ . '/core/listAddressSetting/DeleteProvince.php',
        'add_district'          => __DIR__ . '/core/listAddressSetting/AddDistrict.php',
        'edit_district'         => __DIR__ . '/core/listAddressSetting/EditDistrict.php',
        'delete_district'       => __DIR__ . '/core/listAddressSetting/DeleteDistrict.php',
        'add_sub_district'      => __DIR__ . '/core/listAddressSetting/AddSubDistrict.php',
        'edit_sub_district'     => __DIR__ . '/core/listAddressSetting/EditSubDistrict.php',
        'delete_sub_district'   => __DIR__ . '/core/listAddressSetting/DeleteSubDistrict.php',
    ],
];
foreach ($routes as $state => $actions) {
    foreach ($actions as $action => $file) {
        $router->post($state, $action, $file);
    }
}

$router->dispatch($request_state, $request_function);
