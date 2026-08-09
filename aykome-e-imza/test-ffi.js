const pkcs11js = require('pkcs11js');
const ref = require('ref-napi');
const ffi = require('ffi-napi');

// Direct approach - load DLL and call C_GetFunctionList manually via ffi
try {
    const lib = ffi.Library('C:\\Windows\\System32\\akisp11.dll', {
        'C_GetFunctionList': ['uint', ['pointer']]
    });
    
    // Allocate a pointer (8 bytes) for the function list pointer
    const funcListPtr = ref.alloc('pointer');
    const rv = lib.C_GetFunctionList(funcListPtr);
    console.log('C_GetFunctionList returned:', rv);
    
    if (rv === 0) {
        const pFuncList = ref.deref(funcListPtr);
        console.log('Function list pointer:', pFuncList.toString('hex'));
        
        // Read the version (first 2 bytes at offset 0)
        const version = ref.read(pFuncList, 0, 2);
        console.log('Version major:', version[0], 'minor:', version[1]);
        
        // Read C_Initialize function pointer (offset varies by packing)
        // With default packing: offset 8, with pack(1): offset 2
        for (let offset of [2, 8]) {
            const ptr = ref.readPointer(pFuncList, offset);
            console.log(`C_Initialize ptr at offset ${offset}:`, ptr.toString('hex'));
        }
    }
} catch(e) {
    console.log('Error:', e.message);
}
process.exit(0);
