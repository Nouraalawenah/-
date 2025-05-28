<?php
require_once 'config/db_connect.php';

// توحيد ترميز الأحرف في قاعدة البيانات
$set_charset = "ALTER DATABASE `service_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($set_charset) === TRUE) {
    echo "تم توحيد ترميز قاعدة البيانات بنجاح<br>";
} else {
    echo "خطأ في توحيد ترميز قاعدة البيانات: " . $conn->error . "<br>";
}

// توحيد ترميز الأحرف في الجداول
$tables = ["users", "categories", "service_providers", "services", "languages", "contact_messages"];
foreach ($tables as $table) {
    $convert_table = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($convert_table) === TRUE) {
        echo "تم توحيد ترميز جدول $table بنجاح<br>";
    } else {
        echo "خطأ في توحيد ترميز جدول $table: " . $conn->error . "<br>";
    }
}

// التحقق من وجود الأعمدة قبل إضافتها
$check_columns_categories = "SHOW COLUMNS FROM categories LIKE 'name_ar'";
$result = $conn->query($check_columns_categories);

if ($result->num_rows == 0) {
    // إضافة أعمدة متعددة اللغات لجدول الفئات
    $add_multilingual_categories = "
    ALTER TABLE categories 
    ADD COLUMN name_ar VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN name_en VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN description_ar TEXT,
    ADD COLUMN description_en TEXT";

    if ($conn->query($add_multilingual_categories) === TRUE) {
        echo "تم إضافة دعم تعدد اللغات لجدول الفئات بنجاح<br>";
    } else {
        echo "خطأ في إضافة دعم تعدد اللغات لجدول الفئات: " . $conn->error . "<br>";
    }
} else {
    echo "أعمدة اللغات موجودة بالفعل في جدول الفئات<br>";
}

// التحقق من وجود الأعمدة في جدول مقدمي الخدمة
$check_columns_providers = "SHOW COLUMNS FROM service_providers LIKE 'name_ar'";
$result = $conn->query($check_columns_providers);

if ($result->num_rows == 0) {
    // إضافة أعمدة متعددة اللغات لجدول مقدمي الخدمة
    $add_multilingual_providers = "
    ALTER TABLE service_providers 
    ADD COLUMN name_ar VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN name_en VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN description_ar TEXT,
    ADD COLUMN description_en TEXT";

    if ($conn->query($add_multilingual_providers) === TRUE) {
        echo "تم إضافة دعم تعدد اللغات لجدول مقدمي الخدمة بنجاح<br>";
    } else {
        echo "خطأ في إضافة دعم تعدد اللغات لجدول مقدمي الخدمة: " . $conn->error . "<br>";
    }
} else {
    echo "أعمدة اللغات موجودة بالفعل في جدول مقدمي الخدمة<br>";
}

// إنشاء جدول الفئات إذا لم يكن موجودًا
$create_categories = "
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    image VARCHAR(100),
    name_ar VARCHAR(100) NOT NULL DEFAULT '',
    name_en VARCHAR(100) NOT NULL DEFAULT '',
    description_ar TEXT,
    description_en TEXT
)";

if ($conn->query($create_categories) === TRUE) {
    echo "تم إنشاء جدول الفئات بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول الفئات: " . $conn->error . "<br>";
}

// التحقق مما إذا كان عمود الصورة موجودًا بالفعل
$check_image_column = "SHOW COLUMNS FROM categories LIKE 'image'";
$result = $conn->query($check_image_column);

// إذا لم يكن عمود الصورة موجودًا، قم بإضافته
if ($result->num_rows == 0) {
    $add_image_column = "ALTER TABLE categories ADD COLUMN image VARCHAR(100)";
    if ($conn->query($add_image_column) === TRUE) {
        echo "تم إضافة عمود الصورة إلى جدول الفئات بنجاح<br>";
    } else {
        echo "خطأ في إضافة عمود الصورة: " . $conn->error . "<br>";
    }
}

// التحقق من وجود بيانات في جدول الفئات
$check_categories = "SELECT COUNT(*) as count FROM categories";
$result = $conn->query($check_categories);
$row = $result->fetch_assoc();

