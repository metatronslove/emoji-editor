# 💖 PixelFlood: Emoji Piksel Sanatı ve Sosyal Sohbet Platformu

Bu proje, özellikle **YouTube Sohbetleri** ve diğer sosyal platformlarda kullanılmak üzere, düşük karakter maliyetli emojilerle devasa piksel sanat mesajları (Flood Mesajları) oluşturmaya odaklanmış, PHP/MySQL tabanlı bir eğlence ve sosyal platformdur.

## 🚀 Hızlı Kurulum ve Başlatma

Aşağıdaki adımlar, projenin yerel veya uzak bir sunucuda çalışır hale gelmesi için gereklidir.

### 1\. Projeyi İndirme (Clone)

Projeyi indirin ve dizine gidin:

```bash
git clone https://github.com/KULLANICI_ADINIZ/pixelflood-social-art.git
cd pixelflood-social-art
```

### 2\. Bağımlılıkları Kurma (Composer)

Gerekli PHP kütüphanelerini yüklemek için projenin ana dizininde aşağıdaki komutu çalıştırın:

```bash
composer install
```

> **Not:** `vendor` dizini oluşturulduktan sonra projenin boyutu yaklaşık **93.9 MB** olacaktır.

-----

## ⚙️ Yapılandırma ve Veritabanı Ayarları

### 3\. Veritabanını Oluşturma

Sistem için gerekli veritabanı tablolarını oluşturmak üzere size sağlanan tam SQL sorgusu, kök dizininde **`/generate.sql`** dosyası içinde yer almaktadır.

Veritabanı yönetim aracınızı kullanarak yeni bir veritabanı oluşturun ve ardından bu dosyayı içe aktarın veya içeriğini çalıştırın:

```bash
# Örnek MySQL komut satırı kullanımı
mysql -u [db_kullanici] -p [db_adi] < generate.sql
```

### 4\. Bağlantı Dosyasını Düzenleme (`config.php`)

Veritabanı bağlantısının kurulabilmesi için kök dizinde bulunan `config.php` dosyasını kendi sunucu ve veritabanı bilgilerinizle güncelleyin:

```php
// config.php dosyasında düzeltilmesi gereken satırlar

define('DB_NAME', 'SİZİN_DB_ADINIZ');
define('DB_USER', 'SİZİN_DB_KULLANICI_ADINIZ');
define('DB_PASS', 'SİZİN_DB_ŞİFRENİZ');
// ...
```

### 5\. Web Sunucusu Kurulumu (Apache .htaccess)

Uygulamanın temiz URL'lerini (Örn: `/kullaniciadi`, `/admin/dashboard`) kullanabilmesi için Apache sunucularında `mod_rewrite` modülünün etkin olması gerekir.

Yönlendirme kuralları, kök dizininde **`/htaccess`** dosyası içinde tanımlanmıştır. Bu dosyayı sunucunuzun doğru şekilde tanıması için, canlıya alırken veya yerel testlerde **dosya adını `.htaccess` olarak değiştirmeniz** gerekebilir.

```bash
mv htaccess .htaccess
```

-----

## ✨ Temel Platform Özellikleri

| Alan | Açıklama |
| :--- | :--- |
| **Konsept** | Kalp ve Düşük Maliyetli Emojilerle YouTube Sohbetleri için optimize edilmiş "Flood Mesajı" sanatı. |
| **URL Yapısı** | Temiz URL yönlendirmeleri ile modern bir görünüm. (Örn: `domain.com/profil_adi`). |
| **Gizlilik/Sosyal** | Gizli/Herkese Açık Profil modları, Takip İstek Onayı, Karşılıklı Engelleme Sistemi. |
| **Yönetim Paneli** | `admin/dashboard` üzerinden kullanıcı banlama, yorum yasağı ve içerik (çizim/yorum) gizleme yetkileri. |
| **Veritabanı** | Tüm ilişki ve moderasyon alanlarını destekleyen altı tablo (`users`, `drawings`, `follows`, `blocks`, vb.). |
