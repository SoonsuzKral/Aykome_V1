<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * GÖREV 2 — Token-CN güvenlik kilidi ihlali.
 *
 * Cihazdaki E-İmza akıllı kartının sertifika CN'i (certificate_cn), imzayı
 * BAŞLATAN uygulama kullanıcısı ile uyuşmadığında fırlatılır. Controller bu
 * exception'ı yakalayıp 403 döndürür; servis HTTP katmanına dokunmaz.
 */
class EImzaSahibiUyusmazlikException extends RuntimeException {}
