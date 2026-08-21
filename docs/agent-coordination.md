# Agent Coordination API (Claude ↔ Minimax)

> AYKOME projesinde iki LLM (Claude Code + Minimax) aynı `kucuk-isler`
> branch'inde çalışırken birbirlerini koordine etmek için basit bir
> file-based HTTP API.

## Kurulum

```bash
php artisan config:clear     # config cache temizle
php artisan serve --port=8001  # AYKOME'yı başlat
```

API `http://127.0.0.1:8001/api/coordination` adresinde.

## Auth

```
X-Coordination-Key: aykome_coord_2026_dev
```

## Endpoint'ler

| Method   | URL                          | Açıklama                         |
|----------|------------------------------|----------------------------------|
| GET      | `/api/coordination`          | Tüm mesajları listeler           |
| GET      | `/api/coordination?since=5`  | id > 5 olan mesajlar             |
| POST     | `/api/coordination`          | Yeni mesaj gönder                |
| DELETE   | `/api/coordination`          | Tüm mesajları temizle            |

### POST body

```json
{
  "agent": "claude",           // "claude" | "minimax"
  "task": "gorev-a",           // isteğe bağlı, kısa görev kodu
  "message": "GÖREV A tamam, commit 66ceaa7 pushlendi"  // zorunlu
}
```

## Koordinasyon Protokolü

1. **Başladıktan sonra** hemen `"agent": "minimax"` veya `"agent": "claude"`
   ile mesaj gönder: ne yapacağını (örn. "GÖREV B'de fieldValue() inceliği").
2. **Önemli bulduğun** şeyleri (hatalı satır, eksik alan, commit sonuçları)
   hemen mesajla bildir — diğerinin commit'ine çakışmasın.
3. **Yapmadan önce** değişiklik yapacaksan, ilgili dosya/bölge için
   `GET ?since=<last_id>` ile diğerinin yayınladığı mesajları kontrol et.
4. **`kucuk-isler` branch** her iki taraf için ortak — merge öncesi main'de
   ayrıca onay bekler.

## Hızlı Komutlar

```bash
# Mesaj gönder (Claude)
curl -s -X POST http://127.0.0.1:8001/api/coordination \
  -H "X-Coordination-Key: aykome_coord_2026_dev" \
  -H "Content-Type: application/json" \
  -d '{"agent":"claude","task":"gorev-a","message":"padding-top 64px fix yapıldı"}'

# Mesaj gönder (Minimax)
curl -s -X POST http://127.0.0.1:8001/api/coordination \
  -H "X-Coordination-Key: aykome_coord_2026_dev" \
  -H "Content-Type: application/json" \
  -d '{"agent":"minimax","task":"gorev-7","message":"fieldValue() inceliyorum, proje_kodu tokenı boş dönüyor"}'

# Son mesajları oku
curl -s http://127.0.0.1:8001/api/coordination \
  -H "X-Coordination-Key: aykome_coord_2026_dev" | jq '.messages[-5:]'
```
