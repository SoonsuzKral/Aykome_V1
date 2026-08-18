# Süreç ve Onay Rotası Modülü — Kod İncelemesi

> İnceleme tarihi: 2026-08-18
> Reviewer: Claude Code
> Proje: AYKOME HGB Bilişim ULTRA SAAS v6.21

---

## Genel Mimari

Modül 7 ana bileşenden oluşuyor:

| Bileşen | Dosya | Sorumluluk |
|---|---|---|
| **Controller** | `app/Http/Controllers/Admin/ProcessController.php` | CRUD, Blueprint edit, Publish |
| **Engine** | `app/Services/ProcessEngine.php` | Tüm iş mantığı, yetkilendirme, onay ilerletme |
| **Models** | `app/Models/ProcessDefinition.php`, `app/Models/ProcessStep.php` | Veri yapısı, JSON casting |
| **Migrations** | 3 dosya (`2026_07_31_000008`, `2026_07_31_000009`, `2026_08_06_000002`) | Tablolar |
| **Blade Views** | `resources/views/admin/processes/index.blade.php`, `_step_form.blade.php` | Yönetim paneli |
| **Vue (Inertia)** | `resources/js/Pages/Admin/Processes/BlueprintCanvas.vue` | Görsel süreç editörü |
| **Seeder** | `database/seeders/ProcessFlowSeeder.php` | Varsayılan hiyerarşi rolleri + süreç |

**Olumlu noktalar:**
- Mimari temiz ve modüler — `ProcessEngine` tek başına test edilebilir
- JSON casting ile esnek schema (`roles`, `approvable_modules`, `signature_config`, vs.)
- `LEGACY_FIELDS` ile mevcut kolon uyumluluğu korunmuş
- `TOP_ROLES` hiyerarşi kuralı tutarlı uygulanmış
- E-imza / Paraf / Onay ayrımı `action_type` ile netleştirilmiş
- BlueprintCanvas ile görsel süreç editörü mevcut

---

## 🔴 Kritik Bulgular

### 1. `storeStep()`'de ölü kod — başarı mesajı asla gösterilmez

**Dosya:** `app/Http/Controllers/Admin/ProcessController.php:346-350`

```php
return back()->with('new_step', $step);   // ← Inertia POST'u yakalar, JS tarafında okunur

return back()->with('success', ...);        // ← ASLA ulaşılamaz (ölü kod)
```

İlk `return` Inertia POST yanıtı, ikincisi hiçbir zaman çalışmaz. Kullanıcı "Adım eklendi" mesajını göremez, sadece `flash.new_step` üzerinden gelen ID'yi JS yakalar.

**Etki:** Kullanıcı başarılı ekleme hakkında görsel geri bildirim almaz.
**Öncelik:** Orta — JS tarafı çalışıyor ama Blade fallback mesajı ölü.

---

### 2. `LEGACY_FIELDS` mapping dışı `role_key` sessizce atlanır

**Dosya:** `app/Services/ProcessEngine.php:354-358`

```php
$legacy = self::LEGACY_FIELDS[$current->role_key] ?? null;
if ($legacy) {
    $updates[$legacy['by']] = $user->id;
    $updates[$legacy['at']] = $now;
}
```

Özel süreçlerde `role_key` = `'mudur'`, `'sef'`, `'koordinator'` gibi değerler olabilir. Bunlar `LEGACY_FIELDS` map'inde yoktur ve sessizce atlanır. Eğer bir başvuru legacy kolon üzerinden sorgulanıyorsa, o adımın onayı kaydedilmemiş görünür.

**Etki:** Veri bütünlüğü — legacy kolonlar güncellenmez.
**Öncelik:** Yüksek — kritik rapor ama şu an COZUM_11A'da sadece staff/director/vice_mayor kullanılıyor.

---

### 3. `BlueprintCanvas.vue` — `showAddStep` kapatınca `editingStep` siliniyor

**Dosya:** `resources/js/Pages/Admin/Processes/BlueprintCanvas.vue:575-578`

```javascript
function closeAddStep() {
    showAddStep.value = false;
    editingStep.value = null;   // ← mevcut adım düzenleniyor olsa bile siliniyor!
}
```

"İptal" butonu hem yeni adım ekleme formunu hem de **mevcut adım düzenleme modunu** kapatıyor. Kullanıcı yanlışlıkla tüm düzenleme panelini kaybeder.

