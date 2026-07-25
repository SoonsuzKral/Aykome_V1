# AYKOME — Oturum Özeti (25 Temmuz 2026)

## Yapılan İşlemler

### edit.blade.php Rewrite + Controller Update Fix
- **edit.blade.php** tamamen yeniden yazıldı (1445 satır, `create.blade.php` ile eşitlendi)
  - Başvuru sahibi alanları: `first_name`, `last_name`, `national_id` (TCKN mask+tooltip)
  - Kazı detayları: `excavation_reason`, `work_type`, `start_date`, `end_date`, `project_code`
  - Harita partial (`_harita.blade.php`) eklendi (mode=edit, drawingEnabled=true)
  - Arama inputu + stil paneli + arazi katmanı toggle + TCKN sorgulama butonu
  - Surface lines CRUD: yüzey tipi seçimi, m² girişi, tablo halinde listeleme, silme
  - Doküman yükleme alanı (mevcut dosyalar listelenir, yenisi eklenir)
  - Validasyon hataları toast ile gösterilir
- **ApplicationsController@edit()**: `isInstitutionUser`, `applicantPrefill`, `institutionPrefill` view'e eklendi; institution ilişkileri expand edildi
- **ApplicationsController@update()**: 7 yeni alan validasyonu (`applicant_first_name`, `applicant_last_name`, `applicant_national_id`, `tc_no`, `identity_no`, `excavation_reason`, `work_type`, `start_date`, `end_date`); national ID normalizasyonu (numeric); tüm alanlar `$application->update()` çağrısına eklendi

### ULTRA.md Güncellemesi
- `edit.blade.php` satır sayısı güncellendi (824→1445)
- deploy.ps1 ve git tag workflow'u "yapılacaklar"da işaretlendi
- 2. oturum eklendi

### Diğer
- `.gitignore`'a `aykome_backup.dmp` ve `index/` eklendi
- Yanlışlıkla eklenen backup dosyaları commit'den kaldırıldı

## Commit'ler
```
4c88fac feat: edit.blade.php create sayfasi ile esitlendi, controller update() genisletildi
```

## Kalan İşler (Bir Sonraki Oturum)
1. Migration'lar Docker'da çalıştırılmalı (OCI_DEFAULT hatası)
2. `GisKatmanAyar` modeli eksik (controller raw DB kullanıyor)
3. ionCube + custom lisans sistemi kurulumu
4. edit.blade.php render testi (Docker'da görsel kontrol)
