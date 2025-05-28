<?php
// إنشاء مجلد الأعلام إذا لم يكن موجودًا
$flags_dir = __DIR__ . "/images/flags";
if (!file_exists($flags_dir)) {
    if (!mkdir($flags_dir, 0755, true)) {
        die("فشل في إنشاء مجلد الأعلام. تأكد من صلاحيات الكتابة.");
    }
    echo "تم إنشاء مجلد الأعلام<br>";
}

// تحديد مسارات صور الأعلام
$ar_flag = "$flags_dir/ar.png";
$en_flag = "$flags_dir/en.png";

// صورة العلم العربي (الأخضر)
$ar_flag_data = 'iVBORw0KGgoAAAANSUhEUgAAABgAAAAQCAIAAACDRijCAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH5AoTDjcjz8pxTwAAAB1pVFh0Q29tbWVudAAAAAAAQ3JlYXRlZCB3aXRoIEdJTVBkLmUHAAAAhUlEQVQ4y2P8//8/A7UBEwOVwagFJFvAQqRqRgZGRkYGBgYmJiYGBob///8zMjIyMTGxsLAwMzMzMzOzsLAwMzMzMTGxsLCwsrKysrKys7Ozs7Ozs7OzsrKysLCwsLCwsrKys7Ozs7Ozs7OzsrKysLCwsLCwAAAzOBBVHXfWAAAAAElFTkSuQmCC';

// صورة العلم الإنجليزي (الأحمر والأزرق)
$en_flag_data = 'iVBORw0KGgoAAAANSUhEUgAAABgAAAAQCAIAAACDRijCAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH5AoTDjYrGJKQHwAAAB1pVFh0Q29tbWVudAAAAAAAQ3JlYXRlZCB3aXRoIEdJTVBkLmUHAAAAkElEQVQ4y+2Tuw2AMBQE7xCKIFJqoAUaoQxaoAZKoAhCJEQBD38RIQrAJtmRLJ/u9J4sy7KUUu/9GGMIIcYYc87WWgCqtRZCKKWstUopIQQArbUxxnvPOQegtWaMOedCCK01gJRSzpmI7oSIiAiAMWaMEUKw1t62CyGstUQUY+ScpZRKKWvtcwattXOOiJxzAHLOWuv/+MQXbvUYQCd4pdoAAAAASUVORK5CYII=';

// التحقق من وجود صور الأعلام وإنشائها إذا لم تكن موجودة
if (!file_exists($ar_flag)) {
    if (file_put_contents($ar_flag, base64_decode($ar_flag_data)) === false) {
        die("فشل في إنشاء صورة العلم العربي. تأكد من صلاحيات الكتابة.");
    }
    echo "تم إنشاء صورة العلم العربي<br>";
}

if (!file_exists($en_flag)) {
    if (file_put_contents($en_flag, base64_decode($en_flag_data)) === false) {
        die("فشل في إنشاء صورة العلم الإنجليزي. تأكد من صلاحيات الكتابة.");
    }
    echo "تم إنشاء صورة العلم الإنجليزي<br>";
}

// التحقق من أن الصور تم إنشاؤها بنجاح
if (file_exists($ar_flag) && file_exists($en_flag)) {
    echo "تم إعداد صور الأعلام بنجاح!<br>";
    echo "مسار العلم العربي: $ar_flag<br>";
    echo "مسار العلم الإنجليزي: $en_flag<br>";
} else {
    echo "حدثت مشكلة في إنشاء صور الأعلام. تأكد من صلاحيات الكتابة.<br>";
}

// نسخ الصور إلى المجلد العام إذا كان مختلفًا
$public_flags_dir = $_SERVER['DOCUMENT_ROOT'] . "/images/flags";
if ($flags_dir !== $public_flags_dir && !file_exists($public_flags_dir)) {
    if (!mkdir($public_flags_dir, 0755, true)) {
        die("فشل في إنشاء مجلد الأعلام العام. تأكد من صلاحيات الكتابة.");
    }
    
    // نسخ الصور إلى المجلد العام
    if (!copy($ar_flag, "$public_flags_dir/ar.png")) {
        echo "فشل في نسخ العلم العربي إلى المجلد العام.<br>";
    }
    
    if (!copy($en_flag, "$public_flags_dir/en.png")) {
        echo "فشل في نسخ العلم الإنجليزي إلى المجلد العام.<br>";
    }
    
    echo "تم نسخ صور الأعلام إلى المجلد العام: $public_flags_dir<br>";
}
?>