**Etki:** Kullanıcı deneyimi — düzenleme kaybı.
**Öncelik:** Orta.

---

### 4. Süreç silme özelliği yok

**Dosya:** `ProcessController.php` + `resources/views/admin/processes/index.blade.php`

Mevcut özellikler:
- ✅ Yeni süreç oluşturma (`storeDefinition`)
- ✅ Süreç güncelleme (`updateDefinition`)
- ✅ Varsayılan süreç seçme (`setDefault`)
- ✅ Aktif/pasif toggle (`toggleActive`)
- ❌ **Süreç silme — YOK**

**Etki:** Kullanıcı artık kullanılmayan süreçleri silemez.
**Öncelik:** Kritik — temel CRUD eksik.

---

## 🟡 Orta Risk Bulguları

### 5. `approval_config.mode = 'assigned_only'` — veritabanı constraint yok

**Dosya:** `app/Services/ProcessEngine.php:248`

```php
case 'assigned_only':
    $personnelIds = $step->personnel_ids ?? [];
    return in_array($user->id, $personnelIds, true);
```

`'all'`, `'any'`, `'assigned_only'` değerleri kabul ediliyor ama tablo şemasında ENUM constraint yok. Yanlış değer girilirse sessizce `default: any` case'i çalışır — beklenmedik davranış.

**Etki:** Veri bütünlüğü — geçersiz mode değeri kaydedilebilir.
**Öncelik:** Düşük — şu an sadece Blade form üzerinden değer gönderiliyor.

---

### 6. `BlueprintCanvas.vue` — `autoLayoutSteps()` sonrası konumlar kaydedilmiyor otomatik

**Dosya:** `resources/js/Pages/Admin/Processes/BlueprintCanvas.vue:751-752`

```javascript
nextTick(() => autoLayoutSteps());  // ← konumları değiştirir
// ama KAYDETMEZ — kullanıcı "Kaydet" butonuna basmalı
```

Kullanıcı "📐 Layout" butonuna basar, adımlar düzgün dizilir ama sayfayı yenilerse her şey eski pozisyonlarına döner.

**Etki:** UX — alışkanlık beklenmedik.
**Öncelik:** Düşük.

---

### 7. `canSignStep()` — `stepRequiresSignature()` kontrolü eksik

**Dosya:** `app/Services/ProcessEngine.php:468`

```php
public function stepRequiresSignature(ProcessStep $step): bool
{
    return ($step->action_type ?? 'onay') === 'e_imza';
}

public function canSignStep(ProcessStep $step, User $user): bool
{
    if (($step->action_type ?? 'onay') !== 'e_imza') {  // ← doğrudan kontrol
        return false;
    }
    // ...
}
```

`stepRequiresSignature()` ayrı bir method olarak var ama `canSignStep()` içinde kullanılmıyor. Bu şu an işliyor ama kod tutarsızlığı oluşturuyor.

**Etki:** Kod bakımı — farklı mantıklar farklı yerlerde.
**Öncelik:** Düşük.

---

### 8. `pendingForUser()` — N+1 query potansiyeli

**Dosya:** `app/Services/ProcessEngine.php:273`

```php
foreach ($allProcesses as $proc) {
    $steps = $this->steps($proc);  // Her iterasyonda DB sorgusu
```

`steps()` `process->steps()->where('is_active', true)->get()` çağırıyor. 10 process varsa 10 query. `with('steps')` ile eager load edilebilir.

**Etki:** Performans — çok sayıda süreç varsa.
**Öncelik:** Düşük — şu an az sayıda süreç var.

---

## 🟢 Düşük Risk / İyileştirme Önerileri

### 9. `ProcessEngine` — 769 satır, tek dosyada

Service class çok büyüdü. Alt method grupları ayrı trait'lere taşınabilir:
- `SignatureLogic` — e-imza ile ilgili tüm methodlar
- `PermissionLogic` — yetkilendirme methodları
- `WorkflowLogic` — onay ilerletme, adım bulma

**Öncelik:** Düşük — kod organizasyonu.

---

### 10. `BlueprintCanvas.vue` — 1800+ satır tek dosya

