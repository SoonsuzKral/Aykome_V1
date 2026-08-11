using System;
using System.Runtime.InteropServices;
using System.Text;

class Pkcs11Bridge
{
    const uint CKR_OK = 0;
    const uint CKR_ARGUMENTS_BAD = 0x00000007;
    const uint CKR_BUFFER_TOO_SMALL = 0x00000150;
    const uint CKR_CRYPTOKI_NOT_INITIALIZED = 0x00000101;
    const uint CKF_SERIAL_SESSION = 0x00000004;
    const uint CKF_RW_SESSION = 0x00000002;
    const uint CKF_OS_LOCKING_OK = 0x00000002;
    const uint CKF_LIBRARY_CANT_CREATE_OS_THREADS = 0x00000001;
    const uint CKU_USER = 1;
    const uint CKO_CERTIFICATE = 0x00000001;
    const uint CKO_PRIVATE_KEY = 0x00000003;
    const uint CKA_CLASS = 0x00000000;
    const uint CKA_SERIAL_NUMBER = 0x00000002;
    const uint CKA_ID = 0x00000102;
    const uint CKA_VALUE = 0x00000011;
    const uint CKA_KEY_TYPE = 0x00000100;
    const uint CKK_RSA = 0x00000000;
    const uint CKK_EC = 0x00000003;
    const uint CKM_ECDSA = 0x00001041;
    const uint CKM_RSA_PKCS = 0x00000001;

    // Function pointer offsets in CK_FUNCTION_LIST:
    // The DLL uses pack(1): version (2 bytes), then function pointers immediately
    // offset = 2 + index * 8
    const int OFF_C_Initialize = 2;           // index 0
    const int OFF_C_Finalize = 10;            // index 1
    const int OFF_C_GetSlotList = 34;         // index 4
    const int OFF_C_GetTokenInfo = 50;        // index 6
    const int OFF_C_OpenSession = 98;         // index 12
    const int OFF_C_CloseSession = 106;       // index 13
    const int OFF_C_Login = 146;              // index 18
    const int OFF_C_Logout = 154;             // index 19
    const int OFF_C_GetAttributeValue = 194;  // index 24
    const int OFF_C_FindObjectsInit = 210;    // index 26
    const int OFF_C_FindObjects = 218;        // index 27
    const int OFF_C_FindObjectsFinal = 226;   // index 28
    const int OFF_C_SignInit = 338;           // index 42
    const int OFF_C_Sign = 346;               // index 43
    const int OFF_C_CreateObject = 202;       // index 25

    // Thread-local PKCS#11 state
    [ThreadStatic] static IntPtr ts_handle;
    [ThreadStatic] static IntPtr ts_pFuncList;

    static CK_RV WithFuncList(string dllPath, out IntPtr pFuncList)
    {
        ts_handle = LoadLibrary(dllPath);
        if (ts_handle == IntPtr.Zero) { pFuncList = IntPtr.Zero; return CK_RV.LOAD_FAILED; }

        IntPtr pGetFuncList = GetProcAddress(ts_handle, "C_GetFunctionList");
        if (pGetFuncList == IntPtr.Zero) { FreeLibrary(ts_handle); pFuncList = IntPtr.Zero; return CK_RV.NO_ENTRY_POINT; }

        var getFuncList = (CK_C_GetFunctionList)Marshal.GetDelegateForFunctionPointer(pGetFuncList, typeof(CK_C_GetFunctionList));
        uint rv = getFuncList(out pFuncList);
        if (rv != CKR_OK) { FreeLibrary(ts_handle); pFuncList = IntPtr.Zero; return CK_RV.GETFUNCLIST_FAILED; }

        ts_pFuncList = pFuncList;
        return CK_RV.OK;
    }

    static void Cleanup()
    {
        if (ts_handle != IntPtr.Zero) { FreeLibrary(ts_handle); ts_handle = IntPtr.Zero; }
        ts_pFuncList = IntPtr.Zero;
    }

    static T GetFunc<T>(int offset) where T : class
    {
        IntPtr ptr = Marshal.ReadIntPtr(ts_pFuncList, offset);
        return (T)(object)Marshal.GetDelegateForFunctionPointer(ptr, typeof(T));
    }

