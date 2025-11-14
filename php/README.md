# Emoji Piksel Sanatı ve Sosyal Sohbet Platformu

Hoş geldiniz! Bu proje, kullanıcıların emoji tabanlı piksel sanatı oluşturmasına, paylaşmasına ve sosyal etkileşimde bulunmasına olanak tanıyan bir PHP tabanlı web uygulamasıdır. YouTube sohbetleri gibi platformlarda "flood mesajları" oluşturmak için ideal bir araçtır. Proje, sanatı eğlenceyle birleştirerek topluluk odaklı bir deneyim sunar.

## Proje Amacı
Bu site, dijital sanatçıların kendi emoji piksel sanatlarını oluşturup paylaşabileceği bir platform sağlar. Kullanıcılar eserlerini sergileyebilir, topluluktan geri bildirim alabilir ve benzer ilgi alanlarına sahip kişilerle bağlantı kurabilir. Ayrıca, sosyal sohbet özellikleri ile etkileşim artırılır.

## Ana Özellikler
- **Kullanıcı Kayıt ve Profil Yönetimi**: Kullanıcılar kayıt olur, profil fotoğrafı ekler (Gravatar veya Google entegrasyonu), gizlilik ayarları yapar.
- **Emoji Piksel Sanat Editörü**: Kalp şeklinde piksel sanatı oluşturma, filtre atlatma yöntemleri (ZWNJ, ZWSP vb.), panoya kopyalama ve dosya kaydetme.
- **Sanat Paylaşımı ve Topluluk Akışı**: Çizimler paylaşılır, beğenilir, yorumlanır. Takip sistemi ile kişiselleştirilmiş akış.
- **Özel Mesajlaşma ve Medya Desteği**: Medya (resim, video, ses) gönderme, galeriden seçme, okunmamış mesaj bildirimi.
- **Yönetim Paneli**: Admin/moderatörler için kullanıcı yönetimi, içerik moderasyonu.
- **Güvenlik ve Performans**: Otomatik sayaç güncellemesi, çevrimiçi kullanıcı takibi, engelleme sistemi.
- **Yeni Eklenenler (v6.5)**: Medya galerisi, takip istekleri, profil görüntüleme sayaçları, sezgisel giriş düzeltmeleri.

## Veritabanı Kullanımı
- **Kullanıcılar**: `users` tablosunda kimlik doğrulama ve profil verileri saklanır.
- **Sanat Eserleri**: `drawings` tablosunda çizimler, kategoriler ve meta veriler tutulur.
- **Yorumlar ve Mesajlar**: `comments` ve `private_messages` tablolarında etkileşimler kaydedilir (medya blob desteğiyle).
- **Takip ve Engelleme**: `follows`, `follow_requests` ve `blocks` tabloları sosyal dinamikleri yönetir.
- **İstatistikler**: `stats` ve `sessions` tabloları ziyaretçi sayaçları ve çevrimiçi kullanıcıları izler.

## Güvenlik Sorunları ve Çözümler
- **Şifre Güvenliği**: Argon2 hashing ile güçlendirildi; zayıf şifreler önlenir.
- **XSS ve SQL Injection**: Tüm girişler PDO prepared statements ile filtrelenir; htmlspecialchars() kullanılır.
- **Oturum Yönetimi**: Güvenli session_start() ve token tabanlı koruma.
- **Medya Güvenliği**: Dosya boyut sınırlaması (2MB), MIME tipi doğrulama.
- **Gizlilik**: Özel profiller ve engelleme sistemi eklendi.

Kod kalitesi: 8/10. Kod modüler hale getirildi (sınıflar: Auth, User, Drawing), ancak daha fazla unit test eklenebilir.

## İyileştirmeler Gereken Alanlar
- **Kod Organizasyonu**: Daha fazla sınıf ve namespace kullanımı.
- **Testler**: PHPUnit ile unit/integration testleri ekleyin; %80 kapsama hedefleyin.
- **Güvenlik**: HTTPS zorunlu kılın, CAPTCHA entegrasyonu (reCAPTCHA).
- **İki Faktörlü Kimlik Doğrulama**: OTP desteği ekleyin.
- **İçerik Moderasyonu**: AI tabanlı (örneğin, Grok entegrasyonu) uygunsuz içerik filtreleme.
- **Gizlilik Politikaları**: GDPR uyumlu veri koruma, kullanıcı silme seçenekleri.
- **Performans**: Caching (Redis) ve lazy loading ekleyin.
- **Yeni Öneri**: AI destekli sanat önerileri (xAI Grok entegrasyonuyla).

## Ortak Şablonlar
- `nav.php`: Üst navigasyon.
- `footer.php`: Alt bilgi.
- `index.php`: Ana sayfa ve editör.
- `profile.php`: Kullanıcı profilleri.
- `messages_modal.php`: Mesaj kutusu modalı.
- `styles.css`: Global stiller.
- `main.js`: JavaScript fonksiyonları.

## Kurulum Talimatları
1. **Sunucu Kurulumu**: PHP 8+ ve MySQL/MariaDB içeren bir web sunucusu (Apache/Nginx) kurun. Composer önerilir.
2. **Kaynak Kodunu İndirin**: Depodan klonlayın veya ZIP indirin.
3. **Veritabanı Oluşturun**: Yeni bir DB oluşturun, `schema.sql` scriptini çalıştırın (tabloları içe aktarın).
4. **Konfigürasyon**: `config.php` dosyasını düzenleyin (DB bilgileri, site URL'si).
5. **Admin Hesabı**: `config/admin.php` ile admin kimlik bilgilerini ayarlayın.
6. **Bağımlılıklar**: Gerekliyse `composer install` ile paketleri yükleyin (örneğin, Google OAuth için).
7. **Test Edin**: Tarayıcıda ana sayfayı açın; kayıt/giriş yapın.
8. **Üretim Modu**: `.htaccess` ile rewrite kuralları etkinleştirin; error_reporting'i kapatın.

Sorun mu yaşadınız? Issue açın veya destek için iletişime geçin. Katkıda bulunun – pull request'ler hoş karşılanır! 🚀
