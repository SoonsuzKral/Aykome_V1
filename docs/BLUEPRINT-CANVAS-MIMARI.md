# BlueprintCanvas — Süreç ve Onay Rotası Görsel Editörü

> **Modül:** Süreç ve Onay Rotası Yönetimi (Hiyerarşi Yönetim Modülü)
> **Teknoloji:** Vue 3 Composition API + Inertia.js + Laravel
> **Tarih:** 2026-08-06

---

## 1. Genel Bakış

BlueprintCanvas, belediye merkez yönetiminin onay silsilelerini görsel olarak tasarlamasına, yönetmesine ve yayınlamasına olanak tanıyan bir görsel iş akışı editörüdür.

### 1.1 Temel Kavramlar

| Kavram | Açıklama |
|--------|----------|
| **ProcessDefinition** | Bir onay rotası (örn: "Kazi Ruhsat Süreci") |
| **ProcessStep** | Rotadaki bir adım (örn: "Büro Personeli Onayı", "Müdür Onayı") |
| **BlueprintCanvas** | Adımların sürüklenebilir kartlar olarak görsel olarak düzenlendiği editör |
| **Canvas Connection** | İki adım arasındaki görsel bağlantı çizgisi (bezier eğrisi) |

### 1.2 Mimari Özet

```
┌─────────────────────────────────────────────────────────┐
│                    BlueprintCanvas.vue                    │
│  ┌──────────┐  ┌─────────────────────────────────────┐  │
│  │ Sidebar  │  │           Canvas (SVG)              │  │
│  │          │  │  ┌─────┐      ┌─────┐      ┌─────┐  │  │
│  │ • Adım 1 │──│──│Step1│──────│Step2│──────│Step3│  │  │
│  │ • Adım 2 │  │  └─────┘      └─────┘      └─────┘  │  │
│  │ • Adım 3 │  │                                      │  │
│  └──────────┘  └─────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
          │                    │
          ▼                    ▼
┌─────────────────┐  ┌─────────────────────────┐
│ ProcessController│  │    ProcessEngine.php    │
│  • storeStep    │  │  • approve()           │
│  • updateStep   │  │  • currentStep()       │
│  • reorderStep  │  │  • userCanApprove()    │
│  • saveCanvas   │  │  • nextStep()          │
└─────────────────┘  └─────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────┐
│         ProcessDefinition (Model)        │
│  └── ProcessStep (Model)                 │
│       • role_key                         │
│       • roles[]                          │
│       • approvable_modules[]             │
│       • personnel_ids[]                  │
│       • canvas_x, canvas_y               │
│       • approval_config                  │
└─────────────────────────────────────────┘
```

---

## 2. Teknoloji Stack'i

### 2.1 Frontend
- **Vue 3** — Composition API (`<script setup>`)
- **Inertia.js** — Laravel ile AJAX üzerinden iletişim
- **TailwindCSS** — Responsive UI
- **SVG** — Canvas üzerinde bezier bağlantı çizgileri

### 2.2 Backend
- **Laravel 10** — PHP Framework
- **ProcessEngine** — Onay mantığı servis sınıfı
- **Eloquent** — Veritabanı ilişkileri

### 2.3 Veritabanı
- **MariaDB/MySQL** — Süreç ve adım verileri

---

## 3. Veritabanı Yapısı

### 3.1 process_definitions Tablosu

```sql
id              — Primary Key
name            — Süreç adı (örn: "Kazi Ruhsat Süreci")
slug            — URL-dostu adres (benzersiz)
description     — Açıklama
is_active       — Aktif/Pasif
is_default      — Varsayılan süreç mi?
version         — Versiyon numarası
status          — draft, published, archived
published_at    — Yayınlanma tarihi
canvas_connections — JSON: [{from_step_id, to_step_id}]
initiator_config  — JSON: Başlatıcı yapılandırması
created_by      — Oluşturan kullanıcı ID
timestamps
```