Drag-drop, pan/zoom, connection çizimi, step CRUD, form state, responsive layout, personnel search — hepsi tek component'te. Bileşenler ayrılabilir:
- `BlueprintCanvas.vue` (shell)
- `CanvasNode.vue` (adım kartı)
- `CanvasSidebar.vue` (sol panel)
- `StepPropertyPanel.vue` (sağ panel)

**Öncelik:** Düşük — kod bakımı.

---

### 11. `publish()` — mevcut başvuruların ne olacağı belirsiz

Bir süreç yayınlandığında (`publish()`), o sürece bağlı `process_id`'li başvurular otomatik olarak yeni adımlara mı taşınır? Yoksa sadece yeni başvurular mı etkilenir? Dokümantasyon yok.

**Öncelik:** Düşük — şu an sadece yeni başvuruları etkiliyor gibi görünüyor.

---

### 12. `storeStep()` return değeri tutarsız

```php
// add modunda:
return back()->with('new_step', $step);  // Inertia JSON yanıtı bekliyor

// edit modunda:
return back()->with('success', ...);     // Blade redirect
```

İkisinin davranışı farklı. Add'deki Inertia handling çalışıyor ama tutarsız.

**Öncelik:** Düşük.

---

### 13. `module_permissions` ve `approvable_modules` karışıklığı

Üç kavram var ve arayüzde bazı yerde "görünür" bazı yerde "onaylanabilir" yazıyor:
- `visibility_config` — "bu adımda hangi modüller görünür?"
- `approvable_modules` — "bu adımda hangi modüller onaylanabilir?"
- `module_permissions` — modül başına özel ayarlar (action_type, signer_ids, vs.)

**Öncelik:** Düşük — dokümantasyon iyileştirmesi yeterli.

---

## Özet Tablo

| # | Seviye | Dosya | Satır | Sorun |
|---|---|---|---|---|
| 1 | 🔴 Kritik | `ProcessController.php` | 346-350 | Ölü kod — başarı mesajı asla gösterilmez |
| 2 | 🔴 Kritik | `ProcessEngine.php` | 354-358 | Legacy olmayan role_key'ler sessizce atlanır |
| 3 | 🔴 Kritik | `BlueprintCanvas.vue` | 575-578 | İptal düzenleme panelini kapatıyor |
| 4 | 🔴 Kritik | `ProcessController.php` | — | Süreç silme özelliği yok |
| 5 | 🟡 Orta | `ProcessEngine.php` | 248 | `assigned_only` mode DB constraint yok |
| 6 | 🟡 Orta | `BlueprintCanvas.vue` | 751 | Layout otomatik kaydedilmiyor |
| 7 | 🟡 Orta | `ProcessEngine.php` | 468 | `stepRequiresSignature` kullanılmıyor |
| 8 | 🟡 Orta | `ProcessEngine.php` | 273 | N+1 query potansiyeli |
| 9 | 🟢 Düşük | `ProcessEngine.php` | — | 769 satır — parçalara ayrılabilir |
| 10 | 🟢 Düşük | `BlueprintCanvas.vue` | — | 1800 satır — bileşenlere ayrılabilir |
| 11 | 🟢 Düşük | `ProcessEngine.php` | 243 | Publish sonrası mevcut başvuru davranışı belirsiz |
| 12 | 🟢 Düşük | `ProcessController.php` | 347 | Return değeri tutarsız |
| 13 | 🟢 Düşük | BlueprintCanvas | — | `module_permissions` vs `approvable_modules` karışıklığı |

---

## Yapılacaklar

### Öncelik 1 (Acil)
- [ ] **Süreç silme özelliği ekle** — Issue #4
- [ ] BlueprintCanvas.vue `closeAddStep()` düzelt — Issue #3
- [ ] `storeStep()` ölü kod temizle — Issue #1

### Öncelik 2 (Bu Sprint)
- [ ] `LEGACY_FIELDS` genişlet veya log ekle — Issue #2
- [ ] `approval_config.mode` için ENUM constraint — Issue #5
- [ ] `pendingForUser()` N+1 düzelt — Issue #8

### Öncelik 3 (Sonra)
- [ ] `ProcessEngine` parçalara ayır — Issue #9
- [ ] `BlueprintCanvas.vue` bileşenlere ayır — Issue #10
- [ ] `autoLayoutSteps()` otomatik kaydet — Issue #6
- [ ] Dokümantasyon: publish sonrası başvuru davranışı — Issue #11
