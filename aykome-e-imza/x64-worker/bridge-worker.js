const { spawnSync } = require('child_process');
const path = require('path');

const BRIDGE = path.join(__dirname, 'pkcs11-bridge');

function toHex(str) {
    return Buffer.from(str, 'utf8').toString('hex');
}

function fromHex(hex) {
    return Buffer.from(hex, 'hex');
}

function runBridge(args) {
    const result = spawnSync('arch', ['-x86_64', BRIDGE, ...args], {
        encoding: 'utf8',
        maxBuffer: 50 * 1024 * 1024,
    });
    if (result.error) {
        throw new Error('Bridge error: ' + result.error.message);
    }
    if (result.status !== 0) {
        throw new Error('Bridge exit ' + result.status + ': ' + (result.stderr || 'unknown'));
    }
    return result.stdout;
}

function parseOutput(stdout) {
    const lines = stdout.trim().split('\n');
    const result = {};
    for (const line of lines) {
        if (line.startsWith('SLOTS ')) {
            result.slots = parseInt(line.split(' ')[1]);
        } else if (line.startsWith('TOKEN ')) {
            const parts = line.match(/(\w+)="([^"]*)"/g);
            if (!result.tokens) result.tokens = [];
            const tok = {};
            for (const p of parts || []) {
                const [k, v] = p.split('=');
                tok[k] = v.replace(/^"|"$/g, '');
            }
            result.tokens = result.tokens || [];
            result.tokens.push(tok);
        } else if (line.startsWith('CERT_DER ')) {
            result.certDer = line.substring(9);
        } else if (line.startsWith('KEY_MOD ')) {
            result.keyMod = line.substring(8);
        } else if (line.startsWith('KEY_EXP ')) {
            result.keyExp = line.substring(8);
        } else if (line.startsWith('CERT_OK')) {
            result.certOk = true;
        } else if (line.startsWith('SIGNATURE ')) {
            result.signature = line.substring(10);
        } else if (line.startsWith('ERR ')) {
            result.error = line.substring(4);
        }
    }
    return result;
}

module.exports = {
    listTokens() {
        const out = runBridge(['list']);
        return parseOutput(out);
    },

    getCertificate(pin) {
        const pinHex = toHex(pin);
        const out = runBridge(['cert', pinHex]);
        const result = parseOutput(out);
        if (result.error) throw new Error(result.error);
        if (!result.certOk) throw new Error('Certificate could not be retrieved');
        return {
            certDer: fromHex(result.certDer),
            keyMod: result.keyMod ? fromHex(result.keyMod) : null,
            keyExp: result.keyExp ? fromHex(result.keyExp) : null,
        };
    },

    signData(pin, data) {
        const pinHex = toHex(pin);
        const dataHex = data.toString('hex');
        const out = runBridge(['sign', pinHex, dataHex]);
        const result = parseOutput(out);
        if (result.error) throw new Error(result.error);
        if (!result.signature) throw new Error('No signature returned');
        return fromHex(result.signature);
    },
};