### 3.2 process_steps Tablosu

```sql
id                    — Primary Key
process_definition_id  — Foreign Key
name                  — Adım adı (örn: "Büro Personeli Onayı")
role_key              — Benzersiz anahtar (örn: "staff", "director")
roles[]               — JSON Array: ["municipality-staff"]
approvable_modules[]   — JSON Array: ["pre_excavation", "metraj"]
personnel_ids[]        — JSON Array: Atanmış kullanıcı ID'leri
visibility_config[]    — JSON Array: Görünür modüller
approval_config        — JSON: {mode: "any|all|assigned_only"}
signature_config       — JSON: E-imza konfigürasyonu (bkz. Bölüm 3.3)
action_type            — String: İşlem tipi ("onay"|"paraf"|"e_imza", varsayılan: "onay")
module_permissions     — JSON: Modül bazlı yetki konfigürasyonu (bkz. Bölüm 3.5)
step_order            — Sıralama numarası
canvas_x              — Canvas üzerinde X koordinatı
canvas_y              — Canvas üzerinde Y koordinatı
is_active             — Aktif/Pasif
timestamps
```

### 3.3 signature_config Yapısı

```json
{
    "enabled": true,
    "signer_ids": [1, 5, 9],
    "signer_roles": ["municipality-director"],
    "pdf_type": "ruhsat",
    "delegation": {
        "allowed": true,
        "delegable_to": ["municipality-director", "municipality-makam"],
        "delegable_ids": [3, 7],
        "requires_approval": false
    }
}
```

| Alan | Tip | Açıklama |
|------|-----|----------|
| `enabled` | boolean | Bu adımda e-imza gerekli mi? |
| `signer_ids` | array | İmza atabilecek kullanıcı ID'leri |
| `signer_roles` | array | İmza atabilecek roller |
| `pdf_type` | string | İmzalanacak belge tipi (ruhsat, metraj, tahakkuk, vb.) |
| `delegation.allowed` | boolean | İmza yaptırılabilir mi? |
| `delegation.delegable_to` | array | Hangi rollere yaptırılabilir |
| `delegation.delegable_ids` | array | Hangi kullanıcılara yaptırılabilir |
| `delegation.requires_approval` | boolean | Devir onay gerektirir mi? |

### 3.4 Action Type (İşlem Türü)

Her adımda üç farklı işlem tipi tanımlanabilir:

| Değer | Açıklama | Kullanım Senaryosu |
|-------|----------|-------------------|
| `onay` | Standart onay işlemi | Personel onay versin → sonraki adıma geç |
| `paraf` | Paraflama (ilk atma) | Büro personeli imza atsın → sadece görsün, hafif onay |
| `e_imza` | PKCS#11 dijital imza | Müdür e-imza atsın → tam dijital onay |

**Action Type Göstergeleri (Canvas Node):**
- `onay` → ✅ (yeşil)
- `paraf` → ✍️ (mavi)
- `e_imza` → 🖊️ (amber)

**İşlem Hiyerarşisi:**
```
onay → Standart onay, iş akışını bir sonraki adıma ilerletir
paraf → İlk paraflama, sadece görüntüleme/imzalama, iş akışını ilerletmez (opsiyonel)
e_imza → PKCS#11 dijital imza, tam hukuki geçerlilik
```

**Örnek Senaryo:**
1. Büro Personeli → **Paraf** (imzalatsın, hafif onay)
2. Birim Şefi → **Onay** (standart onay)
3. Müdür → **E-İmza** (dijital imza)
4. Başkan → **E-İmza** (dijital imza)

### 3.5 module_permissions Yapısı (Modül Bazlı Yetkiler)

Her modül için ayrı ayrı onay türü, imza yetkilisi ve görünürlük tanımlanabilir.

