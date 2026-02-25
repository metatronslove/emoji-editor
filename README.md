
# Kalp Emoji Piksel Sanatı Editörü ❤️ https://flood.page.gd/

YouTube sohbeti için özel olarak tasarlanmış, kalp emojileriyle piksel sanatı oluşturabileceğiniz interaktif bir web uygulaması.

![Kalp Emoji Editörü](https://img.shields.io/badge/Emoji-Editörü-pink?style=for-the-badge&logo=heart)

## 🎯 Özellikler

### ✨ Temel Özellikler
- **Dinamik Piksel Sınırları ve Filtre Atlatma**: YouTube'un spam filtrelerini aşmak için çeşitli ZW (Sıfır Genişlik) ve deneysel ayırıcılar kullanılır. Seçilen yönteme göre kullanılabilir piksel sayısı otomatik olarak ayarlanır (Max **100**, **69** veya **53**).
- **Zengin Emoji Paleti**: Kalpler, kareler, daireler ve daha fazlası
- **Responsive Tasarım**: Tüm cihazlarda mükemmel çalışır
- **Kolay Kullanım**: Seç ve tıkla - bu kadar basit!

### 🎨 Emoji Kütüphanesi
- ❤️ **Kalpler**: Tüm renklerde kalp emojileri
- ⬛ **Kareler**: 9 farklı renkte kare
- 🔴 **Daireler**: 9 farklı renkte daire
- ⭐ **Şekiller**: Yıldız, elmas, üçgen ve daha fazlası
- 🔥 **Semboller**: Ateş, yıldırım, kar tanesi vb.

### 💾 Kaydet & Paylaş
- **Panoya Kopyala**: Anında YouTube'a yapıştır (Seçilen filtre atlatma karakteri otomatik eklenir)
- **Dosya Kaydet**: Çizimlerinizi .txt olarak kaydedin
- **Dosya Yükle**: Kayıtlı çizimleri geri yükleyin
- **İçe/Dışa Aktar**: Diğer kullanıcılarla paylaşın

## 🚀 Hızlı Başlangıç

### YouTube Sohbeti İçin Kurulum

1. **Sayfayı Açın**: [GitHub Pages linki]

2. **Nickname Uzunluğunu Belirleyin**:
   - YouTube sohbetine **100 siyah kalp** (`🖤🖤🖤...`) yapıştırıp gönderin.
   - Gönderdiğiniz mesajda **nickname'inizin yanında görünen kalp sayısını** sayın.
   - Bu sayıyı "İlk Satır Uzunluğu" olarak ayarlayın ve **Matrisi Güncelle**'ye basın.

3. **Filtre Atlatma Yöntemi Seçin**:
   - Eğer çiziminiz YouTube tarafından filtreleniyor veya görünmüyorsa, **"Filtre Atlatma Yöntemi"** açılır menüsünden bir seçenek belirleyin.
   - **ÖNEMLİ NOT:** Ayırıcı kullanmak çizim alanını kısıtlar. Örneğin; `ZWNJ` veya `ZWSP` seçmek piksel limitini **69'a**, deneysel `Space + Backspace` seçeneği ise **53'e** düşürür.

4. **Çizime Başlayın!** 🎨

### 📝 Adım Adım Kullanım

```bash
1. İlk satır uzunluğunu nickname'inize göre ayarlayın (0-11).
2. Filtre atlatma yöntemini seçin (gerekirse).
3. Paletten istediğiniz rengi/emojiyi seçin.
4. Matris üzerinde tıklayarak çiziminizi oluşturun.
5. "Panoya Kopyala" butonu ile YouTube'a yapıştırın.
6. Mesajınızı gönderin ve sanatınızı sergileyin! ✨
```

## 🛠️ Teknik Özellikler

### Matris Yapısı

  - **Toplam Satır**: 10
  - **Toplam Sütun**: Dinamik (Çoğunlukla 11, 'Space+Backspace' filtresinde 10)
  - **Düzenlenebilir Piksel**: Seçilen filtreye göre dinamik (Max **100**, **69** veya **53**).
  - **Nickname Offset Pikseli**: İlk satır uzunluğuna göre belirlenir (gri alanlar).

### Tarayıcı Desteği

  - ✅ Chrome 60+
  - ✅ Firefox 55+
  - ✅ Safari 12+
  - ✅ Edge 79+

### Teknolojiler

  - **HTML5** - Modern web standartları
  - **CSS3** - Responsive tasarım ve animasyonlar
  - **Vanilla JavaScript** - Hızlı ve hafif

## 📁 Proje Yapısı

```
emoji-editor/
│
├── index.html          # Ana uygulama dosyası (V3.2)
├── README.md           # Bu dosya
└── assets/            # Görseller ve ek dosyalar
    ├── screenshots/
    └── examples/
```

## 🎮 Kontroller

### Butonlar ve İşlevleri

| Buton | İşlev | Açıklama |
|-------|-------|----------|
| **Matrisi Güncelle** | İlk satır uzunluğunu uygular | Çizimi sıfırlar ve filtreye göre matris boyutunu ayarlar. |
| **Panoya Kopyala** | Çizimi kopyalar | Seçilen filtre atlatma karakterini (ayırıcıyı) otomatik olarak ekler. |
| **Panodan İçe Aktar** | Panodan çizim yükler | Paylaşılan çizimleri açar ve ayırıcıyı otomatik algılar. |
| **Çizimi Kaydet** | .txt dosyasına kaydeder | Yedekleme için |
| **Dosya Aç** | Kayıtlı çizimi açar | Önceki çalışmaları yükler |
| **Temizle** | Tüm çizimi siler | Yeni başlangıç |

## 💡 İpuçları ve Püf Noktaları

### 🎨 Sanat İçin Öneriler

  - **Kontrast kullanın**: Açık ve koyu renkleri karıştırın
  - **Simetri deneyin**: Kalp şekilleri için simetrik tasarımlar
  - **Gradyan efektleri**: Benzer tonlarda geçişler yapın

### ⚡ YouTube Optimizasyonu

  - **Hızlı kopyalama**: `Ctrl+C` / `Ctrl+V` için panoya kopyala
  - **Önizleme**: Her değişiklikten sonra YouTube'da test edin
  - **Backup**: Önemli çizimleri mutlaka kaydedin

### 🔧 Geliştirici İpuçları

```javascript
// Özel renk paleti eklemek için
const customPalette = {
    'Özel Emoji': '🎨',
    'Özel Şekil': '🔶'
};
```

## 🤝 Katkıda Bulunma

Katkılarınızı bekliyoruz\! 🎉

1.  Fork edin
2.  Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3.  Commit edin (`git commit -m 'Add amazing feature'`)
4.  Push edin (`git push origin feature/amazing-feature`)
5.  Pull Request oluşturun

### 🐛 Hata Bildirimi

Hata bulursanız [Issue](https://github.com/metatronslove/emoji-editor/issues) açabilirsiniz.

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır - detaylar için [LICENSE](https://www.google.com/search?q=LICENSE) dosyasına bakın.

## 👨‍💻 Geliştirici

**One Fan Club Rocks**

  - GitHub: [@metatronslove](https://github.com/metatronslove)

## 🙏 Teşekkürler

  - Emoji sağlayıcılarına
  - Test eden kullanıcılara
  - Katkıda bulunan herkese

-----

**⭐ Bu projeyi beğendiyseniz yıldız vermeyi unutmayın\!**

[](https://github.com/metatronslove/emoji-editor/stargazers)

*YouTube sohbetinde sanatınızı sergileyin\! 🎨✨*

```

## ☕ Destek Olun / Support

Projemi beğendiyseniz, bana bir kahve ısmarlayarak destek olabilirsiniz!

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://buymeacoffee.com/metatronslove)

Teşekkürler! 🙏