// إذا كان الجدول فارغًا، قم بإضافة الفئات
if ($row['count'] == 0) {
    $categories = [
        ['إصلاح الكهرباء', 'خدمات صيانة وإصلاح الأعطال الكهربائية المنزلية', 'bolt', 'electricity.jpg', 'إصلاح الكهرباء', 'Electrical Repair', 'خدمات صيانة وإصلاح الأعطال الكهربائية المنزلية', 'Electrical maintenance and repair services for homes'],
        ['خدمات التنظيف', 'خدمات تنظيف المنازل والمكاتب بجودة عالية', 'broom', 'cleaning.jpg', 'خدمات التنظيف', 'Cleaning Services', 'خدمات تنظيف المنازل والمكاتب بجودة عالية', 'High quality home and office cleaning services'],
        ['الدهان', 'خدمات دهان وتجديد المنازل والمباني', 'paint-roller', 'painting.jpg', 'الدهان', 'Painting', 'خدمات دهان وتجديد المنازل والمباني', 'Painting and renovation services for homes and buildings'],
        ['تكييف وتدفئة', 'تركيب وصيانة أنظمة التكييف والتدفئة', 'temperature-high', 'hvac.jpg', 'تكييف وتدفئة', 'HVAC', 'تركيب وصيانة أنظمة التكييف والتدفئة', 'Installation and maintenance of air conditioning and heating systems'],
        ['نجارة', 'خدمات النجارة وإصلاح وتركيب الأثاث الخشبي', 'hammer', 'carpentry.jpg', 'نجارة', 'Carpentry', 'خدمات النجارة وإصلاح وتركيب الأثاث الخشبي', 'Carpentry services and wooden furniture repair and installation'],
        ['سباكة', 'خدمات السباكة وإصلاح تسربات المياه', 'faucet', 'plumbing.jpg', 'سباكة', 'Plumbing', 'خدمات السباكة وإصلاح تسربات المياه', 'Plumbing services and water leak repairs'],
        ['إزالة النفايات', 'خدمات إزالة ونقل النفايات والمخلفات', 'trash', 'waste.jpg', 'إزالة النفايات', 'Waste Removal', 'خدمات إزالة ونقل النفايات والمخلفات', 'Waste removal and transportation services'],
        ['حدائق', 'خدمات تنسيق وصيانة الحدائق والمساحات الخضراء', 'leaf', 'gardening.jpg', 'حدائق', 'Gardening', 'خدمات تنسيق وصيانة الحدائق والمساحات الخضراء', 'Garden design and maintenance services']
    ];
    
    $insert_sql = "INSERT INTO categories (name, description, icon, image, name_ar, name_en, description_ar, description_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    
    foreach ($categories as $category) {
        $stmt->bind_param("ssssssss", $category[0], $category[1], $category[2], $category[3], $category[4], $category[5], $category[6], $category[7]);
        if ($stmt->execute()) {
            echo "تم إضافة فئة: " . $category[0] . "<br>";
        } else {
            echo "خطأ في إضافة فئة: " . $category[0] . " - " . $stmt->error . "<br>";
        }
    }
    
    echo "تم إضافة جميع الفئات بنجاح<br>";
} else {
    // تحديث الفئات الموجودة لإضافة الصور وبيانات اللغات
    $update_categories = [
        [1, 'electricity.jpg', 'إصلاح الكهرباء', 'Electrical Repair', 'خدمات صيانة وإصلاح الأعطال الكهربائية المنزلية', 'Electrical maintenance and repair services for homes'],
        [2, 'cleaning.jpg', 'خدمات التنظيف', 'Cleaning Services', 'خدمات تنظيف المنازل والمكاتب بجودة عالية', 'High quality home and office cleaning services'],
        [3, 'painting.jpg', 'الدهان', 'Painting', 'خدمات دهان وتجديد المنازل والمباني', 'Painting and renovation services for homes and buildings'],
        [4, 'hvac.jpg', 'تكييف وتدفئة', 'HVAC', 'تركيب وصيانة أنظمة التكييف والتدفئة', 'Installation and maintenance of air conditioning and heating systems'],
        [5, 'carpentry.jpg', 'نجارة', 'Carpentry', 'خدمات النجارة وإصلاح وتركيب الأثاث الخشبي', 'Carpentry services and wooden furniture repair and installation'],
        [6, 'plumbing.jpg', 'سباكة', 'Plumbing', 'خدمات السباكة وإصلاح تسربات المياه', 'Plumbing services and water leak repairs'],
        [7, 'waste.jpg', 'إزالة النفايات', 'Waste Removal', 'خدمات إزالة ونقل النفايات والمخلفات', 'Waste removal and transportation services'],
        [8, 'gardening.jpg', 'حدائق', 'Gardening', 'خدمات تنسيق وصيانة الحدائق والمساحات الخضراء', 'Garden design and maintenance services']
    ];
    
    $update_sql = "UPDATE categories SET image = ?, name_ar = ?, name_en = ?, description_ar = ?, description_en = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    
    foreach ($update_categories as $category) {
        $update_stmt->bind_param("sssssi", $category[1], $category[2], $category[3], $category[4], $category[5], $category[0]);
        if ($update_stmt->execute()) {
            echo "تم تحديث الفئة رقم: " . $category[0] . "<br>";
        } else {
            echo "خطأ في تحديث الفئة رقم: " . $category[0] . " - " . $update_stmt->error . "<br>";
        }
    }
}