```json
{
    "pre_excavation": {
        "action_type": "onay",
        "approver_roles": ["municipality-staff"],
        "approver_ids": [1, 2],
        "signer_ids": [],
        "signer_roles": [],
        "visible_to_roles": ["municipality-staff", "municipality-director"],
        "visible_to_ids": []
    },
    "metraj": {
        "action_type": "paraf",
        "approver_roles": ["municipality-chief"],
        "approver_ids": [3],
        "signer_ids": [],
        "signer_roles": [],
        "visible_to_roles": ["municipality-chief", "municipality-director"],
        "visible_to_ids": []
    },
    "ruhsat": {
        "action_type": "e_imza",
        "approver_roles": [],
        "approver_ids": [],
        "signer_ids": [5, 9],
        "signer_roles": ["municipality-director"],
        "visible_to_roles": ["municipality-director", "vice_mayor"],
        "visible_to_ids": []
    },
    "tahakkuk": {
        "action_type": "onay",
        "approver_roles": ["municipality-staff"],
        "approver_ids": [4],
        "signer_ids": [],
        "signer_roles": [],
        "visible_to_roles": ["municipality-staff"],
        "visible_to_ids": []
    },
    "makbuz": {
        "action_type": "onay",
        "approver_roles": ["municipality-staff"],
        "approver_ids": [],
        "signer_ids": [],
        "signer_roles": [],
        "visible_to_roles": ["municipality-staff", "municipality-accountant"],
        "visible_to_ids": []
    },
    "taahhutname": {
        "action_type": "e_imza",
        "approver_roles": [],
        "approver_ids": [],
        "signer_ids": [5],
        "signer_roles": ["municipality-director"],
        "visible_to_roles": ["municipality-director"],
        "visible_to_ids": []
    }
}
```

| Alan | Tip | Açıklama |
|------|-----|----------|
| `action_type` | string | Bu modül için işlem tipi (onay/paraf/e_imza), varsayılan: step.action_type |
| `approver_roles` | array | Bu modülü onaylayabilecek roller |
| `approver_ids` | array | Bu modülü onaylayabilecek kullanıcı ID'leri |
| `signer_ids` | array | Bu modül için e-imza atabilecek kullanıcı ID'leri |
| `signer_roles` | array | Bu modül için e-imza atabilecek roller |
| `visible_to_roles` | array | Bu modülü görebilecek roller |
| `visible_to_ids` | array | Bu modülü görebilecek kullanıcı ID'leri |

**Kullanım Senaryosu:**
- Ön Kazı Onay → Büro Personeli onay verir (onay)
- Metraj → Sadece Şef görür, Paraf atar (paraf)
- Ruhsat → Müdür e-imza atar (e_imza)
- Tahakkuk → Memur onay verir (onay)
- Taahhütname → Müdür e-imza atar (e_imza)

---

## 4. Canvas Koordinat Sistemi

### 4.1 Canvas Transform

```javascript
const canvasTransform = reactive({ x: 0, y: 0, scale: 1 });
// x, y: Pan offset (px cinsinden)
// scale: Zoom seviyesi (0.25 - 2.0)
```

### 4.2 Dönüşüm Formülleri

```javascript
// Canvas koordinatları → Ekran koordinatları
screenX = canvasTransform.x + canvasX * canvasTransform.scale
screenY = canvasTransform.y + canvasY * canvasTransform.scale

// Ekran koordinatları → Canvas koordinatları
canvasX = (screenX - canvasTransform.x) / canvasTransform.scale
canvasY = (screenY - canvasTransform.y) / canvasTransform.scale
```

### 4.3 Node Pozisyonu

Her adım kartı (node) CSS ile konumlandırılır:

```javascript
// Node'un sol üst köşesi
left: canvasTransform.x + step.canvas_x * canvasTransform.scale + 'px'
top:  canvasTransform.y + step.canvas_y * canvasTransform.scale + 'px'

// Node genişliği/yüksekliği (scale ile orantılı)
width:  NODE_WIDTH  * canvasTransform.scale + 'px'  // 260px
height: NODE_HEIGHT * canvasTransform.scale + 'px'  // 120px
```

