<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات مزود الخدمة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_provider']) {
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// جلب بيانات مزود الخدمة
$provider_sql = "SELECT * FROM users WHERE id = ?";
$provider_stmt = $conn->prepare($provider_sql);
$provider_stmt->bind_param("i", $provider_id);
$provider_stmt->execute();
$provider_result = $provider_stmt->get_result();
$provider = $provider_result->fetch_assoc();

// جلب بيانات الملف الشخصي
$profile_sql = "SELECT * FROM provider_profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $provider_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();

if ($profile_result->num_rows > 0) {
    $profile = $profile_result->fetch_assoc();
} else {
    // إنشاء ملف شخصي فارغ إذا لم يكن موجودًا
    $profile = [
        'bio' => '',
        'phone' => '',
        'address' => '',
        'city' => '',
        'country' => '',
        'website' => '',
        'experience_years' => 0,
        'specialization' => '',
        'facebook' => '',
        'twitter' => '',
        'instagram' => '',
        'linkedin' => ''
    ];
}

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // تحديث بيانات المستخدم
        $username = $_POST['username'];
        $email = $_POST['email'];
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // التحقق من البريد الإلكتروني واسم المستخدم
        $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssi", $username, $email, $provider_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = __('username_or_email_exists');
        } else {
            // تحديث بيانات المستخدم
            if (!empty($current_password)) {
                // التحقق من كلمة المرور الحالية
                if (password_verify($current_password, $provider['password'])) {
                    if (!empty($new_password) && $new_password === $confirm_password) {
                        // تحديث كلمة المرور
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_user_sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
                        $update_user_stmt = $conn->prepare($update_user_sql);
                        $update_user_stmt->bind_param("sssi", $username, $email, $hashed_password, $provider_id);
                    } else {
                        $error_message = __('new_passwords_not_match');
                    }
                } else {
                    $error_message = __('current_password_incorrect');
                }
            } else {
                // تحديث بدون تغيير كلمة المرور
                $update_user_sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
                $update_user_stmt = $conn->prepare($update_user_sql);
                $update_user_stmt->bind_param("ssi", $username, $email, $provider_id);
            }
            
            if (empty($error_message)) {
                if ($update_user_stmt->execute()) {
                    // تحديث بيانات الملف الشخصي
                    $bio = $_POST['bio'];
                    $phone = $_POST['phone'];
                    $address = $_POST['address'];
                    $city = $_POST['city'];
                    $country = $_POST['country'];
                    $website = $_POST['website'];
                    $experience_years = $_POST['experience_years'];
                    $specialization = $_POST['specialization'];
                    $facebook = $_POST['facebook'];
                    $twitter = $_POST['twitter'];
                    $instagram = $_POST['instagram'];
                    $linkedin = $_POST['linkedin'];
                    
                    // التحقق من وجود ملف شخصي
                    $check_profile_sql = "SELECT user_id FROM provider_profiles WHERE user_id = ?";
                    $check_profile_stmt = $conn->prepare($check_profile_sql);
                    $check_profile_stmt->bind_param("i", $provider_id);
                    $check_profile_stmt->execute();
                    $check_profile_result = $check_profile_stmt->get_result();
                    
                    if ($check_profile_result->num_rows > 0) {
                        // تحديث الملف الشخصي الموجود
                        $update_profile_sql = "UPDATE provider_profiles SET 
                            bio = ?, phone = ?, address = ?, city = ?, country = ?, 
                            website = ?, experience_years = ?, specialization = ?,
                            facebook = ?, twitter = ?, instagram = ?, linkedin = ? 
                            WHERE user_id = ?";
                        $update_profile_stmt = $conn->prepare($update_profile_sql);
                        $update_profile_stmt->bind_param("ssssssissssi", 
                            $bio, $phone, $address, $city, $country, 
                            $website, $experience_years, $specialization,
                            $facebook, $twitter, $instagram, $linkedin, 
                            $provider_id);
                    } else {
                        // إنشاء ملف شخصي جديد
                        $update_profile_sql = "INSERT INTO provider_profiles 
                            (user_id, bio, phone, address, city, country, 
                            website, experience_years, specialization,
                            facebook, twitter, instagram, linkedin) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $update_profile_stmt = $conn->prepare($update_profile_sql);
                        $update_profile_stmt->bind_param("issssssisssss", 
                            $provider_id, $bio, $phone, $address, $city, $country, 
                            $website, $experience_years, $specialization,
                            $facebook, $twitter, $instagram, $linkedin);
                    }
                    
                    if ($update_profile_stmt->execute()) {
                        $success_message = __('profile_updated_successfully');
                        
                        // تحديث بيانات الجلسة
                        $_SESSION['username'] = $username;
                        
                        // إعادة تحميل بيانات المستخدم والملف الشخصي
                        $provider_stmt->execute();
                        $provider_result = $provider_stmt->get_result();
                        $provider = $provider_result->fetch_assoc();
                        
                        $profile_stmt->execute();
                        $profile_result = $profile_stmt->get_result();
                        $profile = $profile_result->fetch_assoc();
                    } else {
                        $error_message = __('error_updating_profile') . ': ' . $conn->error;
                    }
                } else {
                    $error_message = __('error_updating_user') . ': ' . $conn->error;
                }
            }
        }
    } elseif (isset($_POST['update_image'])) {
        // معالجة تحديث الصورة
        if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['user_image']['name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($file_ext), $allowed)) {
                $new_name = 'user_' . $provider_id . '_' . time() . '.' . $file_ext;
                $upload_dir = '../images/users/';
                
                // إنشاء المجلد إذا لم يكن موجودًا
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $upload_path = $upload_dir . $new_name;
                
                if (move_uploaded_file($_FILES['user_image']['tmp_name'], $upload_path)) {
                    // حذف الصورة القديمة إذا كانت موجودة
                    if (!empty($provider['image'])) {
                        $old_image_path = '../images/users/' . $provider['image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    
                    // تحديث الصورة في قاعدة البيانات
                    $update_image_sql = "UPDATE users SET image = ? WHERE id = ?";
                    $update_image_stmt = $conn->prepare($update_image_sql);
                    $update_image_stmt->bind_param("si", $new_name, $provider_id);
                    
                    if ($update_image_stmt->execute()) {
                        $success_message = __('image_updated_successfully');
                        
                        // تحديث بيانات الجلسة
                        $_SESSION['user_image'] = $new_name;
                        
                        // إعادة تحميل بيانات المستخدم
                        $provider_stmt->execute();
                        $provider_result = $provider_stmt->get_result();
                        $provider = $provider_result->fetch_assoc();
                    } else {
                        $error_message = __('error_updating_image') . ': ' . $conn->error;
                    }
                } else {
                    $error_message = __('error_uploading_image');
                }
            } else {
                $error_message = __('invalid_image_format');
            }
        } else {
            $error_message = __('no_image_selected');
        }
    }
}

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'profile';
$page_title = __('profile');

