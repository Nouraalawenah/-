<?php
/**
 * عرض شريط التنقل الفرعي (Breadcrumb)
 * 
 * @param array $breadcrumbs مصفوفة تحتوي على عناصر التنقل
 * كل عنصر يجب أن يحتوي على:
 * - title: عنوان الرابط
 * - url: رابط الصفحة (اختياري إذا كان العنصر نشط)
 * - icon: أيقونة فونت أوسم (اختياري)
 * - active: إذا كان العنصر هو الصفحة الحالية (اختياري)
 */
function display_breadcrumbs($breadcrumbs) {
    if (empty($breadcrumbs)) return;
    
    echo '<nav class="breadcrumb-nav" aria-label="breadcrumb">';
    echo '<ol class="breadcrumb">';
    
    foreach ($breadcrumbs as $index => $item) {
        $is_active = isset($item['active']) && $item['active'];
        $has_icon = isset($item['icon']) && !empty($item['icon']);
        
        echo '<li class="breadcrumb-item' . ($is_active ? ' active' : '') . '"';
        if ($is_active) echo ' aria-current="page"';
        echo '>';
        
        if (!$is_active && isset($item['url'])) {
            echo '<a href="' . $item['url'] . '">';
        }
        
        if ($has_icon) {
            echo '<i class="fas ' . $item['icon'] . '"></i> ';
        }
        
        echo $item['title'];
        
        if (!$is_active && isset($item['url'])) {
            echo '</a>';
        }
        
        echo '</li>';
    }
    
    echo '</ol>';
    echo '</nav>';
}
?>



