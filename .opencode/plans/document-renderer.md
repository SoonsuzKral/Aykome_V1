# DocumentRenderer Implementation Plan

## Goal
Replace Blade-based PDF views with pdf2htmlEX template-based HTML rendering for 4 document types.

## Files to Create
1. `app/Services/DocumentRenderer.php` — Core service with 4 render methods
2. Routes in `routes/admin.php` — `/pdf/ruhsat/{application}`, `/pdf/metraj/{application}`

## Files to Modify
1. `app/Http/Controllers/Admin/ApplicationsController.php`
   - `downloadPrePermit()` → use `DocumentRenderer::renderPrePermit()`
   - `downloadCoverLetter()` → use `DocumentRenderer::renderCoverLetter()`
   - Add `downloadRuhsat()`, `downloadMetraj()` methods
   - Remove `injectPrintOverlay()` (moved to service)

## Data Flow
```
Template (.html) ─── file_get_contents ──┐
                                          ├── strtr(old → new) ──→ injectOverlay ──→ response
Application data ─── map builder ─────────┘
```

## Template → Data Mapping

### Ön_Kazı_İzni.html (10 replacements)
| Old Text | Marker Concept |
|---|---|
| `78efbceb47d54a00b2cb93f801534b91` | md5 doğrulama kodu |
| `eyyubiye@hs03.kep.tr` | kurum email |
| `Zeynelabidin AKTAŞOĞLU` | imzacı adı |
| `E-18790261-755-555505` | belge no |
| `Kazı İzni Hk.` | konu |
| `Mustafa Kemal KARATAŞ` | imzacı |
| `Belediye Başkan Yardımcısı` | ünvan |
| `09/06/2026` | tarih |
| `13/07/2026` | bitiş tarihi |
| `30.04.2026` | oluşturma tarihi |

### dicle-üst-yazı.html (10 replacements)
| Old Text | Marker Concept |
|---|---|
| `E-50005665001100-100-1176543` | belge no |
| `DİCLE ELEKTRİK DAĞITIM A.Ş.` | kurum |
| `Mehmet SULU` | mühendis adı |
| `FUAT DEĞER` | müdür adı |
| `01D0-6OP0-0HZV` | doğrulama kodu |
| `650 mt2` | alan |
| `30.04.2026` | tarih |
| `AKŞEMSETTİN PROJESİ KAZI ÖN` | konu |
| `0541 762 29 57` | telefon |
| `MEHMET SULU` | büyük mühendis |

### ruhsat.html (~15 replacements)
Dates, ruhsat no, applicant name, institution, amounts (6 fields)

### YerBilgiFormu_Metraj-Formu.html (~6 replacements)
Dates, institution, project code

## Implementation Order
1. Create `DocumentRenderer.php`
2. Update `ApplicationsController.php`
3. Add routes
4. Test with application ID 864
