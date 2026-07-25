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

### createDraft() Bug Fix (project_code + application_type)
- **ApplicationService::createDraft()**: `Application::create()` çağrısına `project_code` ve `application_type` alanları eklendi
  - Bug: Bu alanlar daha önce create()'e iletilmiyordu, DB default değerleri (NULL/'basvuru') yazılıyordu
  - Sonuç: Proje kodu kayboluyor, başvuru türü hep "Normal Başvuru" görünüyordu
- **ApplicationsController::edit()**: `surfaceLinesData` mapping'ine `id` ve `address` alanları eklendi, `->toArray()` ile güvenli JSON serialization

## Commit'ler
```
4c88fac feat: edit.blade.php create sayfasi ile esitlendi, controller update() genisletildi
796321f fix: createDraft()'a project_code ve application_type eklendi, edit() surface lines mapping iyilestirildi
```

## Kalan İşler (Bir Sonraki Oturum)
1. 🐛 **Zemin Tipleri edit sayfasında gelmiyor** — Controller mapping iyileştirildi (->toArray(), address/id eklendi), Docker'da test edilmeli
2. Migration'lar Docker'da çalıştırılmalı (OCI_DEFAULT hatası)
3. `GisKatmanAyar` modeli eksik (controller raw DB kullanıyor)
4. ionCube + custom lisans sistemi kurulumu
5. edit.blade.php render testi (Docker'da görsel kontrol — proje kodu, başvuru türü, zemin tipleri doğrulanmalı)