// تضمين ملف الهيدر الذي يحتوي على الشريط الجانبي
include 'includes/header.php';
?>

<div class="provider-content">
    <div class="provider-content-header">
        <h1><?php echo __('profile'); ?></h1>
    </div>
    
    <?php if ($success_message): ?>
        <div class="provider-alert provider-alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="provider-alert provider-alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="provider-row">
        <div class="provider-col-lg-4">
            <div class="provider-card">
                <div class="provider-card-body text-center">
                    <div class="provider-profile-image">
                        <?php if (!empty($provider['image'])): ?>
                            <img src="../images/users/<?php echo $provider['image']; ?>" alt="<?php echo htmlspecialchars($provider['username']); ?>">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="provider-profile-name"><?php echo htmlspecialchars($provider['username']); ?></h3>
                    <p class="provider-profile-email"><?php echo htmlspecialchars($provider['email']); ?></p>
                    
                    <form method="post" enctype="multipart/form-data" class="provider-form">
                        <div class="provider-form-group">
                            <label for="user_image" class="provider-btn provider-btn-outline provider-btn-block">
                                <i class="fas fa-camera"></i> <?php echo __('change_profile_picture'); ?>
                            </label>
                            <input type="file" id="user_image" name="user_image" class="provider-file-input" accept="image/*">
                        </div>
                        <button type="submit" name="update_image" class="provider-btn provider-btn-primary provider-btn-block">
                            <i class="fas fa-save"></i> <?php echo __('save_image'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="provider-card mt-4">
                <div class="provider-card-header">
                    <h3><?php echo __('account_info'); ?></h3>
                </div>
                <div class="provider-card-body">
                    <div class="provider-info-item">
                        <span class="provider-info-label"><?php echo __('member_since'); ?></span>
                        <span class="provider-info-value"><?php echo date('Y-m-d', strtotime($provider['created_at'])); ?></span>
                    </div>
                    <div class="provider-info-item">
                        <span class="provider-info-label"><?php echo __('status'); ?></span>
                        <span class="provider-info-value">
                            <span class="provider-badge <?php echo $provider['is_active'] ? 'provider-badge-success' : 'provider-badge-danger'; ?>">
                                <?php echo $provider['is_active'] ? __('active') : __('inactive'); ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="provider-col-lg-8">
            <div class="provider-card">
                <div class="provider-card-header">
                    <h3><?php echo __('edit_profile'); ?></h3>
                </div>
                <div class="provider-card-body">
                    <form method="post" class="provider-form">
                        <div class="provider-form-section">
                            <h4><?php echo __('basic_information'); ?></h4>
                            
                            <div class="provider-row">
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="username"><?php echo __('username'); ?></label>
                                        <input type="text" id="username" name="username" class="provider-form-control" value="<?php echo htmlspecialchars($provider['username']); ?>" required>
                                    </div>
                                </div>
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="email"><?php echo __('email'); ?></label>
                                        <input type="email" id="email" name="email" class="provider-form-control" value="<?php echo htmlspecialchars($provider['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="provider-form-group">
                                <label for="bio"><?php echo __('bio'); ?></label>
                                <textarea id="bio" name="bio" class="provider-form-control" rows="4"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="provider-row">
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="phone"><?php echo __('phone'); ?></label>
                                        <input type="text" id="phone" name="phone" class="provider-form-control" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="website"><?php echo __('website'); ?></label>
                                        <input type="url" id="website" name="website" class="provider-form-control" value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="provider-form-section">
                            <h4><?php echo __('address_information'); ?></h4>
                            
                            <div class="provider-form-group">
                                <label for="address"><?php echo __('address'); ?></label>
                                <input type="text" id="address" name="address" class="provider-form-control" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="provider-row">
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="city"><?php echo __('city'); ?></label>
                                        <input type="text" id="city" name="city" class="provider-form-control" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="country"><?php echo __('country'); ?></label>
                                        <input type="text" id="country" name="country" class="provider-form-control" value="<?php echo htmlspecialchars($profile['country'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="provider-form-section">
                            <h4><?php echo __('professional_information'); ?></h4>
                            
                            <div class="provider-row">
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="specialization"><?php echo __('specialization'); ?></label>
                                        <input type="text" id="specialization" name="specialization" class="provider-form-control" value="<?php echo htmlspecialchars($profile['specialization'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="experience_years"><?php echo __('experience_years'); ?></label>
                                        <input type="number" id="experience_years" name="experience_years" class="provider-form-control" value="<?php echo intval($profile['experience_years'] ?? 0); ?>" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="provider-form-section">
                            <h4><?php echo __('social_media'); ?></h4>
                            
                            <div class="provider-row">
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="facebook"><i class="fab fa-facebook"></i> <?php echo __('facebook'); ?></label>
                                        <input type="url" id="facebook" name="facebook" class="provider-form-control" value="<?php echo htmlspecialchars($profile['facebook'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="twitter"><i class="fab fa-twitter"></i> <?php echo __('twitter'); ?></label>
                                        <input type="url" id="twitter" name="twitter" class="provider-form-control" value="<?php echo htmlspecialchars($profile['twitter'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="provider-row">
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="instagram"><i class="fab fa-instagram"></i> <?php echo __('instagram'); ?></label>
                                        <input type="url" id="instagram" name="instagram" class="provider-form-control" value="<?php echo htmlspecialchars($profile['instagram'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="provider-col-md-6">
                                    <div class="provider-form-group">
                                        <label for="linkedin"><i class="fab fa-linkedin"></i> <?php echo __('linkedin'); ?></label>
                                        <input type="url" id="linkedin" name="linkedin" class="provider-form-control" value="<?php echo htmlspecialchars($profile['linkedin'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="provider-form-section">
                            <h4><?php echo __('change_password'); ?></h4>
                            
                            <div class="provider-form-group">
                                <label for="current_password"><?php echo __('current_password'); ?></label>
                                <input type="password" id="current_password" name="current_password" class="provider-form-control">
                            </div>
                            
                            <div class="provider-form-group">
                                <label for="new_password"><?php echo __('new_password'); ?></label>
                                <input type="password" id="new_password" name="new_password" class="provider-form-control">
                            </div>
                            
                            <div class="provider-form-group">
                                <label for="confirm_password"><?php echo __('confirm_password'); ?></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="provider-form-control">
                            </div>
                        </div>
                        
                        <div class="provider-form-group">
                            <button type="submit" name="update_profile" class="provider-btn provider-btn-primary">
                                <i class="fas fa-save"></i> <?php echo __('save_changes'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

