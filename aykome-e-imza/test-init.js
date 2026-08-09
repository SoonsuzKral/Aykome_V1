const p11 = require('pkcs11js');

function testInit(label, initArgs) {
    const mod = new p11.PKCS11();
    mod.load('C:\\Windows\\System32\\akisp11.dll');
    try {
        if (initArgs !== undefined) {
            mod.C_Initialize(initArgs);
        } else {
            mod.C_Initialize();
        }
        console.log('? ' + label + ': C_Initialize basarili');
        const slots = mod.C_GetSlotList(true);
        console.log('   Slot sayisi:', slots.length);
        if (slots.length > 0) {
            mod.C_OpenSession(slots[0], p11.CKF_SERIAL_SESSION | p11.CKF_RW_SESSION);
            console.log('   Session acildi');
        }
        mod.C_Finalize();
        return true;
    } catch(e) {
        console.log('? ' + label + ': ' + e.message);
        try { mod.C_Finalize(); } catch {}
        return false;
    }
}

// Deneme 1: parametresiz
testInit('Parametresiz');

// Deneme 2: null
testInit('null', null);

// Deneme 3: bos nesne
testInit('Bos nesne', {});

// Deneme 4: OsLockingOK flag
const initArgs = new p11.CK_C_INITIALIZE_ARGS();
initArgs.CreateMutex = null;
initArgs.DestroyMutex = null;
initArgs.LockMutex = null;
initArgs.UnlockMutex = null;
initArgs.flags = 2; // CKF_OS_LOCKING_OK
initArgs.pReserved = null;
testInit('CKF_OS_LOCKING_OK', initArgs);

process.exit(0);