// إنشاء جدول مقدمي الخدمة إذا لم يكن موجودًا
$create_providers = "
CREATE TABLE IF NOT EXISTS service_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(100) DEFAULT 'default.jpg',
    category_id INT,
    name_ar VARCHAR(100) NOT NULL DEFAULT '',
    name_en VARCHAR(100) NOT NULL DEFAULT '',
    description_ar TEXT,
    description_en TEXT,
    address VARCHAR(255),
    rating DECIMAL(3,1) DEFAULT 0.0,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($create_providers) === TRUE) {
    echo "تم إنشاء جدول مقدمي الخدمة بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول مقدمي الخدمة: " . $conn->error . "<br>";
}

// التحقق من وجود بيانات في جدول مقدمي الخدمة
$check_providers = "SELECT COUNT(*) as count FROM service_providers";
$result = $conn->query($check_providers);
$row = $result->fetch_assoc();

// إذا كان الجدول فارغًا، قم بإضافة بعض مقدمي الخدمة
if ($row['count'] == 0) {
    // أولاً، إنشاء مستخدمين لمقدمي الخدمة
    $users = [
        ['ahmed_mohamed', '123123', 'ahmed@example.com', '0123456789', 1],
        ['mohamed_ali', '123123', 'mohamed@example.com', '0123456788', 1],
        ['sarah_ahmed', '123123', 'sarah@example.com', '0123456787', 1],
        ['fatima_mohamed', '123123', 'fatima@example.com', '0123456786', 1],
        ['khaled_ibrahim', '123123', 'khaled@example.com', '0123456785', 1],
        ['omar_ahmed', '123123', 'omar@example.com', '0123456784', 1],
        ['hassan_ali', '123123', 'hassan@example.com', '0123456783', 1],
        ['ali_mahmoud', '123123', 'ali@example.com', '0123456782', 1],
        ['mahmoud_sami', '123123', 'mahmoud@example.com', '0123456781', 1],
        ['sami_hassan', '123123', 'sami@example.com', '0123456780', 1]
    ];
    
    $insert_user_sql = "INSERT INTO users (username, password, email, phone, is_provider) VALUES (?, ?, ?, ?, ?)";
    $user_stmt = $conn->prepare($insert_user_sql);
    
    $user_ids = [];
    foreach ($users as $index => $user) {
        $hashed_password = password_hash($user[1], PASSWORD_DEFAULT);
        $user_stmt->bind_param("ssssi", $user[0], $hashed_password, $user[2], $user[3], $user[4]);
        if ($user_stmt->execute()) {
            $user_ids[$index] = $conn->insert_id;
            echo "تم إضافة مستخدم: " . $user[0] . "<br>";
        } else {
            echo "خطأ في إضافة مستخدم: " . $user[0] . " - " . $user_stmt->error . "<br>";
        }
    }
    
    // ثم إضافة مقدمي الخدمة
    $providers = [
        ['أحمد محمد', 'فني كهرباء محترف مع خبرة 10 سنوات في إصلاح جميع المشاكل الكهربائية المنزلية', 'electrician1.jpg', 1, 'أحمد محمد', 'Ahmed Mohamed', 'فني كهرباء محترف مع خبرة 10 سنوات في إصلاح جميع المشاكل الكهربائية المنزلية', 'Professional electrician with 10 years of experience in fixing all home electrical problems'],
        ['محمد علي', 'فني كهرباء متخصص في تركيب وصيانة الأنظمة الكهربائية الحديثة', 'electrician2.jpg', 1, 'محمد علي', 'Mohamed Ali', 'فني كهرباء متخصص في تركيب وصيانة الأنظمة الكهربائية الحديثة', 'Electrician specialized in installing and maintaining modern electrical systems'],
        ['سارة أحمد', 'خدمة تنظيف منازل احترافية مع ضمان الجودة والنظافة التامة', 'cleaner1.jpg', 2, 'سارة أحمد', 'Sarah Ahmed', 'خدمة تنظيف منازل احترافية مع ضمان الجودة والنظافة التامة', 'Professional home cleaning service with quality and cleanliness guarantee'],
        ['فاطمة محمد', 'خدمة تنظيف شاملة للمنازل والمكاتب بأسعار منافسة', 'cleaner2.jpg', 2, 'فاطمة محمد', 'Fatima Mohamed', 'خدمة تنظيف شاملة للمنازل والمكاتب بأسعار منافسة', 'Comprehensive cleaning service for homes and offices at competitive prices'],
        ['خالد إبراهيم', 'دهان محترف مع خبرة في جميع أنواع الدهانات الداخلية والخارجية', 'painter1.jpg', 3, 'خالد إبراهيم', 'Khaled Ibrahim', 'دهان محترف مع خبرة في جميع أنواع الدهانات الداخلية والخارجية', 'Professional painter with experience in all types of interior and exterior paints'],
        ['عمر أحمد', 'فني تكييف وتدفئة مع خبرة في صيانة وتركيب جميع الأنواع', 'hvac1.jpg', 4, 'عمر أحمد', 'Omar Ahmed', 'فني تكييف وتدفئة مع خبرة في صيانة وتركيب جميع الأنواع', 'HVAC technician with experience in maintenance and installation of all types'],
        ['حسن علي', 'نجار محترف متخصص في تصنيع وإصلاح الأثاث الخشبي', 'carpenter1.jpg', 5, 'حسن علي', 'Hassan Ali', 'نجار محترف متخصص في تصنيع وإصلاح الأثاث الخشبي', 'Professional carpenter specialized in manufacturing and repairing wooden furniture'],
        ['علي محمود', 'سباك ذو خبرة في إصلاح جميع مشاكل السباكة وتسربات المياه', 'plumber1.jpg', 6, 'علي محمود', 'Ali Mahmoud', 'سباك ذو خبرة في إصلاح جميع مشاكل السباكة وتسربات المياه', 'Experienced plumber in fixing all plumbing problems and water leaks'],
        ['محمود سامي', 'خدمة إزالة ونقل النفايات بطريقة آمنة وصديقة للبيئة', 'waste1.jpg', 7, 'محمود سامي', 'Mahmoud Sami', 'خدمة إزالة ونقل النفايات بطريقة آمنة وصديقة للبيئة', 'Waste removal and transportation service in a safe and environmentally friendly way'],
        ['سامي حسن', 'مهندس حدائق محترف لتنسيق وصيانة الحدائق والمساحات الخضراء', 'gardener1.jpg', 8, 'سامي حسن', 'Sami Hassan', 'مهندس حدائق محترف لتنسيق وصيانة الحدائق والمساحات الخضراء', 'Professional landscape engineer for garden design and maintenance']
    ];
    
    $insert_provider_sql = "INSERT INTO service_providers (user_id, name, description, image, category_id, name_ar, name_en, description_ar, description_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $provider_stmt = $conn->prepare($insert_provider_sql);
    
    foreach ($providers as $index => $provider) {
        $user_id = $user_ids[$index];
        $provider_stmt->bind_param("isssissss", $user_id, $provider[0], $provider[1], $provider[2], $provider[3], $provider[4], $provider[5], $provider[6], $provider[7]);
        if ($provider_stmt->execute()) {
            echo "تم إضافة مقدم خدمة: " . $provider[0] . "<br>";
        } else {
            echo "خطأ في إضافة مقدم خدمة: " . $provider[0] . " - " . $provider_stmt->error . "<br>";
        }
    }
    
    echo "تم إضافة مقدمي الخدمة بنجاح<br>";
} else {
    // تحديث مقدمي الخدمة الموجودين لإضافة بيانات اللغات
    $update_providers_sql = "UPDATE service_providers SET name_ar = name, name_en = name, description_ar = description, description_en = description WHERE name_ar = '' OR name_en = ''";
    if ($conn->query($update_providers_sql) === TRUE) {
        echo "تم تحديث مقدمي الخدمة بنجاح للدعم متعدد اللغات<br>";
    } else {
        echo "خطأ في تحديث مقدمي الخدمة: " . $conn->error . "<br>";
    }
}

