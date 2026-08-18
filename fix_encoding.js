const fs = require('fs');
const path = 'C:/Aykome_V1/app/Http/Controllers/Admin/ProcessController.php';
let content = fs.readFileSync(path, 'utf8');

// Find the destroyDefinition method start
const methodStart = content.indexOf('public function destroyDefinition');
if (methodStart === -1) { console.log('Method not found'); process.exit(1); }

// Find the updateDefinition start to know where destroyDefinition ends
const updateMethodStart = content.indexOf('public function updateDefinition', methodStart + 1);
if (updateMethodStart === -1) { console.log('updateDefinition not found'); process.exit(1); }

// Find the closing brace of destroyDefinition by counting braces
let braceCount = 0;
let inMethod = false;
let methodEnd = methodStart;
for (let i = methodStart; i < content.length; i++) {
    if (content[i] === '{') { braceCount++; inMethod = true; }
    if (content[i] === '}') { braceCount--; }
    if (inMethod && braceCount === 0) { methodEnd = i + 1; break; }
}

const oldMethod = content.substring(methodStart, methodEnd);

console.log('Old method length:', oldMethod.length);
console.log('First 200 chars:', oldMethod.substring(0, 200));

// Build new method with proper Turkish UTF-8 characters
// Note: we escape $ as \$ in the string to avoid JS template literal issues
const newMethod = "    public function destroyDefinition(Request \\$request, ProcessDefinition \\$process): RedirectResponse\n    {\n        // Varsayılan süreç silinemez — önce başka bir süreci varsayılan yap\n        if (\\$process->is_default) {\n            return back()->with('error', \"\\\\x22{\\$process->name}\\\\x22 varsayılan süreç olduğu için silinemez. Önce başka bir süreci varsayılan yapın.\");\n        }\n\n        // Bu sürece bağlı başvuru sayısını kontrol et\n        \\$applicationCount = \\App\\Models\\Application::query()\n            ->where('process_id', \\$process->id)\n            ->count();\n\n        if (\\$applicationCount > 0) {\n            return back()->with('error', \"\\\\x22{\\$process->name}\\\\x22 silinemez çünkü {\\$applicationCount} başvuru bu sürece bağlıdır. Önce başvuruları başka bir sürece taşıyın.\");\n        }\n\n        \\$name = \\$process->name;\n\n        // Adımlar cascade ile otomatik silinir (FK cascadeOnDelete)\n        \\$process->delete();\n\n        return redirect()->route('admin.processes.index')\n            ->with('success', \"Süreç \\\\x22{\\$name}\\\\x22 ve tüm adımları silindi.\");\n    }";

console.log('\nNew method:');
console.log(newMethod);

if (content.includes(oldMethod)) {
    console.log('\nFound old method, replacing...');
    content = content.replace(oldMethod, newMethod);
    fs.writeFileSync(path, content, 'utf8');
    console.log('File written successfully!');
} else {
    console.log('\nOld method NOT found in content. Looking for partial match...');
    // Maybe the turkish chars are the issue - let's look at what's actually there
    const corrupt = "silinemez Ã§Ã¼nkÃ¼";
    if (content.includes(corrupt)) {
        console.log('Found corrupted text:', corrupt);
    }
}
