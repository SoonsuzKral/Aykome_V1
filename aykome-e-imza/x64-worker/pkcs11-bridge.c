#include <dlfcn.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include "pkcs11.h"

static CK_FUNCTION_LIST_PTR fn = NULL;
static void* handle = NULL;
static char p11_path[512] = {0};

static int bridge_init() {
    if (fn) return 1;
    if (!p11_path[0]) strcpy(p11_path, "/usr/local/lib/libakisp11.dylib");
    handle = dlopen(p11_path, RTLD_LAZY | RTLD_LOCAL);
    if (!handle) { fprintf(stderr, "ERR dlopen: %s\n", dlerror()); return 0; }
    CK_C_GetFunctionList p = (CK_C_GetFunctionList)dlsym(handle, "C_GetFunctionList");
    if (!p) { fprintf(stderr, "ERR dlsym: %s\n", dlerror()); return 0; }
    CK_RV rv = p(&fn);
    if (rv != CKR_OK) { fprintf(stderr, "ERR GetFunctionList: %lu\n", rv); return 0; }
    rv = fn->C_Initialize(NULL);
    if (rv != CKR_OK) { fprintf(stderr, "ERR Initialize: %lu\n", rv); return 0; }
    return 1;
}

static void bridge_close() {
    if (fn) { fn->C_Finalize(NULL); fn = NULL; }
    if (handle) { dlclose(handle); handle = NULL; }
}

static void print_token_info(CK_SLOT_ID slot) {
    CK_TOKEN_INFO info;
    CK_RV rv = fn->C_GetTokenInfo(slot, &info);
    if (rv != CKR_OK) { printf("TOKEN_ERR\n"); return; }
    char label[33], man[33], model[33], serial[33];
    memcpy(label, info.label, 32); label[32] = 0;
    memcpy(man, info.manufacturerID, 32); man[32] = 0;
    memcpy(model, info.model, 32); model[32] = 0;
    memcpy(serial, info.serialNumber, 32); serial[32] = 0;
    for (int j = 31; j >= 0 && label[j] == ' '; j--) label[j] = 0;
    for (int j = 31; j >= 0 && man[j] == ' '; j--) man[j] = 0;
    for (int j = 31; j >= 0 && model[j] == ' '; j--) model[j] = 0;
    for (int j = 31; j >= 0 && serial[j] == ' '; j--) serial[j] = 0;
    printf("TOKEN label=\"%s\" man=\"%s\" model=\"%s\" serial=\"%s\" flags=%lu\n",
           label, man, model, serial, info.flags);
}

static void cmd_list() {
    if (!bridge_init()) return;
    CK_ULONG count = 0;
    CK_RV rv = fn->C_GetSlotList(CK_TRUE, NULL, &count);
    if (rv != CKR_OK) { printf("SLOT_ERR %lu\n", rv); bridge_close(); return; }
    printf("SLOTS %lu\n", count);
    if (count > 0) {
        CK_SLOT_ID* slots = malloc(sizeof(CK_SLOT_ID) * count);
        fn->C_GetSlotList(CK_TRUE, slots, &count);
        for (CK_ULONG i = 0; i < count; i++) {
            printf("SLOT %lu ", (unsigned long)slots[i]);
            print_token_info(slots[i]);
        }
        free(slots);
    }
    bridge_close();
}