### 4.4 Socket Pozisyonu

Bağlantı noktaları (socket) node kenarlarında bulunur:

```javascript
// Input socket (node'un sol kenarı, ortasında)
socketX = nodeLeft
socketY = nodeMidY

// Output socket (node'un sağ kenarı, ortasında)
socketX = nodeLeft + NODE_WIDTH
socketY = nodeMidY
```

### 4.5 Bezier Bağlantı Eğrisi

```javascript
function bezierPath(x1, y1, x2, y2) {
    const dx = Math.abs(x2 - x1);
    const cpOffset = Math.max(40, dx * 0.5); // Min 40px, dx'in %50'si
    return `M ${x1} ${y1} C ${x1 + cpOffset} ${y1}, ${x2 - cpOffset} ${y2}, ${x2} ${y2}`;
}
```

---

## 5. Bileşen Yapısı (BlueprintCanvas.vue)

### 5.1 State Tanımları

```javascript
// Seçili adım
const selectedStepId = ref(null);
const editingStep = ref(null);        // Düzenlenen adım
const showAddStep = ref(false);        // Yeni adım modalı

// Canvas transform
const canvasTransform = reactive({ x: 0, y: 0, scale: 1 });

// Node sürükleme
const draggingNodeId = ref(null);
const dragOffset = reactive({ x: 0, y: 0 });

// Bağlantı çizme
const isConnecting = ref(false);
const connectingFromId = ref(null);
const connectingLine = reactive({ x1: 0, y1: 0, x2: 0, y2: 0 });

// Process name editing
const isEditingProcessName = ref(false);
const processNameInput = ref('');

// Signature config (E-İmza Yetkisi)
const signatureEnabled = computed({ ... });
const signatureSignerIds = computed({ ... });
const signaturePdfType = computed({ ... });
const pdfTypeOptions = [
    { value: 'ruhsat', label: 'Ruhsat' },
    { value: 'metraj', label: 'Kazı Metraj' },
    { value: 'tahakkuk', label: 'Tahakkuk' },
    { value: 'taahhutname', label: 'Taahhütname' },
    { value: 'makbuz', label: 'Tahsilat Makbuzu' },
    { value: 'pre_permit', label: 'Ön Kazı İzni' },
    { value: 'cover_letter', label: 'Üst Yazı' },
];

// Action Type (İşlem Türü)
const actionTypeOptions = [
    { value: 'onay', label: '✅ Onay', description: 'Standart onay işlemi' },
    { value: 'paraf', label: '✍️ Paraf', description: 'İlk paraflama - hafif onay' },
    { value: 'e_imza', label: '🖊️ E-İmza', description: 'PKCS#11 dijital imza' },
];
const actionType = computed({ get() {...}, set(val){...} });
```

### 5.2 Kritik Fonksiyonlar

| Fonksiyon | Açıklama |
|-----------|----------|
| `getNodeScreenPos(step)` | Node'un SVG içindeki ekran pozisyonu |
| `getSocketScreenPos(step, isOutput)` | Socket'in ekran pozisyonu (sol/sağ kenar) |
| `screenToCanvas(sx, sy)` | Mouse koordinatlarını canvas'a çevir |
| `bezierPath(x1, y1, x2, y2)` | İki nokta arasında bezier eğrisi oluştur |
| `autoLayoutSteps()` | Adımları yatay sıraya diz (100px aralıklı) |
| `reorderStep(step, direction)` | Adım sırasını yukarı/aşağı değiştir |
| `startEditProcessName()` | Process adı düzenleme moduna geç |
| `saveProcessName()` | Process adını sunucuya kaydet |

### 5.3 Template Yapısı

