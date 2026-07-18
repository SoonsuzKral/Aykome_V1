#!/bin/bash
# AYKOME Maps Module Tester
# Usage: bash test_maps.sh [base_url] [cookie_file]
#   base_url:   defaults to http://localhost:8000
#   cookie_file: path to Laravel auth cookie (optional, prompts login if missing)

BASE_URL="${1:-http://localhost:8000}"
COOKIE_JAR="${2:-/tmp/aykome_cookies.txt}"
PASS=0
FAIL=0

green() { echo -e "\033[32m✓ $1\033[0m"; }
red()   { echo -e "\033[31m✗ $1\033[0m"; }
bold()  { echo -e "\033[1m$1\033[0m"; }

# ---------- AUTH ----------
bold "=== AYKOME MAPS TEST SUITE ==="
echo "Base URL: $BASE_URL"
echo ""

# Try to get CSRF token & login if no cookie
if [ ! -f "$COOKIE_JAR" ] || ! grep -q "session" "$COOKIE_JAR" 2>/dev/null; then
    echo "No session cookie found. Attempting login..."
    CSRF=$(curl -s -c "$COOKIE_JAR" "$BASE_URL/login" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
    if [ -z "$CSRF" ]; then
        red "Cannot reach $BASE_URL/login or no CSRF found"
        echo "Usage: $0 [base_url] [laravel_session_cookie_file]"
        exit 1
    fi
    curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
        -X POST "$BASE_URL/login" \
        -d "_token=$CSRF&email=admin@aykome.com&password=password" \
        > /dev/null
    if grep -q "session" "$COOKIE_JAR" 2>/dev/null; then
        green "Login successful"
    else
        red "Login failed — provide a valid session cookie file"
        exit 1
    fi
fi

CSRF_TOKEN=$(curl -s -b "$COOKIE_JAR" "$BASE_URL/maps" | grep -oP 'name="csrf-token" content="\K[^"]+' | head -1)
if [ -z "$CSRF_TOKEN" ]; then
    CSRF_TOKEN=$(curl -s -b "$COOKIE_JAR" "$BASE_URL/maps" | grep -oP 'csrf-token" content="\K[^"]+' | head -1)
fi

# ---------- TEST 1: Maps page loads ----------
bold "Test 1: Maps page loads"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "$BASE_URL/maps")
if [ "$HTTP_CODE" = "200" ]; then
    green "HTTP 200"
    PASS=$((PASS+1))
else
    red "HTTP $HTTP_CODE"
    FAIL=$((FAIL+1))
fi

# ---------- TEST 2: Map tiles / proxy rejects no URL ----------
bold "Test 2: Proxy rejects missing URL"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "$BASE_URL/maps/proxy")
if [ "$HTTP_CODE" = "400" ]; then
    green "HTTP 400 (expected)"
    PASS=$((PASS+1))
else
    red "HTTP $HTTP_CODE (expected 400)"
    FAIL=$((FAIL+1))
fi

# ---------- TEST 3: Proxy rejects invalid domain ----------
bold "Test 3: Proxy rejects invalid domain"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "$BASE_URL/maps/proxy?url=https://evil.com/test")
if [ "$HTTP_CODE" = "403" ]; then
    green "HTTP 403 (expected)"
    PASS=$((PASS+1))
else
    red "HTTP $HTTP_CODE (expected 403)"
    FAIL=$((FAIL+1))
fi

# ---------- TEST 4: GeoJSON endpoint ----------
bold "Test 4: Basvurular GeoJSON"
HTTP_CODE=$(curl -s -o /tmp/aykome_geojson.json -w "%{http_code}" -b "$COOKIE_JAR" "$BASE_URL/maps/basvurular/geojson")
if [ "$HTTP_CODE" = "200" ]; then
    TYPE=$(jq -r '.type' /tmp/aykome_geojson.json 2>/dev/null)
    if [ "$TYPE" = "FeatureCollection" ]; then
        green "Valid FeatureCollection"
        PASS=$((PASS+1))
    else
        red "Response is not FeatureCollection (got: $TYPE)"
        FAIL=$((FAIL+1))
    fi
else
    red "HTTP $HTTP_CODE"
    FAIL=$((FAIL+1))
fi