// إضافة حقول اللغة إلى جدول languages
$create_languages_table = "
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    is_rtl BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    is_default BOOLEAN DEFAULT 0
)";

if ($conn->query($create_languages_table) === TRUE) {
    echo "تم إنشاء جدول اللغات بنجاح<br>";
    
    // التحقق من وجود عمود is_default
    $check_default_column = "SHOW COLUMNS FROM languages LIKE 'is_default'";
    $result = $conn->query($check_default_column);
    
    // إذا لم يكن عمود is_default موجودًا، قم بإضافته
    if ($result->num_rows == 0) {
        $add_default_column = "ALTER TABLE languages ADD COLUMN is_default BOOLEAN DEFAULT 0";
        if ($conn->query($add_default_column) === TRUE) {
            echo "تم إضافة عمود is_default إلى جدول اللغات بنجاح<br>";
        } else {
            echo "خطأ في إضافة عمود is_default: " . $conn->error . "<br>";
        }
    }
    
    // حذف جميع اللغات الموجودة
    $delete_languages = "DELETE FROM languages";
    if ($conn->query($delete_languages) === TRUE) {
        echo "تم حذف اللغات السابقة بنجاح<br>";
    }
    
    // إضافة اللغة العربية فقط
    $insert_lang_sql = "INSERT INTO languages (code, name, is_rtl, is_active, is_default) VALUES ('ar', 'العربية', 1, 1, 1)";
    if ($conn->query($insert_lang_sql) === TRUE) {
        echo "تم إضافة اللغة العربية بنجاح<br>";
    } else {
        echo "خطأ في إضافة اللغة العربية: " . $conn->error . "<br>";
    }
} else {
    echo "خطأ في إنشاء جدول اللغات: " . $conn->error . "<br>";
}