```
BlueprintCanvas.vue
├── <Head> — Sayfa başlığı
├── .flex.h-screen — Ana container
│   ├── <header> — Toolbar
│   │   ├── Sidebar toggle (mobil)
│   │   ├── Process name (editable) ← Tıklanabilir başlık
│   │   ├── Zoom controls
│   │   └── Action buttons (Kaydet, Yayınla)
│   ├── .flex.flex-1 — Body
│   │   ├── <aside> — Sol Sidebar (Adım listesi)
│   │   │   ├── Step cards (sürükle-sırala)
│   │   │   └── ▲/▼ Reorder butonları
│   │   └── Canvas area
│   │       ├── Pan/zoom handlers
│   │       ├── SVG (bağlantı çizgileri)
│   │       └── Node divs (sürüklenebilir kartlar)
│   └── Step edit modal (Inertia form ile)
```

---

## 6. ProcessEngine Servisi

### 6.1 Temel Metodlar

```php
class ProcessEngine
{
    // Süreç ve adım erişimi
    public function activeProcess(): ?ProcessDefinition
    public function processFor(Application $application): ?ProcessDefinition
    public function steps(?ProcessDefinition $process = null): Collection
    public function currentStep(Application $application): ?ProcessStep
    public function nextStep(Application $application): ?ProcessStep

    // Yetki kontrolü
    public function roleCanApproveStep(ProcessStep $step, User $user): bool
    public function userCanApprove(Application $application, User $user): bool
    public function canApproveStep(ProcessStep $step, User $user): bool
    public function hasAnyStepRole(User $user): bool

    // Onay işlemleri
    public function approve(Application $application, User $user): array
    public function pendingForUser(User $user): Collection

    // Action Type (İşlem Türü)
    public function getStepActionType(ProcessStep $step): string
    public function canPerformStepAction(ProcessStep $step, User $user): bool

    // Paraf (paraf action_type)
    public function stepRequiresParaf(ProcessStep $step): bool
    public function canParafStep(ProcessStep $step, User $user): bool

    // E-İmza Yetkisi (signature_config / e_imza action_type)
    public function stepRequiresSignature(ProcessStep $step): bool
    public function canSignStep(ProcessStep $step, User $user): bool
    public function getSignaturePdfType(ProcessStep $step): ?string

    // Modül Bazlı Yetkiler (module_permissions)
    public function getModuleActionType(ProcessStep $step, string $module): string
    public function canApproveModule(ProcessStep $step, User $user, string $module): bool
    public function canSignModule(ProcessStep $step, User $user, string $module): bool
    public function canParafModule(ProcessStep $step, User $user, string $module): bool
    public function canViewModule(ProcessStep $step, User $user, string $module): bool
    public function visibleModulesForUserOnStep(ProcessStep $step, User $user): array

    // Konfigürasyon
    public function moduleOptions(): array
    public function roleOptions(): array
}
```

### 6.2 Approval Config Modları

```javascript
// any — Rolü olan VEYA personnel_ids'de olan onaylayabilir
{ mode: 'any' }

// all — Rolü olan VE (personnel_ids'de olan VEYA boşsa)
{ mode: 'all' }

// assigned_only — Sadece personnel_ids'dekiler onaylayabilir
{ mode: 'assigned_only' }
```

### 6.3 Paraf Yetkisi (paraf action_type)

Paraflama, bir belgenin veya başvurunun ilk aşamada görüldüğünü/okunduğunu ve onaylandığını gösteren hafif bir onay işlemidir. Standart onaydan farklı olarak, paraffin genellikle iş akışını doğrudan ilerletmez — sadece ilgili kişinin görüşünü/onaladığını kaydeder.

**Kullanım Senaryosu:**
- "Büro personeli e-imzası var ama o e-imza atmayacak, parafla yapacak"
- Paraf, hafif onay olarak düşünülebilir — bir sonraki adıma geçiş için tam onay gerekmez

**Yetki Kontrolü:**
- `stepRequiresParaf()` — Adımda paraffin gerekli mi?
- `canParafStep()` — Kullanıcı bu adımı paraflayabilir mi?

