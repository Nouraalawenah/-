CREATE DATABASE service_portal;
USE service_portal;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    is_admin BOOLEAN DEFAULT 0,
    is_provider BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    description_ar TEXT,
    description_en TEXT
);

CREATE TABLE service_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    description TEXT,
    image VARCHAR(100) DEFAULT 'default.jpg',
    category_id INT,
    name_ar VARCHAR(100) NOT NULL DEFAULT '',
    name_en VARCHAR(100) NOT NULL DEFAULT '',
    description_ar TEXT,
    description_en TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE services (
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
);

CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- إضافة جدول التقييمات
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY (provider_id, user_id)
);

-- تعديل جدول service_providers لإزالة الحقول المكررة
ALTER TABLE service_providers 
DROP COLUMN email,
DROP COLUMN phone;

-- إضافة عمود phone إلى جدول users إذا لم يكن موجودًا بالفعل
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) AFTER email;

-- تحديث جدول service_providers لنقل البيانات المتخصصة فقط
CREATE TABLE IF NOT EXISTS service_providers_new (
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
);

-- نقل البيانات من الجدول القديم إلى الجدول الجديد
INSERT INTO service_providers_new (
    id, user_id, name, description, image, category_id, 
    name_ar, name_en, description_ar, description_en, address, rating
)
SELECT 
    sp.id, 
    sp.user_id, 
    sp.name, 
    sp.description, 
    sp.image, 
    sp.category_id, 
    sp.name_ar, 
    sp.name_en, 
    sp.description_ar, 
    sp.description_en, 
    sp.address, 
    sp.rating
FROM service_providers sp
WHERE sp.user_id IS NOT NULL;

-- تحديث جدول users بأرقام الهواتف من جدول service_providers
UPDATE users u
JOIN service_providers sp ON u.id = sp.user_id
SET u.phone = sp.phone
WHERE sp.phone IS NOT NULL AND sp.phone != '';

-- حذف الجدول القديم وإعادة تسمية الجدول الجديد
DROP TABLE service_providers;
RENAME TABLE service_providers_new TO service_providers;