// إنشاء مجلد اللغات إذا لم يكن موجودًا
$languages_dir = "languages";
if (!file_exists($languages_dir)) {
    mkdir($languages_dir, 0755, true);
    echo "تم إنشاء مجلد اللغات<br>";
}

// إنشاء ملف اللغة العربية إذا لم يكن موجودًا
$ar_file = "$languages_dir/ar.php";
if (!file_exists($ar_file)) {
    $ar_content = '<?php
$lang = [
    "dir" => "rtl",
    "lang_code" => "ar",
    "site_name" => "بوابة الخدمات المنزلية",
    "home" => "الرئيسية",
    "about" => "من نحن",
    "contact" => "اتصل بنا",
    "login" => "تسجيل الدخول",
    "register" => "إنشاء حساب",
    "logout" => "تسجيل الخروج",
    "profile" => "الملف الشخصي",
    "dashboard" => "لوحة التحكم",
    "search" => "بحث",
    "advanced_search" => "بحث متقدم",
    "welcome" => "مرحباً، ",
    "services" => "خدماتنا",
    "all_services" => "جميع الخدمات",
    "service_providers" => "مقدمو الخدمة",
    "contact_us" => "تواصل معنا",
    "footer_text" => "جميع الحقوق محفوظة © 2023 بوابة الخدمات المنزلية",
    "toggle_theme" => "تبديل المظهر",
    "hero_title" => "خدمات منزلية موثوقة",
    "hero_subtitle" => "نقدم أفضل مزودي الخدمات المنزلية في مكان واحد",
    "our_services" => "خدماتنا",
    "view_details" => "عرض التفاصيل",
    "phone" => "الهاتف:",
    "call_now" => "اتصل الآن",
    "back_to_home" => "العودة للرئيسية",
    "no_providers" => "لا يوجد مقدمي خدمة متاحين حاليا في هذه الفئة.",
    "quick_links" => "روابط سريعة",
    "email_address" => "البريد الإلكتروني:",
    "phone_footer" => "الهاتف:",
    "copyright" => "جميع الحقوق محفوظة.",
    "search_results" => "نتائج البحث",
    "back_to_language_management" => "العودة إلى إدارة اللغات",
    "about_us" => "من نحن",
    "about_intro" => "بوابة الخدمات المنزلية هي منصة متكاملة تهدف إلى ربط أصحاب المنازل بمقدمي خدمات منزلية موثوقين ومحترفين.",
    "our_vision" => "رؤيتنا",
    "vision_text" => "نسعى لأن نكون المنصة الرائدة في مجال الخدمات المنزلية، من خلال توفير تجربة سهلة وموثوقة للعملاء ومقدمي الخدمات على حد سواء.",
    "our_mission" => "مهمتنا",
    "mission_text" => "توفير منصة سهلة الاستخدام تربط بين أصحاب المنازل ومقدمي الخدمات المنزلية الموثوقين، مع ضمان جودة الخدمة وسهولة الوصول إليها.",
    "our_values" => "قيمنا",
    "value_1" => "الجودة",
    "value_1_text" => "نلتزم بتقديم خدمات عالية الجودة تلبي توقعات عملائنا.",
    "value_2" => "الموثوقية",
    "value_2_text" => "نعمل على توفير خدمات موثوقة يمكن الاعتماد عليها في جميع الأوقات.",
    "value_3" => "سهولة الوصول",
    "value_3_text" => "نسعى لجعل الخدمات المنزلية في متناول الجميع بسهولة ويسر.",
    "show_team" => "true",
    "our_team" => "فريقنا",
    "team_member_1_name" => "أحمد محمد",
    "team_member_1_position" => "المدير التنفيذي",
    "team_member_2_name" => "سارة أحمد",
    "team_member_2_position" => "مدير التسويق",
    "team_member_3_name" => "محمد علي",
    "team_member_3_position" => "المدير التقني",
    "about_hero_subtitle" => "تعرف على قصتنا ورؤيتنا وفريقنا المتميز",
    "cta_title" => "هل أنت مستعد للبدء؟",
    "cta_subtitle" => "انضم إلينا اليوم واستفد من خدماتنا المميزة أو كن جزءًا من فريق مقدمي الخدمة لدينا",
    "cta_button_1_text" => "ابحث عن خدمة",
    "cta_button_1_url" => "advanced_search.php",
    "cta_button_2_text" => "انضم كمقدم خدمة",
    "cta_button_2_url" => "register.php?type=provider",
    "my_services" => "خدماتي",
    "service_requests" => "طلبات الخدمة",
    "reviews" => "التقييمات",
    "settings" => "الإعدادات",
    "admin_panel" => "لوحة الإدارة",
    "admin_dashboard" => "لوحة التحكم",
    "content_management" => "إدارة المحتوى",
    "user_management" => "إدارة المستخدمين",
    "manage_categories" => "إدارة الفئات",
    "manage_services" => "إدارة الخدمات",
    "manage_users" => "إدارة المستخدمين",
    "manage_providers" => "إدارة مقدمي الخدمة",
    "notifications" => "الإشعارات",
    "system" => "النظام",
    "manage_languages" => "إدارة اللغات",
    "statistics" => "الإحصائيات",
    "system_maintenance" => "صيانة النظام",
    "version" => "الإصدار",
    "view_site" => "عرض الموقع",
    "guest" => "زائر",
    "provider_image" => "صورة مقدم الخدمة",
    "current_image" => "الصورة الحالية",
    "default_image" => "صورة افتراضية",
    "leave_empty_to_keep_current" => "اترك هذا الحقل فارغًا للاحتفاظ بالصورة الحالية",
    "site_settings" => "إعدادات الموقع",
    "edit_settings" => "تعديل الإعدادات",
    "general_settings" => "الإعدادات العامة",
    "registration_settings" => "إعدادات التسجيل",
    "service_settings" => "إعدادات الخدمات",
    "appearance_settings" => "إعدادات المظهر",
    "site_name_arabic" => "اسم الموقع",
    "site_email" => "البريد الإلكتروني للموقع",
    "site_phone" => "رقم هاتف الموقع",
    "default_language" => "اللغة الافتراضية",
    "maintenance_mode" => "وضع الصيانة",
    "maintenance_mode_desc" => "عند تفعيله، يمكن للمدراء فقط الوصول إلى الموقع.",
    "allow_registration" => "السماح بتسجيل المستخدمين",
    "allow_registration_desc" => "تمكين أو تعطيل تسجيلات المستخدمين الجدد.",
    "email_verification" => "التحقق من البريد الإلكتروني",
    "email_verification_desc" => "طلب التحقق من البريد الإلكتروني للحسابات الجديدة.",
    "admin_approve_providers" => "موافقة المدير على مقدمي الخدمة",
    "admin_approve_providers_desc" => "طلب موافقة المدير على مقدمي الخدمة الجدد.",
    "save_settings" => "حفظ الإعدادات",
    "site_logo" => "شعار الموقع",
    "site_logo_desc" => "يفضل استخدام صورة شفافة بصيغة PNG أو SVG",
    "services_per_page" => "عدد الخدمات في الصفحة",
    "allow_ratings" => "السماح بالتقييمات",
    "allow_ratings_desc" => "السماح للمستخدمين بتقييم مقدمي الخدمة",
    "allow_comments" => "السماح بالتعليقات",
    "allow_comments_desc" => "السماح للمستخدمين بكتابة تعليقات على الخدمات"
];
?>';
    file_put_contents($ar_file, $ar_content);
    echo "تم إنشاء ملف اللغة العربية<br>";
}