**Yetki Hiyerarşisi:**
1. `super-admin` ve `municipality-admin` her adımı paraflayabilir
2. `personnel_ids` içinde tanımlı kullanıcılar paraflayabilir
3. `roles` içinde tanımlı rollere sahip kullanıcılar paraflayabilir

### 6.4 E-İmza Yetkisi (signature_config)

E-İmza Yetkisi, adımlarda PKCS#11 dijital imza gereksinimini tanımlar. Bu, mevcut onay (approval) yapısından bağımsızdır.

**Kullanım Senaryosu:**
- Personel onay versin → Birim Şefi sadece görsün → Müdür e-imza atsın → Başkan e-imza atsın

**Yetki Hiyerarşisi:**
1. `super-admin` ve `municipality-admin` her adımı imzalayabilir
2. `signer_ids` içinde tanımlı kullanıcılar imzalayabilir
3. `signer_roles` içinde tanımlı rollere sahip kullanıcılar imzalayabilir

**PDF Tipleri:**
- `ruhsat` — Ruhsat
- `metraj` — Kazı Metraj
- `tahakkuk` — Tahakkuk
- `taahhutname` — Taahhütname
- `makbuz` — Tahsilat Makbuzu
- `pre_permit` — Ön Kazı İzni
- `cover_letter` — Üst Yazı

**Delegation (İmzalatma Yetkisi):**
Bir kullanıcı, imza yetkisini başka bir kullanıcıya/role'e devredebilir:
- `delegation.allowed` — Devir aktif mi?
- `delegation.delegable_to` — Hangi rollere devredilebilir?
- `delegation.delegable_ids` — Hangi kullanıcılara devredilebilir?
- `delegation.requires_approval` — Devir onay gerektirir mi (admin onayı)?

---

## 7. API Route'ları

| Route | Method | Controller | Açıklama |
|-------|--------|------------|----------|
| `/admin/processes` | GET | index | Süreç listesi |
| `/admin/processes` | POST | storeDefinition | Yeni süreç oluştur |
| `/admin/processes/{process}` | PUT/PATCH | updateDefinition | Süreç güncelle |
| `/admin/processes/{process}/toggle-active` | POST | toggleActive | Aktif/Pasif toggle |
| `/admin/processes/{process}/set-default` | POST | setDefault | Varsayılan yap |
| `/admin/processes/{process}/blueprint` | GET | blueprint | Blueprint editör |
| `/admin/processes/{process}/save-canvas` | POST | saveCanvas | Canvas pozisyonlarını kaydet |
| `/admin/processes/{process}/publish` | POST | publish | Yayınla |
| `/admin/processes/steps` | POST | storeStep | Yeni adım ekle |
| `/admin/processes/steps/{step}` | PUT | updateStep | Adım güncelle |
| `/admin/processes/steps/{step}` | DELETE | destroyStep | Adım sil |
| `/admin/processes/steps/{step}/reorder/{direction}` | POST | reorderStep | Sıralama değiştir |

---

## 8. Blueprint Geliştirme Detayları

### 8.1 Yeni Özellik Ekleme Adımları

1. **Controller'a metod ekle** (`ProcessController.php`)
2. **Route ekle** (`routes/admin.php`)
3. **Vue state/fonksiyon ekle** (`BlueprintCanvas.vue`)
4. **Template güncelle**

### 8.2 Yeni Adım Tipi Ekleme

```php
// ProcessEngine.php - moduleOptions() veya roleOptions() genişlet
public function moduleOptions(): array
{
    return [
        'pre_excavation' => 'Ön Kazı Onayı',
        'metraj' => 'Metraj Onayı',
        'ruhsat' => 'Ruhsat İzni',
        // YENİ: 'yeni_modul' => 'Yeni Modül Adı',
    ];
}
```

### 8.3 Canvas'a Yeni UI Elemanı Ekleme

