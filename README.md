# Gelişmiş Pop-up Uyarı Sistemi (Advanced Pop-up Warning System)

WordPress siteniz için reklam engelleyici (AdBlock) uyarısı, duyurular, çerez bildirimleri, bülten aboneliği ve bağış toplama gibi çeşitli amaçlarla kullanabileceğiniz modern, hızlı ve hafif bir pop-up eklentisi.

![Version](https://img.shields.io/badge/version-4.3-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)
![License](https://img.shields.io/badge/license-GPLv2-green.svg)

## 🌟 Özellikler

*   **6 Farklı Pop-up Türü:**
    *   🛑 **Reklam Engelleyici (AdBlock):** Ziyaretçilerden reklam engelleyicilerini kapatmalarını ister.
    *   📢 **Duyuru:** Önemli haberleri ve güncellemeleri paylaşın.
    *   ⚠️ **Uyarı:** Kritik uyarılar için dikkat çekici tasarım.
    *   🍪 **Çerez (Cookie):** Yasal uyumluluk için çerez bildirimi.
    *   📧 **Bülten (Newsletter):** E-posta aboneliği toplayın.
    *   💗 **Bağış / Destek:** Destek toplamak için özel tasarım.

*   **🎨 Modern Tasarım & Temalar:**
    *   5 Hazır Tema: Koyu (Dark), Açık (Light), Cam (Glassmorphism), Gradient, Neon.
    *   Tamamen özelleştirilebilir renkler (Arka plan, Ana renk, Vurgu rengi, Yazı rengi).
    *   6 Farklı giriş animasyonu (Scale, Fade, Slide Up/Down, Bounce, Flip).

*   **🎯 Gelişmiş Hedefleme:**
    *   **Çıkış Niyeti (Exit Intent):** Kullanıcı sayfayı terk etmek üzereyken pop-up açılır.
    *   **Scroll Tetikleyici:** Sayfanın belirli bir yüzdesine gelindiğinde açılır.
    *   **Sayfa Hedefleme:** Belirli sayfalarda gösterme veya gizleme (URL bazlı).
    *   **Mobil Kontrolü:** Mobilde göster/gizle seçeneği.

*   **⚙️ Kolay Yönetim:**
    *   Gecikme süresi (Delay) ayarlama.
    *   Kapatma butonu için geri sayım (Countdown).
    *   Çerez (Cookie) süresi yönetimi (Tekrar gösterim sıklığı).
    *   Admin panelinden canlıya yakın renk yönetimi.

*   **📊 İstatistikler:**
    *   Görüntülenme ve Kapatma sayılarını takip edin.
    *   Dönüşüm oranını (Kapatma/Görüntülenme) izleyin.

## 🚀 Kurulum

1.  Bu depoyu indirin (`.zip` olarak).
2.  WordPress admin panelinize gidin.
3.  **Eklentiler > Yeni Ekle** menüsüne tıklayın.
4.  **Eklenti Yükle** butonuna basın ve indirdiğiniz `.zip` dosyasını seçin.
5.  **Şimdi Kur** ve ardından **Etkinleştir** butonuna tıklayın.
6.  Admin menüsünde beliren **Pop-up Uyarı** sekmesinden ayarlarınızı yapın.

## 🛠️ Yapılandırma

Eklenti ayarları 5 ana bölümde toplanmıştır:

1.  **Genel Ayarlar:** Pop-up türü, teması, konumu ve animasyonu.
2.  **İçerik Ayarları:** Başlık, açıklama, liste maddeleri ve buton metni.
3.  **Zamanlama:** Gecikme, geri sayım, cookie süresi ve Exit Intent ayarı.
4.  **Görünüm:** Renk paleti, bulanıklık (blur) ve opaklık ayarları.
5.  **Gelişmiş:** Mobil ayarı, istatistikler, hedefleme ve özel CSS.

## 💻 Geliştiriciler İçin

Eklenti `wp_footer` kancasını kullanarak çalışır. CSS değişkenleri (CSS Variables) sayesinde admin panelinden yapılan renk değişiklikleri anlık olarak `style.css` dosyasına enjekte edilir.

### Dosya Yapısı
```
reklam-uyarisi/
├── assets/
│   ├── admin.css      # Admin paneli stilleri
│   ├── script.js      # Frontend mantığı (Exit intent, scroll, cookie)
│   ├── style.css      # Pop-up stilleri
│   └── index.php      # Güvenlik (Silence is golden)
├── reklam-uyarisi.php # Ana eklenti dosyası
├── index.php          # Güvenlik
├── README.md          # Dokümantasyon
└── .gitignore         # Git ayarları
```

## 🤝 Katkıda Bulunma

Hataları raporlamak veya özellik isteğinde bulunmak için lütfen "Issues" bölümünü kullanın. Pull request'ler memnuniyetle karşılanır.

## 📝 Lisans

Bu proje GPLv2 lisansı ile lisanslanmıştır.