// حذف ملف اللغة الإنجليزية إذا كان موجودًا
$en_file = "$languages_dir/en.php";
if (file_exists($en_file)) {
    unlink($en_file);
    echo "تم حذف ملف اللغة الإنجليزية<br>";
}

// إنشاء مجلد أعلام الدول
$flags_dir = "images/flags";
if (!file_exists($flags_dir)) {
    mkdir($flags_dir, 0755, true);
    echo "تم إنشاء مجلد أعلام الدول<br>";
    echo "يرجى إضافة صورة العلم العربي يدويًا (ar.png)<br>";
}

// التحقق مما إذا كانت أعمدة البريد الإلكتروني والتقييم موجودة بالفعل
$check_email_column = "SHOW COLUMNS FROM service_providers LIKE 'email'";
$result = $conn->query($check_email_column);

// إذا لم يكن عمود البريد الإلكتروني موجودًا، قم بإضافته
if ($result->num_rows == 0) {
    $add_email_column = "ALTER TABLE service_providers ADD COLUMN email VARCHAR(100)";
    if ($conn->query($add_email_column) === TRUE) {
        echo "تم إضافة عمود البريد الإلكتروني إلى جدول مقدمي الخدمة بنجاح<br>";
    } else {
        echo "خطأ في إضافة عمود البريد الإلكتروني: " . $conn->error . "<br>";
    }
}