# ---------- TEST 5: Nokta Kaydet (POST) ----------
bold "Test 5: Nokta kaydet (validation)"
HTTP_CODE=$(curl -s -o /tmp/aykome_nokta.json -w "%{http_code}" \
    -b "$COOKIE_JAR" \
    -X POST "$BASE_URL/maps/nokta-kaydet" \
    -H "Content-Type: application/json" \
    -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
    -d '{"lat":37.123,"lng":38.456,"basvuru_tipi":"kazi_ruhsat"}')
if [ "$HTTP_CODE" = "200" ]; then
    green "Nokta kaydedildi"
    PASS=$((PASS+1))
else
    red "HTTP $HTTP_CODE"
    FAIL=$((FAIL+1))
fi

# ---------- TEST 6: Basvuru Olustur (POST) ----------
bold "Test 6: Basvuru olustur (full flow)"
HTTP_CODE=$(curl -s -o /tmp/aykome_basvuru.json -w "%{http_code}" \
    -b "$COOKIE_JAR" \
    -X POST "$BASE_URL/maps/basvuru-olustur" \
    -H "Content-Type: application/json" \
    -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
    -d '{
        "lat":37.159,"lng":38.792,
        "basvuru_tipi":"kazi_ruhsat",
        "ilce":"Eyyübiye","mahalle":"Bahçelievler",
        "applicant_first_name":"Test","applicant_last_name":"Kullanıcı",
        "applicant_national_id":"12345678901",
        "applicant_phone":"05441234567",
        "excavation_reason":"Test kazısı",
        "work_type":"Kazı",
        "start_date":"2026-07-20","end_date":"2026-08-20",
        "surface_type_id":1,
        "width_m":1.5,"length_m":2.0
    }')
if [ "$HTTP_CODE" = "200" ]; then
    SUCCESS=$(jq -r '.success' /tmp/aykome_basvuru.json 2>/dev/null)
    APP_NO=$(jq -r '.application_no' /tmp/aykome_basvuru.json 2>/dev/null)
    if [ "$SUCCESS" = "true" ] && [ "$APP_NO" != "null" ]; then
        green "Basvuru olusturuldu: $APP_NO"
        PASS=$((PASS+1))
    else
        MSG=$(jq -r '.message' /tmp/aykome_basvuru.json 2>/dev/null)
        red "Basvuru hatasi: $MSG"
        FAIL=$((FAIL+1))
    fi
else
    red "HTTP $HTTP_CODE"
    FAIL=$((FAIL+1))
fi

# ---------- TEST 7: 15m road data ----------
bold "Test 7: 15m alti road data"
HTTP_CODE=$(curl -s -o /tmp/aykome_15alti.json -w "%{http_code}" -b "$COOKIE_JAR" "$BASE_URL/maps/15m/alti")
if [ "$HTTP_CODE" = "200" ]; then
    green "HTTP 200"
    PASS=$((PASS+1))
else
    red "HTTP $HTTP_CODE"
    FAIL=$((FAIL+1))
fi

# ---------- TEST 8: Basvuru sorgula ----------
bold "Test 8: Basvuru sorgula"
HTTP_CODE=$(curl -s -o /tmp/aykome_sorgu.json -w "%{http_code}" \
    -b "$COOKIE_JAR" \
    "$BASE_URL/maps/basvuru-sorgula?q=2026")
if [ "$HTTP_CODE" = "200" ]; then
    green "HTTP 200"
    PASS=$((PASS+1))
else
    red "HTTP $HTTP_CODE"
    FAIL=$((FAIL+1))
fi

# ---------- TEST 9: Unauthenticated redirect ----------
bold "Test 9: Unauthenticated access redirects"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --cookie-jar /dev/null "$BASE_URL/maps")
if [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "301" ]; then
    green "HTTP $HTTP_CODE (redirect to login)"
    PASS=$((PASS+1))
else
    red "HTTP $HTTP_CODE (expected 302)"
    FAIL=$((FAIL+1))
fi

# ---------- SUMMARY ----------
bold ""
bold "========== TEST SUMMARY =========="
bold "Passed: $PASS | Failed: $FAIL"
if [ "$FAIL" -eq 0 ]; then
    green "All tests passed!"
else
    red "$FAIL test(s) failed"
    exit 1
fi