```javascript
// 1. State ekle
const myNewFeature = ref(null);

// 2. Template'de kullan
<div :style="{
    left: canvasTransform.x + myNewFeature.x * canvasTransform.scale + 'px',
    top:  canvasTransform.y + myNewFeature.y * canvasTransform.scale + 'px',
}">
    Content
</div>
```

---

## 9. Gelecek Modüller İçin Mimari Şablon

Bu mimari, benzer görsel editör ihtiyaçları için yeniden kullanılabilir.

### 9.1 Yeni Görsel Editör Oluşturma

```
1. models/ altında ilgili Model oluştur
2. migration/ altında tablo oluştur
3. app/Http/Controllers/ altında Controller oluştur
4. app/Services/ altında Logic Service oluştur
5. routes/ altında route'ları tanımla
6. resources/js/Pages/ altında Vue sayfası oluştur
7. Canvas component'ini BlueprintCanvas'dan kopyala, ihtiyaca göre özelleştir
```

### 9.2 Çizim/Kayıt Mimarisi

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Vue Canvas  │────▶│  Controller   │────▶│    Model     │
│  (drag/drop) │     │  (validate)  │     │  (Eloquent)  │
└──────────────┘     └──────────────┘     └──────────────┘
       │                    │
       │                    ▼
       │             ┌──────────────┐
       └────────────▶│   Service    │
                     │  (business   │
                     │   logic)     │
                     └──────────────┘
```

### 9.3 Veri Akışı

```
User Interaction (Vue)
       │
       ▼
  State Güncelleme (ref/reactive)
       │
       ▼
  Inertia Request (router.post/put)
       │
       ▼
  Controller (validasyon, işlem)
       │
       ▼
  Service (işlem mantığı)
       │
       ▼
  Model (veritabanı)
       │
       ▼
  Redirect/back() with flash
       │
       ▼
  Inertia: Sayfa yeniden render
```

---

## 10. Önemli Notlar ve Best Practices

### 10.1 Canvas Koordinatları

- **Asla CSS `transform: scale()` kullanma** — DOM `getBoundingClientRect()` bunu hesaba katmaz
- **Tüm pozisyonları `left`/`top` ile CSS'te yap** — JavaScript ile tutarlı olur
- **Socket pozisyonlarını JavaScript'ten hesapla** — DOM measurement yerine matematiksel hesaplama

### 10.2 Vue 3 + Inertia

- **Props'ı doğrudan mutate etme** — `props.process.name = x` çalışmaz
- **`toRefs()` ile props destructuring** — Reaktif referans oluştur
- **`preserveScroll: true`** — Kayıt sonrası scroll pozisyonunu koru

### 10.3 Laravel

- **Redirect kullan** — `back()->with()` ile flash mesaj
- **ValidationException** — `throw ValidationException::withMessages()`
- **Model ilişkileri** — `steps()` gibi relation method'ları kullan

---

## 11. Dosya Listesi

```
app/
├── Http/Controllers/Admin/
│   └── ProcessController.php          # Süreç controller'ı
├── Models/
│   ├── ProcessDefinition.php           # Süreç modeli
│   └── ProcessStep.php                # Adım modeli
└── Services/
    └── ProcessEngine.php              # Onay motoru

resources/js/
└── Pages/Admin/Processes/
    └── BlueprintCanvas.vue            # Görsel editör

routes/
└── admin.php                          # Route tanımları

database/migrations/
├── 2026_08_05_000001_add_blueprint_fields_to_process_steps_table.php
├── 2026_08_05_000002_add_version_fields_to_process_definitions_table.php
├── 2026_08_06_000001_add_signature_config_to_process_steps_table.php
├── 2026_08_06_000002_add_action_type_to_process_steps_table.php
└── 2026_08_06_000003_add_module_permissions_to_process_steps_table.php
```

---

*AYKOME HGB Bilişim — BlueprintCanvas Görsel İş Akışı Editörü*
*v6.21 | 2026-08-06*