$check_rating_column = "SHOW COLUMNS FROM service_providers LIKE 'rating'";
$result = $conn->query($check_rating_column);

// إذا لم يكن عمود التقييم موجودًا، قم بإضافته
if ($result->num_rows == 0) {
    $add_rating_column = "ALTER TABLE service_providers ADD COLUMN rating DECIMAL(3,1) DEFAULT 0.0";
    if ($conn->query($add_rating_column) === TRUE) {
        echo "تم إضافة عمود التقييم إلى جدول مقدمي الخدمة بنجاح<br>";
    } else {
        echo "خطأ في إضافة عمود التقييم: " . $conn->error . "<br>";
    }
    
    // تحديث التقييمات بقيم افتراضية
    $update_ratings = "UPDATE service_providers SET rating = ROUND(RAND() * 3 + 2, 1)";
    if ($conn->query($update_ratings) === TRUE) {
        echo "تم تحديث التقييمات بقيم افتراضية<br>";
    } else {
        echo "خطأ في تحديث التقييمات: " . $conn->error . "<br>";
    }
}

// التحقق مما إذا كان عمود العنوان موجودًا بالفعل
$check_address_column = "SHOW COLUMNS FROM service_providers LIKE 'address'";
$result = $conn->query($check_address_column);

// إذا لم يكن عمود العنوان موجودًا، قم بإضافته
if ($result->num_rows == 0) {
    $add_address_column = "ALTER TABLE service_providers ADD COLUMN address VARCHAR(255)";
    if ($conn->query($add_address_column) === TRUE) {
        echo "تم إضافة عمود العنوان إلى جدول مقدمي الخدمة بنجاح<br>";
    } else {
        echo "خطأ في إضافة عمود العنوان: " . $conn->error . "<br>";
    }
}

