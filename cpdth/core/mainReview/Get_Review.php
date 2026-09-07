<?php
use App\Database\Connection;
use App\Utility\Response;

/**
 * Resolve an avatar/image value from the database into a usable URL.
 *
 * Handles all cases regardless of how the value was stored:
 *   - Plain absolute URL:          "https://cpdth-storage.s3..."
 *   - Corrupted with prefix:       "backoffice/https://cpdth-storage.s3..."
 *   - Relative path:               "reviews/reviewer/abc.png"
 *
 * @param string $raw       Raw value from DB column
 * @param string $prefix    Prefix to prepend for relative paths (e.g. 'backoffice/')
 * @return string           Resolved URL
 */
function resolveAvatarUrl(?string $raw, string $prefix): string
{
    $val = trim((string)$raw);
    if ($val === '') {
        return 'assets/images/profile-default.jpg';
    }
    // Remote URL
    if (preg_match('#^(https?://\S+)#i', $val, $m)) {
        return $m[1];
    }
    
    // Check if using local upload folder
    $clean = preg_replace('~^(am/)?(backoffice/)?upload/~i', '', ltrim($val, './'));
    $localUpload = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'backoffice' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
    if (is_file($localUpload)) {
        return '../backoffice/upload/' . $clean;
    }

    // Relative to prefix (e.g. backoffice/)
    $targetPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $prefix . $val);
    if (is_file($targetPath)) {
        return $prefix . $val;
    }

    return 'assets/images/profile-default.jpg';
}

try {

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (! $pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    $sql = "SELECT r.*, u.user_firstname, u.user_lastname, u.user_cpa_no, u.user_cpd_no, u.current_photo 
            FROM tbl_reviews r
            LEFT JOIN tbl_user u ON r.user_id = u.user_id
            WHERE r.is_approved = '1'
            ORDER BY r.created_at DESC";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $formatted_reviews = [];
    foreach ($reviews as $row) {
        $name = $row['reviewer_name'] ?: ($row['user_firstname'] . ' ' . $row['user_lastname']);
        if (trim($name) === '') {
            $name = "ผู้เรียนไม่ประสงค์ออกนาม";
        }
        
        $role = "ผู้เรียน";
        if (!empty($row['user_cpa_no']) && !empty($row['user_cpd_no'])) {
            $role = "ผู้สอบบัญชี / ผู้ทำบัญชี";
        } elseif (!empty($row['user_cpa_no'])) {
            $role = "ผู้สอบบัญชี";
        } elseif (!empty($row['user_cpd_no'])) {
            $role = "ผู้ทำบัญชี";
        }

        // Check if image exists, otherwise fallback to default
        $avatar = 'assets/images/default-avatar.png';
        if (!empty($row['reviewer_image'])) {
            $avatar = resolveAvatarUrl($row['reviewer_image'], 'backoffice/');
        } elseif (!empty($row['current_photo'])) {
            $avatar = resolveAvatarUrl($row['current_photo'], 'assets/img/user/');
        }
        
        // Convert to Thai Year
        $ts = strtotime($row['created_at']);
        $thai_year = date('Y', $ts) + 543;
        $date_str = date('d/m/', $ts) . $thai_year . date(' H:i', $ts);

        $formatted_reviews[] = [
            'id' => $row['review_id'],
            'name' => trim($name),
            'role' => $role,
            'avatar' => $avatar,
            'rating' => (int)$row['rating'],
            'comment' => $row['comment'],
            'date' => $date_str
        ];
    }

    Response::json(1, 'Success', $formatted_reviews);

} catch (Throwable $e) {
    Response::json(0, $e->getMessage(), null);
}
