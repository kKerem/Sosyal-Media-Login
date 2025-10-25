# WooCommerce - Social Media Login

WordPress ve WooCommerce için sosyal medya ile giriş yapma eklentisi. Google, Facebook, Apple, Twitter, GitHub ve LinkedIn ile giriş yapma desteği.

## 🚀 Özellikler

- **Çoklu Sağlayıcı Desteği**: Google, Facebook, Apple, Twitter, GitHub, LinkedIn
- **WooCommerce Entegrasyonu**: Otomatik müşteri rolü ataması
- **Responsive Tasarım**: Bootstrap uyumlu butonlar
- **Güvenli OAuth**: State parametresi ile güvenlik
- **Otomatik Kullanıcı Oluşturma**: Sosyal medya bilgileri ile kayıt
- **Admin Paneli**: Kolay yapılandırma arayüzü
- **Shortcode Desteği**: `[kkerem_social_login]` ile istediğiniz yerde gösterin

## 📦 Kurulum

1. Eklenti dosyalarını `/wp-content/plugins/kkerem-social-login/` klasörüne yükleyin
2. WordPress admin panelinden eklentiyi aktifleştirin
3. **Sosyal Giriş** menüsünden ayarları yapılandırın

## ⚙️ Yapılandırma

### Google OAuth Kurulumu

1. [Google Cloud Console](https://console.cloud.google.com/) hesabınıza giriş yapın
2. Yeni proje oluşturun veya mevcut projeyi seçin
3. **APIs & Services > Credentials** bölümüne gidin
4. **Create Credentials > OAuth 2.0 Client IDs** seçin
5. **Application type**: Web application
6. **Authorized redirect URIs** ekleyin:
   ```
   https://yourdomain.com/wp-login.php?kkerem-sl-callback=google
   ```
7. Client ID ve Client Secret'i eklenti ayarlarına girin

### Facebook OAuth Kurulumu

1. [Facebook Developers](https://developers.facebook.com/) hesabınıza giriş yapın
2. Yeni uygulama oluşturun
3. **Facebook Login** ürününü ekleyin
4. **Settings > Basic** bölümünde App ID ve App Secret'i alın
5. **Valid OAuth Redirect URIs** ekleyin:
   ```
   https://yourdomain.com/wp-login.php?kkerem-sl-callback=facebook
   ```

### Diğer Sağlayıcılar

Benzer şekilde Apple, Twitter, GitHub ve LinkedIn için OAuth uygulamaları oluşturun ve redirect URI'ları ayarlayın.

## 🎨 Kullanım

### WooCommerce Formlarında Otomatik Gösterim

Eklenti ayarlarından:
- ✅ **WooCommerce giriş formunda göster**
- ✅ **WooCommerce kayıt formunda göster**

### Shortcode ile Manuel Gösterim

```php
[kkerem_social_login]
```

### PHP ile Manuel Gösterim

```php
echo do_shortcode('[kkerem_social_login]');
```

## 🔧 Gelişmiş Ayarlar

### Buton Stilleri

- **Default**: Standart Bootstrap butonları
- **Minimal**: Minimal tasarım
- **Rounded**: Yuvarlatılmış köşeler

### Kullanıcı Rolleri

Eklenti otomatik olarak:
- Yeni kullanıcıları **customer** rolüne atar
- Mevcut kullanıcıları **customer** rolüne çevirir
- WooCommerce müşteri verilerini otomatik doldurur

### Mevcut Kullanıcıları Düzeltme

Admin panelinde **"Mevcut Kullanıcıları Müşteri Rolüne Çevir"** butonu ile sosyal medya ile giriş yapmış kullanıcıların rollerini düzeltebilirsiniz.

## 🛡️ Güvenlik

- **State Parameter**: CSRF saldırılarına karşı koruma
- **Nonce Verification**: WordPress güvenlik standartları
- **Input Sanitization**: Tüm girdiler temizlenir
- **HTTPS Zorunluluğu**: OAuth için güvenli bağlantı

## 🔍 Debug ve Loglama

Eklenti aşağıdaki durumları loglar:
- Kullanıcı oluşturma işlemleri
- Rol atama işlemleri
- OAuth hataları

Logları görmek için WordPress debug modunu aktifleştirin:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📱 Responsive Tasarım

Eklenti Bootstrap 5 ile uyumlu tasarım kullanır:
- Mobil cihazlarda optimize edilmiş butonlar
- Responsive grid sistemi
- Touch-friendly interface

## 🔧 Hook'lar ve Filtreler

### Action Hook'ları

```php
// Sosyal medya ile giriş sonrası
do_action('kkerem_social_login_after_login', $user_id, $provider);

// Kullanıcı oluşturma sonrası
do_action('kkerem_social_login_after_user_creation', $user_id, $user_data);
```

### Filter'lar

```php
// Kullanıcı adı oluşturma
apply_filters('kkerem_social_login_username', $username, $email, $provider);

// Redirect URL'i değiştirme
apply_filters('kkerem_social_login_redirect_url', $redirect_url, $user_id);
```

### Debug Adımları

1. WordPress debug modunu aktifleştirin
2. Error loglarını kontrol edin
3. OAuth sağlayıcı ayarlarını doğrulayın
4. Redirect URI'ları kontrol edin

## 📋 Sistem Gereksinimleri

- **WordPress**: 5.0+
- **WooCommerce**: 3.0+
- **PHP**: 7.4+
- **HTTPS**: OAuth için zorunlu

## 🔄 Güncellemeler

### v0.1.0
- İlk sürüm
- Google OAuth desteği
- WooCommerce entegrasyonu
- Admin paneli
- Responsive tasarım

**Not**: Bu eklenti aktif geliştirme aşamasındadır. Üretim ortamında kullanmadan önce test edin.