// إضافة جدول الخدمات إذا لم يكن موجودًا
$create_services = "
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category_id INT,
    name_ar VARCHAR(100) NOT NULL DEFAULT '',
    name_en VARCHAR(100) NOT NULL DEFAULT '',
    description_ar TEXT,
    description_en TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
)";

if ($conn->query($create_services) === TRUE) {
    echo "تم إنشاء جدول الخدمات بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول الخدمات: " . $conn->error . "<br>";
}

// إضافة كود للتأكد من وجود صورة افتراضية لمقدمي الخدمة
$providers_dir = __DIR__ . "/images/providers";
if (!file_exists($providers_dir)) {
    if (!mkdir($providers_dir, 0755, true)) {
        die("فشل في إنشاء مجلد صور مقدمي الخدمة. تأكد من صلاحيات الكتابة.");
    }
    echo "تم إنشاء مجلد صور مقدمي الخدمة<br>";
}

// إنشاء صورة افتراضية لمقدمي الخدمة إذا لم تكن موجودة
$anonymous_image = "$providers_dir/anonymous.jpg";
if (!file_exists($anonymous_image)) {
    // يمكنك استبدال هذا برمز Base64 لصورة افتراضية أو نسخ صورة موجودة
    $default_image = file_get_contents("https://via.placeholder.com/150x150.jpg?text=User");
    if ($default_image !== false) {
        file_put_contents($anonymous_image, $default_image);
        echo "تم إنشاء صورة افتراضية لمقدمي الخدمة<br>";
    } else {
        echo "فشل في إنشاء صورة افتراضية لمقدمي الخدمة. يرجى تحميل صورة يدويًا إلى المسار: $anonymous_image<br>";
    }
}

// تحديث جدول الفئات لإزالة الأعمدة الإنجليزية
$update_categories_table = "
ALTER TABLE categories 
DROP COLUMN name_en,
DROP COLUMN description_en,
CHANGE name_ar name VARCHAR(100) NOT NULL,
CHANGE description_ar description TEXT
";

if ($conn->query($update_categories_table) === TRUE) {
    echo "تم تحديث جدول الفئات لإزالة الأعمدة الإنجليزية<br>";
} else {
    // قد يكون الخطأ بسبب عدم وجود الأعمدة بالفعل
    echo "ملاحظة: " . $conn->error . "<br>";
}

// تحديث جدول مقدمي الخدمة لإزالة الأعمدة الإنجليزية
$update_providers_table = "
ALTER TABLE service_providers 
DROP COLUMN name_en,
DROP COLUMN description_en,
CHANGE name_ar name VARCHAR(100) NOT NULL,
CHANGE description_ar description TEXT
";

if ($conn->query($update_providers_table) === TRUE) {
    echo "تم تحديث جدول مقدمي الخدمة لإزالة الأعمدة الإنجليزية<br>";
} else {
    // قد يكون الخطأ بسبب عدم وجود الأعمدة بالفعل
    echo "ملاحظة: " . $conn->error . "<br>";
}

// تحديث جدول الخدمات لإزالة الأعمدة الإنجليزية
$update_services_table = "
ALTER TABLE services 
DROP COLUMN name_en,
DROP COLUMN description_en,
CHANGE name_ar name VARCHAR(100) NOT NULL,
CHANGE description_ar description TEXT
";

if ($conn->query($update_services_table) === TRUE) {
    echo "تم تحديث جدول الخدمات لإزالة الأعمدة الإنجليزية<br>";
} else {
    // قد يكون الخطأ بسبب عدم وجود الأعمدة بالفعل
    echo "ملاحظة: " . $conn->error . "<br>";
}

echo "<br><a href='index.php'>العودة إلى الصفحة الرئيسية</a>";
?>