static void cmd_cert(const char* pin_hex) {
    if (!bridge_init()) return;
    CK_ULONG count = 0;
    CK_RV rv = fn->C_GetSlotList(CK_TRUE, NULL, &count);
    if (rv != CKR_OK || count == 0) { printf("ERR No slot\n"); bridge_close(); return; }
    CK_SLOT_ID* slots = malloc(sizeof(CK_SLOT_ID) * count);
    fn->C_GetSlotList(CK_TRUE, slots, &count);
    CK_SESSION_HANDLE session;
    rv = fn->C_OpenSession(slots[0], CKF_SERIAL_SESSION | CKF_RW_SESSION, NULL, NULL, &session);
    if (rv != CKR_OK) { printf("ERR OpenSession: %lu\n", rv); free(slots); bridge_close(); return; }
    size_t pin_len = strlen(pin_hex) / 2;
    unsigned char* pin = malloc(pin_len + 1);
    for (size_t i = 0; i < pin_len; i++) {
        sscanf(pin_hex + 2*i, "%2hhx", &pin[i]);
    }
    pin[pin_len] = 0;
    rv = fn->C_Login(session, CKU_USER, pin, pin_len);
    free(pin);
    if (rv != CKR_OK) { printf("ERR Login: %lu\n", rv); fn->C_CloseSession(session); free(slots); bridge_close(); return; }

    // Find certificate objects
    CK_OBJECT_CLASS cls = CKO_CERTIFICATE;
    CK_ATTRIBUTE tmpl[] = {
        { CKA_CLASS, &cls, sizeof(cls) },
        { CKA_CERTIFICATE_TYPE, NULL, 0 },
    };
    CK_ULONG tmpl_cnt = 1;
    rv = fn->C_FindObjectsInit(session, tmpl, tmpl_cnt);
    if (rv != CKR_OK) { printf("ERR FindInit: %lu\n", rv); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    CK_OBJECT_HANDLE obj;
    CK_ULONG obj_cnt = 0;
    rv = fn->C_FindObjects(session, &obj, 1, &obj_cnt);
    if (rv != CKR_OK || obj_cnt == 0) { printf("ERR No cert obj\n"); fn->C_FindObjectsFinal(session); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    // Get DER
    CK_ATTRIBUTE get_der[] = { { CKA_VALUE, NULL, 0 } };
    rv = fn->C_GetAttributeValue(session, obj, get_der, 1);
    if (rv != CKR_OK) { printf("ERR GetAttrLen: %lu\n", rv); fn->C_FindObjectsFinal(session); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    unsigned char* der = malloc(get_der[0].ulValueLen);
    get_der[0].pValue = der;
    rv = fn->C_GetAttributeValue(session, obj, get_der, 1);
    if (rv != CKR_OK) { printf("ERR GetAttr: %lu\n", rv); free(der); fn->C_FindObjectsFinal(session); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    fn->C_FindObjectsFinal(session);
    printf("CERT_DER ");
    for (CK_ULONG i = 0; i < get_der[0].ulValueLen; i++) printf("%02x", der[i]);
    printf("\nCERT_OK\n");
    free(der);
    fn->C_Logout(session);
    fn->C_CloseSession(session);
    free(slots);
    bridge_close();
}

// ECDSA raw (r||s) to DER SEQUENCE { INTEGER r, INTEGER s }
static unsigned char* ecdsa_raw_to_der(const unsigned char* raw, CK_ULONG raw_len, CK_ULONG* out_len) {
    CK_ULONG half = raw_len / 2;
    const unsigned char* r = raw;
    const unsigned char* s = raw + half;
    int r_lead = (r[0] & 0x80) ? 1 : 0;
    int s_lead = (s[0] & 0x80) ? 1 : 0;
    CK_ULONG r_len = half + r_lead;
    CK_ULONG s_len = half + s_lead;
    // SEQUENCE + length + INTEGER(r) + length + r_data + INTEGER(s) + length + s_data
    CK_ULONG der_len = 2 + 2 + r_len + 2 + s_len;
    unsigned char* der = malloc(der_len);
    CK_ULONG pos = 0;
    der[pos++] = 0x30; // SEQUENCE
    if (der_len - 2 > 127) der[pos++] = 0x81;
    der[pos++] = der_len - 2; // length
    der[pos++] = 0x02; // INTEGER
    der[pos++] = r_len;
    if (r_lead) der[pos++] = 0x00;
    memcpy(der + pos, r, half); pos += half;
    der[pos++] = 0x02; // INTEGER
    der[pos++] = s_len;
    if (s_lead) der[pos++] = 0x00;
    memcpy(der + pos, s, half); pos += half;
    *out_len = pos;
    return der;
}

static void cmd_sign(const char* pin_hex, const char* data_hex) {
    if (!bridge_init()) return;
    CK_ULONG count = 0;
    CK_RV rv = fn->C_GetSlotList(CK_TRUE, NULL, &count);
    if (rv != CKR_OK || count == 0) { printf("ERR No slot\n"); bridge_close(); return; }
    CK_SLOT_ID* slots = malloc(sizeof(CK_SLOT_ID) * count);
    fn->C_GetSlotList(CK_TRUE, slots, &count);
    CK_SESSION_HANDLE session;
    rv = fn->C_OpenSession(slots[0], CKF_SERIAL_SESSION | CKF_RW_SESSION, NULL, NULL, &session);
    if (rv != CKR_OK) { printf("ERR OpenSession: %lu\n", rv); free(slots); bridge_close(); return; }
    size_t pin_len = strlen(pin_hex) / 2;
    unsigned char* pin = malloc(pin_len + 1);
    for (size_t i = 0; i < pin_len; i++) sscanf(pin_hex + 2*i, "%2hhx", &pin[i]);
    pin[pin_len] = 0;
    rv = fn->C_Login(session, CKU_USER, pin, pin_len);
    free(pin);
    if (rv != CKR_OK) { printf("ERR Login: %lu\n", rv); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    // Find private key
    CK_OBJECT_CLASS cls = CKO_PRIVATE_KEY;
    CK_ATTRIBUTE key_tmpl[] = { { CKA_CLASS, &cls, sizeof(cls) } };
    rv = fn->C_FindObjectsInit(session, key_tmpl, 1);
    if (rv != CKR_OK) { printf("ERR FindKeyInit: %lu\n", rv); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    CK_OBJECT_HANDLE key;
    CK_ULONG key_cnt = 0;
    rv = fn->C_FindObjects(session, &key, 1, &key_cnt);
    if (rv != CKR_OK || key_cnt == 0) { printf("ERR No key\n"); fn->C_FindObjectsFinal(session); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    fn->C_FindObjectsFinal(session);
    // CÖZÜM_04: Anahtar tipi (RSA vs EC) CKA_KEY_TYPE ile ayrilir — eskiden her
    // zaman CKM_ECDSA kullaniliyordu; RSA kartlarda imza garantili bozuktu.
    CK_ULONG key_type = CKK_EC;
    {
        CK_ATTRIBUTE kt[] = { { CKA_KEY_TYPE, NULL, 0 } };
        rv = fn->C_GetAttributeValue(session, key, kt, 1);
        if (rv == CKR_OK && kt[0].ulValueLen == sizeof(CK_ULONG)) {
            CK_ULONG* v = malloc(sizeof(CK_ULONG));
            kt[0].pValue = v;
            if (fn->C_GetAttributeValue(session, key, kt, 1) == CKR_OK) key_type = *v;
            free(v);
        }
    }
    // ECDSA mechanism — RSA icin CKM_RSA_PKCS (JS tarafinda DigestInfo ASN.1 verilir)
    CK_MECHANISM mech = { (key_type == CKK_RSA) ? CKM_RSA_PKCS : CKM_ECDSA, NULL, 0 };
    rv = fn->C_SignInit(session, &mech, key);
    if (rv != CKR_OK) { printf("ERR SignInit: %lu\n", rv); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    size_t data_len = strlen(data_hex) / 2;
    unsigned char* data = malloc(data_len);
    for (size_t i = 0; i < data_len; i++) sscanf(data_hex + 2*i, "%2hhx", &data[i]);
    CK_ULONG sig_len = 0;
    rv = fn->C_Sign(session, data, data_len, NULL, &sig_len);
    if (rv != CKR_OK) { printf("ERR SignLen: %lu\n", rv); free(data); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    unsigned char* sig = malloc(sig_len);
    rv = fn->C_Sign(session, data, data_len, sig, &sig_len);
    if (rv != CKR_OK) { printf("ERR Sign: %lu\n", rv); free(data); free(sig); fn->C_Logout(session); fn->C_CloseSession(session); free(slots); bridge_close(); return; }
    free(data);
    CK_ULONG out_len = sig_len;
    unsigned char* out = sig;
    // CÖZÜM_04: EC imzasi ya ham r||s ya da zaten DER gelir; ilk bayt 0x30 ise
    // dokunma (çift dönüşüm bozuk imza üretirdi).
    if (mech.mechanism == CKM_ECDSA && sig_len > 0 && sig_len <= 132 && sig[0] != 0x30) {
        unsigned char* der = ecdsa_raw_to_der(sig, sig_len, &out_len);
        if (der) { free(sig); out = der; }
    }
    printf("SIGNATURE ");
    for (CK_ULONG i = 0; i < out_len; i++) printf("%02x", out[i]);
    printf("\n");
    free(out);
    fn->C_Logout(session);
    fn->C_CloseSession(session);
    free(slots);
    bridge_close();
}

int main(int argc, char** argv) {
    if (argc < 2) {
        printf("Usage: pkcs11-bridge [p11_path] <cmd> [args...]\n");
        printf("  list                          - List slots and tokens\n");
        printf("  cert <pin_hex>                - Get certificate and public key\n");
        printf("  sign <pin_hex> <data_hex>     - Sign data with private key\n");
        return 1;
    }
    int idx = 1;
    // Check if first arg is a path (contains '/' or ends with '.dylib' or '.so' or '.dll')
    if (argc > 2 && (strchr(argv[1], '/') || strstr(argv[1], ".dylib") || strstr(argv[1], ".so") || strstr(argv[1], ".dll"))) {
        strncpy(p11_path, argv[1], sizeof(p11_path) - 1);
        idx = 2;
    }
    if (strcmp(argv[idx], "list") == 0) cmd_list();
    else if (strcmp(argv[idx], "cert") == 0 && argc >= idx + 2) cmd_cert(argv[idx + 1]);
    else if (strcmp(argv[idx], "sign") == 0 && argc >= idx + 3) cmd_sign(argv[idx + 1], argv[idx + 2]);
    else fprintf(stderr, "Unknown command or missing args\n");
    return 0;
}