    static uint Initialize(IntPtr pFuncList)
    {
        var cInit = GetFunc<CK_C_Initialize>(OFF_C_Initialize);
        return cInit(IntPtr.Zero);
    }

    enum CK_RV { OK, LOAD_FAILED, NO_ENTRY_POINT, GETFUNCLIST_FAILED, INIT_FAILED,
                 SLOTLIST_FAILED, OPENSESSION_FAILED, LOGIN_FAILED, FINDINIT_FAILED,
                 FIND_FAILED, GETATTRLEN_FAILED, GETATTR_FAILED, SIGNINIT_FAILED,
                 SIGNLEN_FAILED, SIGN_FAILED }

    static void Main(string[] args)
    {
        try
        {
            if (args.Length < 2) { Console.Error.WriteLine("Usage: Pkcs11Bridge <dll_path> <cmd> [args...]"); return; }

            string dllPath = args[0];
            string cmd = args[1];

            IntPtr pFuncList;
            CK_RV crv = WithFuncList(dllPath, out pFuncList);
            if (crv != CK_RV.OK) { Console.Error.WriteLine("ERR Load failed: " + crv); return; }

            uint rv = Initialize(pFuncList);
            if (rv != CKR_OK) { Console.Error.WriteLine("ERR C_Initialize: 0x" + rv.ToString("X8")); Cleanup(); return; }

            var cGetSlotList = GetFunc<CK_C_GetSlotList>(OFF_C_GetSlotList);
            var cFinalize = GetFunc<CK_C_Finalize>(OFF_C_Finalize);

            uint count = 0;
            rv = cGetSlotList(1, IntPtr.Zero, ref count);
            if (rv != CKR_OK) { Console.Error.WriteLine("ERR C_GetSlotList: 0x" + rv.ToString("X8")); cFinalize(IntPtr.Zero); Cleanup(); return; }
            Console.WriteLine("SLOTS " + count);

            if (count > 0 && cmd == "list")
            {
                // CK_SLOT_ID = CK_ULONG = 4 bytes on Windows x64
                IntPtr pSlots = Marshal.AllocHGlobal((int)(count * 4));
                rv = cGetSlotList(1, pSlots, ref count);
                if (rv == CKR_OK)
                {
                    var cGetTokenInfo = GetFunc<CK_C_GetTokenInfo>(OFF_C_GetTokenInfo);

                    for (uint i = 0; i < count; i++)
                    {
                        uint slotId = (uint)Marshal.ReadInt32(pSlots, (int)(i * 4));
                        Console.Write("SLOT " + slotId + " ");
                        CK_TOKEN_INFO tinfo;
                        rv = cGetTokenInfo(slotId, out tinfo);
                        if (rv == CKR_OK)
                            Console.WriteLine("TOKEN label=\"" + tinfo.label.Trim() + "\" man=\"" + tinfo.manufacturerID.Trim() + "\"");
                        else
                            Console.WriteLine("TOKEN_ERR");
                    }
                    Marshal.FreeHGlobal(pSlots);
                }
            }
            else if ((cmd == "cert" || cmd == "sign") && args.Length >= 3)
            {
                byte[] pinBytes = HexToBytes(args[2]);
                string pinStr = Encoding.UTF8.GetString(pinBytes);

                IntPtr pSlots = Marshal.AllocHGlobal((int)(count * 4));
                cGetSlotList(1, pSlots, ref count);
                uint slotId = (uint)Marshal.ReadInt32(pSlots);
                Marshal.FreeHGlobal(pSlots);

                var cOpenSession = GetFunc<CK_C_OpenSession>(OFF_C_OpenSession);
                var cLogin = GetFunc<CK_C_Login>(OFF_C_Login);
                var cFindInit = GetFunc<CK_C_FindObjectsInit>(OFF_C_FindObjectsInit);
                var cFindObjs = GetFunc<CK_C_FindObjects>(OFF_C_FindObjects);
                var cFindFinal = GetFunc<CK_C_FindObjectsFinal>(OFF_C_FindObjectsFinal);
                var cGetAttr = GetFunc<CK_C_GetAttributeValue>(OFF_C_GetAttributeValue);
                var cCloseSession = GetFunc<CK_C_CloseSession>(OFF_C_CloseSession);
                var cLogout = GetFunc<CK_C_Logout>(OFF_C_Logout);

                uint session = 0;
                rv = cOpenSession(slotId, CKF_SERIAL_SESSION | CKF_RW_SESSION, IntPtr.Zero, IntPtr.Zero, out session);
                if (rv != CKR_OK) { Console.Error.WriteLine("ERR OpenSession: 0x" + rv.ToString("X8")); cFinalize(IntPtr.Zero); Cleanup(); return; }

                byte[] pinBytesUtf8 = Encoding.UTF8.GetBytes(pinStr);
                rv = cLogin(session, CKU_USER, pinBytesUtf8, (uint)pinBytesUtf8.Length);
                if (rv != CKR_OK) { Console.Error.WriteLine("ERR Login: 0x" + rv.ToString("X8")); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                if (cmd == "cert")
                {
                    // Opsiyonel 4. argüman: hedeflenen sertifikanın seri numarası (hex).
                    // Verilirse tüm sertifika objeleri taranır, CKA_SERIAL_NUMBER eşleşeni seçilir.
                    byte[] targetSerial = (args.Length >= 4 && !string.IsNullOrEmpty(args[3])) ? HexToBytes(args[3]) : null;

                    uint objClass = CKO_CERTIFICATE;
                    var tmpl = new CK_ATTRIBUTE[] { new CK_ATTRIBUTE { type = CKA_CLASS, pValue = Marshal.AllocHGlobal(4), ulValueLen = 4 } };
                    Marshal.WriteInt32(tmpl[0].pValue, (int)objClass);
                    rv = cFindInit(session, tmpl, 1);
                    Marshal.FreeHGlobal(tmpl[0].pValue);
                    if (rv != CKR_OK) { Console.Error.WriteLine("ERR FindInit: 0x" + rv.ToString("X8")); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    uint foundObj = 0;
                    bool matched = false;
                    while (true)
                    {
                        uint obj = 0; uint objCount = 0;
                        rv = cFindObjs(session, out obj, 1, out objCount);
                        if (rv != CKR_OK || objCount == 0) break;

                        if (targetSerial == null) { foundObj = obj; matched = true; break; }

                        // AKIS token'ları CKA_SERIAL_NUMBER'ı boş (0x00) döndürür; bu yüzden
                        // eşleştirme önce serial, sonra CKA_ID (SubjectKeyIdentifier) üzerinden yapılır.
                        bool objMatched = false;
                        uint[] matchTypes = new uint[] { CKA_SERIAL_NUMBER, CKA_ID };
                        for (int mi = 0; mi < matchTypes.Length; mi++)
                        {
                            var mt = new CK_ATTRIBUTE[] { new CK_ATTRIBUTE { type = matchTypes[mi], pValue = IntPtr.Zero, ulValueLen = 0 } };
                            uint srv = cGetAttr(session, obj, mt, 1);
                            if (srv == CKR_OK && mt[0].ulValueLen > 0 && mt[0].ulValueLen <= 64)
                            {
                                byte[] sbuf = new byte[mt[0].ulValueLen];
                                IntPtr pSer = Marshal.AllocHGlobal(sbuf.Length);
                                mt[0].pValue = pSer;
                                srv = cGetAttr(session, obj, mt, 1);
                                Marshal.Copy(pSer, sbuf, 0, sbuf.Length);
                                Marshal.FreeHGlobal(pSer);
                                if (srv == CKR_OK && SerialEquals(sbuf, targetSerial))
                                {
                                    objMatched = true;
                                    break;
                                }
                            }
                        }
                        if (objMatched) { foundObj = obj; matched = true; break; }
                    }
                    cFindFinal(session);
                    if (!matched) { Console.Error.WriteLine("ERR No cert"); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    var getTmpl = new CK_ATTRIBUTE[] { new CK_ATTRIBUTE { type = CKA_VALUE, pValue = IntPtr.Zero, ulValueLen = 0 } };
                    rv = cGetAttr(session, foundObj, getTmpl, 1);
                    if (rv != CKR_OK) { Console.Error.WriteLine("ERR GetAttrLen: 0x" + rv.ToString("X8")); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    byte[] certBuf = new byte[getTmpl[0].ulValueLen];
                    IntPtr pCert = Marshal.AllocHGlobal(certBuf.Length);
                    getTmpl[0].pValue = pCert;
                    rv = cGetAttr(session, foundObj, getTmpl, 1);
                    Marshal.Copy(pCert, certBuf, 0, certBuf.Length);
                    Marshal.FreeHGlobal(pCert);
                    if (rv != CKR_OK) { Console.Error.WriteLine("ERR GetAttr: 0x" + rv.ToString("X8")); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    Console.Write("CERT_DER ");
                    Console.WriteLine(BytesToHex(certBuf));
                    Console.WriteLine("CERT_OK");

                    // Anahtar tipi tespiti (RSA vs EC) — imza mekanizması seçimi için.
                    // E-Tuğra sertifikaları RSA, Kamu SM sertifikaları ECDSA taşır.
                    uint keyObj = 0; uint keyCount = 0;
                    var keyTmpl = new CK_ATTRIBUTE[] { new CK_ATTRIBUTE { type = CKA_CLASS, pValue = Marshal.AllocHGlobal(4), ulValueLen = 4 } };
                    Marshal.WriteInt32(keyTmpl[0].pValue, (int)CKO_PRIVATE_KEY);
                    rv = cFindInit(session, keyTmpl, 1);
                    Marshal.FreeHGlobal(keyTmpl[0].pValue);
                    if (rv == CKR_OK)
                    {
                        rv = cFindObjs(session, out keyObj, 1, out keyCount);
                        cFindFinal(session);
                        if (rv == CKR_OK && keyCount > 0)
                        {
                            var kt = new CK_ATTRIBUTE[] { new CK_ATTRIBUTE { type = CKA_KEY_TYPE, pValue = IntPtr.Zero, ulValueLen = 0 } };
                            uint krv = cGetAttr(session, keyObj, kt, 1);
                            if (krv == CKR_OK && kt[0].ulValueLen == 4)
                            {
                                IntPtr pKt = Marshal.AllocHGlobal(4);
                                kt[0].pValue = pKt;
                                krv = cGetAttr(session, keyObj, kt, 1);
                                uint keyType = (uint)Marshal.ReadInt32(pKt);
                                Marshal.FreeHGlobal(pKt);
                                if (keyType == CKK_RSA) Console.WriteLine("KEY_TYPE RSA");
                                else if (keyType == CKK_EC) Console.WriteLine("KEY_TYPE EC");
                                else Console.WriteLine("KEY_TYPE " + keyType);
                            }
                        }
                    }

                    cLogout(session);
                    cCloseSession(session);
                }
                else if (cmd == "sign" && args.Length >= 4)
                {
                    byte[] dataBytes = HexToBytes(args[3]);

                    uint objClass = CKO_PRIVATE_KEY;
                    var tmpl = new CK_ATTRIBUTE[] { new CK_ATTRIBUTE { type = CKA_CLASS, pValue = Marshal.AllocHGlobal(4), ulValueLen = 4 } };
                    Marshal.WriteInt32(tmpl[0].pValue, (int)objClass);
                    rv = cFindInit(session, tmpl, 1);
                    Marshal.FreeHGlobal(tmpl[0].pValue);
                    if (rv != CKR_OK) { Console.Error.WriteLine("ERR FindKeyInit: 0x" + rv.ToString("X8")); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    uint key = 0; uint keyCount = 0;
                    rv = cFindObjs(session, out key, 1, out keyCount);
                    cFindFinal(session);
                    if (rv != CKR_OK || keyCount == 0) { Console.Error.WriteLine("ERR No key"); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    // Anahtar tipi: RSA -> CKM_RSA_PKCS (AKIS middleware hash'li RSA mekanizmalarini
                    // desteklemez; JS SHA-256 DigestInfo ASN.1 gonderir), EC -> CKM_ECDSA (Kamu SM)
                    uint keyType = CKK_EC;
                    var ktAttr = new CK_ATTRIBUTE[] { new CK_ATTRIBUTE { type = CKA_KEY_TYPE, pValue = IntPtr.Zero, ulValueLen = 0 } };
                    uint ktrv = cGetAttr(session, key, ktAttr, 1);
                    if (ktrv == CKR_OK && ktAttr[0].ulValueLen == 4)
                    {
                        IntPtr pKt = Marshal.AllocHGlobal(4);
                        ktAttr[0].pValue = pKt;
                        ktrv = cGetAttr(session, key, ktAttr, 1);
                        keyType = (uint)Marshal.ReadInt32(pKt);
                        Marshal.FreeHGlobal(pKt);
                    }

                    var cSignInit = GetFunc<CK_C_SignInit>(OFF_C_SignInit);
                    var cSign = GetFunc<CK_C_Sign>(OFF_C_Sign);

                    var mech = new CK_MECHANISM { mechanism = (keyType == CKK_RSA) ? CKM_RSA_PKCS : CKM_ECDSA, pParameter = IntPtr.Zero, ulParameterLen = 0 };
                    rv = cSignInit(session, ref mech, key);
                    if (rv != CKR_OK) { Console.Error.WriteLine("ERR SignInit: 0x" + rv.ToString("X8")); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    uint sigLen = 512;
                    byte[] sigProbe = new byte[sigLen];
                    rv = cSign(session, dataBytes, (uint)dataBytes.Length, sigProbe, ref sigLen);
                    if (rv != CKR_OK && rv != CKR_BUFFER_TOO_SMALL) { Console.Error.WriteLine("ERR SignLen: 0x" + rv.ToString("X8")); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    byte[] sigBuf = new byte[sigLen];
                    rv = cSign(session, dataBytes, (uint)dataBytes.Length, sigBuf, ref sigLen);
                    if (rv != CKR_OK) { Console.Error.WriteLine("ERR Sign: 0x" + rv.ToString("X8")); cLogout(session); cCloseSession(session); cFinalize(IntPtr.Zero); Cleanup(); return; }

                    Array.Resize(ref sigBuf, (int)sigLen);

                    // ECDSA ham (r||s) -> DER dönüşümü; RSA imzası zaten PKCS#1 v1.5, dokunulmaz
                    if (keyType != CKK_RSA && sigLen > 0 && sigLen <= 132)
                    {
                        byte[] der = EcdsaRawToDer(sigBuf);
                        if (der != null) sigBuf = der;
                    }

                    Console.WriteLine("KEY_TYPE " + (keyType == CKK_RSA ? "RSA" : "EC"));
                    Console.Write("SIGNATURE ");
                    Console.WriteLine(BytesToHex(sigBuf));

                    cLogout(session);
                    cCloseSession(session);
                }
            }

            cFinalize(IntPtr.Zero);
            Cleanup();
        }
        catch (Exception ex)
        {
            Console.Error.WriteLine("ERR " + ex.Message);
        }
    }

    static byte[] EcdsaRawToDer(byte[] raw)
    {
        if (raw.Length == 0) return null;
        int half = raw.Length / 2;
        byte[] r = new byte[half]; Array.Copy(raw, 0, r, 0, half);
        byte[] s = new byte[half]; Array.Copy(raw, half, s, 0, half);
        int rLead = (r[0] & 0x80) != 0 ? 1 : 0;
        int sLead = (s[0] & 0x80) != 0 ? 1 : 0;
        int rLen = half + rLead;
        int sLen = half + sLead;
        int derLen = 2 + 2 + rLen + 2 + sLen;
        byte[] der = new byte[derLen];
        int pos = 0;
        der[pos++] = 0x30;
        der[pos++] = (byte)(derLen - 2);
        der[pos++] = 0x02;
        der[pos++] = (byte)rLen;
        if (rLead != 0) der[pos++] = 0x00;
        Array.Copy(r, 0, der, pos, half); pos += half;
        der[pos++] = 0x02;
        der[pos++] = (byte)sLen;
        if (sLead != 0) der[pos++] = 0x00;
        Array.Copy(s, 0, der, pos, half); pos += half;
        if (pos < derLen) Array.Resize(ref der, pos);
        return der;
    }

    static byte[] HexToBytes(string hex)
    {
        byte[] bytes = new byte[hex.Length / 2];
        for (int i = 0; i < bytes.Length; i++)
            bytes[i] = Convert.ToByte(hex.Substring(i * 2, 2), 16);
        return bytes;
    }

    // Sertifika seri numarası karşılaştırması: iki yanda da pozitif integer
    // gösteriminden gelebilecek baştaki 0x00 byte'larını yok say.
    static bool SerialEquals(byte[] a, byte[] b)
    {
        int i = 0, j = 0;
        while (i < a.Length - 1 && a[i] == 0) i++;
        while (j < b.Length - 1 && b[j] == 0) j++;
        if (a.Length - i != b.Length - j) return false;
        for (int k = 0; k < a.Length - i; k++)
            if (a[i + k] != b[j + k]) return false;
        return true;
    }

    static string BytesToHex(byte[] bytes)
    {
        var sb = new StringBuilder(bytes.Length * 2);
        foreach (byte b in bytes) sb.Append(b.ToString("X2"));
        return sb.ToString();
    }

    // PKCS#11 delegate definitions
    // On Windows x64: CK_ULONG = unsigned long = 4 bytes = uint in C#
    // CK_SLOT_ID, CK_SESSION_HANDLE, CK_OBJECT_HANDLE, CK_FLAGS, CK_USER_TYPE, CK_MECHANISM_TYPE all = CK_ULONG = uint

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_GetFunctionList(out IntPtr ppFunctionList);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_Initialize(IntPtr pInitArgs);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_Finalize(IntPtr pReserved);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_GetSlotList(byte tokenPresent, IntPtr pSlotList, ref uint pulCount);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_GetTokenInfo(uint slotID, out CK_TOKEN_INFO pInfo);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_OpenSession(uint slotID, uint flags, IntPtr pApplication, IntPtr pNotify, out uint pSession);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_CloseSession(uint hSession);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_Login(uint hSession, uint userType, [In] byte[] pPin, uint ulPinLen);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_Logout(uint hSession);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_FindObjectsInit(uint hSession, [In] CK_ATTRIBUTE[] pTemplate, uint ulCount);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_FindObjects(uint hSession, out uint phObject, uint ulMaxObjectCount, out uint pulObjectCount);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_FindObjectsFinal(uint hSession);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_GetAttributeValue(uint hSession, uint hObject, [In, Out] CK_ATTRIBUTE[] pTemplate, uint ulCount);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_SignInit(uint hSession, ref CK_MECHANISM pMechanism, uint hKey);

    [UnmanagedFunctionPointer(CallingConvention.Cdecl)]
    delegate uint CK_C_Sign(uint hSession, [In] byte[] pData, uint ulDataLen, [Out] byte[] pSignature, ref uint pulSignatureLen);

    // PKCS#11 struct definitions
    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    struct CK_ATTRIBUTE
    {
        public uint type;
        public IntPtr pValue;
        public uint ulValueLen;
    }

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    struct CK_MECHANISM
    {
        public uint mechanism;
        public IntPtr pParameter;
        public uint ulParameterLen;
    }

    [StructLayout(LayoutKind.Sequential, Pack = 1, CharSet = CharSet.Ansi)]
    struct CK_TOKEN_INFO
    {
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 32)] public string label;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 32)] public string manufacturerID;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 16)] public string model;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 16)] public string serialNumber;
        public uint flags;
        public uint ulMaxSessionCount;
        public uint ulSessionCount;
        public uint ulMaxRwSessionCount;
        public uint ulRwSessionCount;
        public uint ulMaxPinLen;
        public uint ulMinPinLen;
        public uint ulTotalPublicMemory;
        public uint ulFreePublicMemory;
        public uint ulTotalPrivateMemory;
        public uint ulFreePrivateMemory;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 16)] public string hardwareVersion;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 16)] public string firmwareVersion;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 16)] public string utcTime;
    }

    [DllImport("kernel32.dll", CharSet = CharSet.Ansi)]
    static extern IntPtr LoadLibrary(string lpFileName);

    [DllImport("kernel32.dll", CharSet = CharSet.Ansi)]
    static extern IntPtr GetProcAddress(IntPtr hModule, string lpProcName);

    [DllImport("kernel32.dll")]
    static extern bool FreeLibrary(IntPtr hModule);
}
