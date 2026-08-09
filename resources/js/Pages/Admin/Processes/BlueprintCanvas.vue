<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, toRefs, watch } from 'vue';

// Props from controller
const props = defineProps({
    process: Object,
    steps: Array,
    connections: Array,
    roleOptions: Object,
    moduleOptions: Object,
    personnelOptions: Array,
    users: Array,
});

// Destructure props to use as refs - only those without local copies
const { personnelOptions, roleOptions, moduleOptions } = toRefs(props);

// ─── State ───────────────────────────────────────────────────────────────────

const selectedStepId = ref(null);
const selectedStep = computed(() => steps.value.find(s => s.id === selectedStepId.value) || null);

// Canvas transform
const canvasTransform = reactive({ x: 0, y: 0, scale: 1 });
const isDraggingCanvas = ref(false);
const canvasDragStart = reactive({ x: 0, y: 0 });

// Node drag
const draggingNodeId = ref(null);
const dragOffset = reactive({ x: 0, y: 0 });

// Connection drawing
const isConnecting = ref(false);
const connectingFromId = ref(null);
const connectingLine = reactive({ x1: 0, y1: 0, x2: 0, y2: 0 });

// Canvas ref
const canvasEl = ref(null);
const svgEl = ref(null);

// Mobile mode
const isMobile = ref(false);
const isTablet = ref(false);
const isSidebarOpen = ref(true);

// Step being edited
const editingStep = ref(null);
const showAddStep = ref(false);

// Process name editing
const isEditingProcessName = ref(false);
const processNameInput = ref('');
const savingProcessName = ref(false);
const personnelSearch = ref('');
const personnelDirectInput = ref('');

const filteredPersonnel = computed(() => {
    const list = personnelOptions.value || [];
    if (!personnelSearch.value) return list;
    const q = personnelSearch.value.toLowerCase();
    return list.filter(u =>
        u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
    );
});

function addDirectPersonnel() {
    if (!personnelDirectInput.value.trim() || !editingStep.value) return;
    const q = personnelDirectInput.value.trim().toLowerCase();
    const list = personnelOptions.value || [];
    const user = list.find(u => u.name.toLowerCase().includes(q));
    if (user) {
        const arr = editingStep.value.personnel_ids || [];
        if (!arr.includes(user.id)) {
            arr.push(user.id);
            editingStep.value.personnel_ids = [...arr];
            // Auto-derive roles from personnel
            autoDeriveRolesFromPersonnel();
        }
        personnelDirectInput.value = '';
    } else {
        alert(`"${personnelDirectInput.value}" bulunamadı. Lütfen listeden seçin.`);
    }
}

function removePersonnel(userId) {
    if (!editingStep.value) return;
    const arr = editingStep.value.personnel_ids || [];
    editingStep.value.personnel_ids = arr.filter(id => id !== userId);
    // Re-derive roles after removal
    autoDeriveRolesFromPersonnel();
}

// Auto-derive roles from selected personnel (they have roles in the system)
function autoDeriveRolesFromPersonnel() {
    if (!editingStep.value) return;
    const selectedIds = editingStep.value.personnel_ids || [];
    if (!selectedIds.length) {
        editingStep.value.roles = [];
        return;
    }
    // Get personnel with their roles from the full list
    const selectedPersonnel = personnelOptions.value.filter(u => selectedIds.includes(u.id));
    // Collect unique roles from all selected personnel
    const roleSet = new Set();
    selectedPersonnel.forEach(u => {
        if (u.roles && Array.isArray(u.roles)) {
            u.roles.forEach(r => roleSet.add(r));
        }
    });
    editingStep.value.roles = [...roleSet];
}

// ─── Reactive copies of server data ──────────────────────────────────────────

const steps = ref([...props.steps]);
const connections = ref([...props.connections]);

// Sync steps from props when they change (after page reload via redirect)
watch(() => props.steps, (newSteps) => {
    if (newSteps && newSteps.length) {
        steps.value = [...newSteps];
    }
}, { deep: true });

// ─── Computed ────────────────────────────────────────────────────────────────

// Responsive node dimensions based on screen size
const getNodeWidth = () => {
    if (typeof window !== 'undefined') {
        if (window.innerWidth < 640) return 200;  // Mobile
        if (window.innerWidth < 1024) return 220; // Tablet
    }
    return 260; // Desktop
};
const getNodeHeight = () => {
    if (typeof window !== 'undefined' && window.innerWidth < 640) return 100;
    return 120;
};

// Use refs for reactive node dimensions
const NODE_WIDTH = ref(260);
const NODE_HEIGHT = ref(120);

// Update node dimensions on resize
function updateNodeDimensions() {
    NODE_WIDTH.value = getNodeWidth();
    NODE_HEIGHT.value = getNodeHeight();
}

const SOCKET_SIZE = 12;

const selectedStepPersonnel = computed({
    get() { return editingStep.value?.personnel_ids || []; },
    set(val) {
        if (editingStep.value) editingStep.value.personnel_ids = val;
    }
});

const selectedStepVisibility = computed({
    get() { return editingStep.value?.visibility_config || []; },
    set(val) {
        if (editingStep.value) editingStep.value.visibility_config = val;
    }
});

const selectedStepRoles = computed({
    get() { return editingStep.value?.roles || []; },
    set(val) {
        if (editingStep.value) editingStep.value.roles = val;
    }
});

const selectedStepModules = computed({
    get() { return editingStep.value?.approvable_modules || []; },
    set(val) {
        if (editingStep.value) editingStep.value.approvable_modules = val;
    }
});

// Signature config
const signatureEnabled = computed({
    get() { return editingStep.value?.signature_config?.enabled ?? false; },
    set(val) {
        if (editingStep.value) {
            editingStep.value.signature_config = editingStep.value.signature_config || {};
            editingStep.value.signature_config.enabled = val;
        }
    }
});

const signatureSignerIds = computed({
    get() { return editingStep.value?.signature_config?.signer_ids ?? []; },
    set(val) {
        if (editingStep.value) {
            editingStep.value.signature_config = editingStep.value.signature_config || {};
            editingStep.value.signature_config.signer_ids = val;
        }
    }
});

const signaturePdfType = computed({
    get() { return editingStep.value?.signature_config?.pdf_type ?? 'ruhsat'; },
    set(val) {
        if (editingStep.value) {
            editingStep.value.signature_config = editingStep.value.signature_config || {};
            editingStep.value.signature_config.pdf_type = val;
        }
    }
});

const pdfTypeOptions = [
    { value: 'ruhsat', label: 'Ruhsat' },
    { value: 'metraj', label: 'Kazı Metraj' },
    { value: 'tahakkuk', label: 'Tahakkuk' },
    { value: 'taahhutname', label: 'Taahhütname' },
    { value: 'makbuz', label: 'Tahsilat Makbuzu' },
    { value: 'pre_permit', label: 'Ön Kazı İzni' },
    { value: 'cover_letter', label: 'Üst Yazı' },
];

const actionTypeOptions = [
    { value: 'onay', label: '✅ Onay', description: 'Standart onay işlemi' },
    { value: 'paraf', label: '✍️ Paraf', description: 'İlk paraflama - hafif onay' },
    { value: 'e_imza', label: '🖊️ E-İmza', description: 'PKCS#11 dijital imza' },
];

const actionType = computed({
    get() { return editingStep.value?.action_type ?? 'onay'; },
    set(val) {
        if (editingStep.value) {
            editingStep.value.action_type = val;
        }
    }
});

// ─── Module Permissions ───────────────────────────────────────────────────────

const modulePermissions = computed({
    get() { return editingStep.value?.module_permissions || {}; },
    set(val) {
        if (editingStep.value) {
            editingStep.value.module_permissions = val;
        }
    }
});

// Get or init module permission config
function getModulePerm(module) {
    const perms = modulePermissions.value;
    return perms[module] || {
        action_type: editingStep.value?.action_type ?? 'onay',
        approver_roles: [],
        approver_ids: [],
        signer_ids: [],
        visible_to_roles: [],
        visible_to_ids: [],
    };
}

function setModulePerm(module, config) {
    const perms = { ...modulePermissions.value };
    perms[module] = config;
    modulePermissions.value = perms;
}

function toggleModuleApproverRole(module, role) {
    const perm = getModulePerm(module);
    const idx = perm.approver_roles.indexOf(role);
    if (idx === -1) perm.approver_roles.push(role);
    else perm.approver_roles.splice(idx, 1);
    setModulePerm(module, perm);
}

function toggleModuleSignerId(module, userId) {
    const perm = getModulePerm(module);
    const idx = perm.signer_ids.indexOf(userId);
    if (idx === -1) perm.signer_ids.push(userId);
    else perm.signer_ids.splice(idx, 1);
    setModulePerm(module, perm);
}

function toggleModuleVisibleRole(module, role) {
    const perm = getModulePerm(module);
    const idx = perm.visible_to_roles.indexOf(role);
    if (idx === -1) perm.visible_to_roles.push(role);
    else perm.visible_to_roles.splice(idx, 1);
    setModulePerm(module, perm);
}

function setModuleActionType(module, val) {
    const perm = getModulePerm(module);
    perm.action_type = val;
    setModulePerm(module, perm);
}

function hasModulePerm(module) {
    const perms = modulePermissions.value;
    return perms[module] && (
        (perms[module].approver_roles && perms[module].approver_roles.length) ||
        (perms[module].approver_ids && perms[module].approver_ids.length) ||
        (perms[module].signer_ids && perms[module].signer_ids.length) ||
        (perms[module].action_type && perms[module].action_type !== (editingStep.value?.action_type ?? 'onay'))
    );
}

const modulePermissionModules = computed(() => {
    // Show all module options for configuration
    return Object.entries(moduleOptions.value || {}).map(([key, label]) => ({
        key,
        label,
        perm: getModulePerm(key),
        hasCustom: hasModulePerm(key),
    }));
});

// Expanded module in permission panel
const expandedModule = ref(null);
function toggleExpandedModule(key) {
    expandedModule.value = expandedModule.value === key ? null : key;
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function nodeLeft(step) { return step.canvas_x; }
function nodeTop(step)  { return step.canvas_y; }
function nodeRight(step){ return step.canvas_x + NODE_WIDTH.value; }
function nodeMidY(step) { return step.canvas_y + NODE_HEIGHT.value / 2; }

function getInputPos(step) {
    return { x: nodeLeft(step), y: nodeMidY(step) };
}
function getOutputPos(step) {
    return { x: nodeRight(step), y: nodeMidY(step) };
}

function screenToCanvas(sx, sy) {
    const rect = canvasEl.value.getBoundingClientRect();
    return {
        x: (sx - rect.left - canvasTransform.x) / canvasTransform.scale,
        y: (sy - rect.top  - canvasTransform.y) / canvasTransform.scale,
    };
}

function getSvgPos(step, isOutput) {
    const pos = isOutput ? getOutputPos(step) : getInputPos(step);
    return {
        x: canvasTransform.x + pos.x * canvasTransform.scale,
        y: canvasTransform.y + pos.y * canvasTransform.scale,
    };
}

function bezierPath(x1, y1, x2, y2) {
    const dx = Math.abs(x2 - x1);
    const cpOffset = Math.max(40, dx * 0.5);
    return `M ${x1} ${y1} C ${x1 + cpOffset} ${y1}, ${x2 - cpOffset} ${y2}, ${x2} ${y2}`;
}

function getStepById(id) {
    return steps.value.find(s => s.id === id);
}

// ─── Socket Position Helpers (screen coords = SVG coordinate space) ─────────────

function getNodeScreenPos(step) {
    return {
        x: canvasTransform.x + step.canvas_x * canvasTransform.scale,
        y: canvasTransform.y + step.canvas_y * canvasTransform.scale,
    };
}

function getSocketScreenPos(step, isOutput) {
    const pos = getNodeScreenPos(step);
    const w = NODE_WIDTH.value * canvasTransform.scale;
    const h = NODE_HEIGHT.value * canvasTransform.scale;
    return {
        x: isOutput ? pos.x + w : pos.x,
        y: pos.y + h / 2,
    };
}

// ─── Canvas Pan ───────────────────────────────────────────────────────────────

function onCanvasMouseDown(e) {
    if (e.target !== canvasEl.value && e.target !== svgEl.value && !e.target.classList.contains('canvas-bg')) return;
    isDraggingCanvas.value = true;
    canvasDragStart.x = e.clientX - canvasTransform.x;
    canvasDragStart.y = e.clientY - canvasTransform.y;
    canvasEl.value.style.cursor = 'grabbing';
}

function onCanvasMouseMove(e) {
    if (isDraggingCanvas.value) {
        canvasTransform.x = e.clientX - canvasDragStart.x;
        canvasTransform.y = e.clientY - canvasDragStart.y;
    }
    if (isConnecting.value) {
        const fromStep = getStepById(connectingFromId.value);
        if (fromStep) {
            const fp = getSocketScreenPos(fromStep, true);
            connectingLine.x1 = fp.x;
            connectingLine.y1 = fp.y;
            // Endpoint follows mouse in screen space
            const cp = screenToCanvas(e.clientX, e.clientY);
            connectingLine.x2 = canvasTransform.x + cp.x * canvasTransform.scale;
            connectingLine.y2 = canvasTransform.y + cp.y * canvasTransform.scale;
        }
    }
    if (draggingNodeId.value !== null) {
        const step = getStepById(draggingNodeId.value);
        if (step) {
            const rect = canvasEl.value.getBoundingClientRect();
            step.canvas_x = Math.max(0, (e.clientX - rect.left - canvasTransform.x) / canvasTransform.scale - dragOffset.x);
            step.canvas_y = Math.max(0, (e.clientY - rect.top  - canvasTransform.y) / canvasTransform.scale - dragOffset.y);
        }
    }
}

function onCanvasMouseUp() {
    isDraggingCanvas.value = false;
    if (canvasEl.value) canvasEl.value.style.cursor = '';
    isConnecting.value = false;
    connectingFromId.value = null;
    draggingNodeId.value = null;
}

// ─── Node Drag ───────────────────────────────────────────────────────────────

function onNodeMouseDown(e, step) {
    e.stopPropagation();
    const rect = e.currentTarget.getBoundingClientRect();
    dragOffset.x = (e.clientX - rect.left) / canvasTransform.scale;
    dragOffset.y = (e.clientY - rect.top)  / canvasTransform.scale;
    draggingNodeId.value = step.id;
}

function onNodeClick(e, step) {
    e.stopPropagation();
    selectedStepId.value = step.id;
    editingStep.value = { ...step };
    showAddStep.value = false;
}

function onSocketMouseDown(e, stepId, isOutput) {
    e.stopPropagation();
    isConnecting.value = true;
    connectingFromId.value = stepId;
    const fromStep = getStepById(stepId);
    if (fromStep) {
        // Output socket → right edge of node; input socket → left edge
        const fp = getSocketScreenPos(fromStep, true);
        connectingLine.x1 = fp.x;
        connectingLine.y1 = fp.y;
        connectingLine.x2 = fp.x;
        connectingLine.y2 = fp.y;
    }
}

function onSocketMouseUp(e, stepId) {
    if (!isConnecting.value || connectingFromId.value === null) return;
    if (connectingFromId.value === stepId) return;

    // Create connection
    const existingIdx = connections.value.findIndex(
        c => c.from_step_id === connectingFromId.value && c.to_step_id === stepId
    );
    if (existingIdx === -1) {
        connections.value.push({
            from_step_id: connectingFromId.value,
            to_step_id: stepId,
        });
    }

    isConnecting.value = false;
    connectingFromId.value = null;
}

// ─── Zoom ─────────────────────────────────────────────────────────────────────

function onWheel(e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    const newScale = Math.min(2, Math.max(0.25, canvasTransform.scale * delta));
    const rect = canvasEl.value.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;
    canvasTransform.x = mouseX - (mouseX - canvasTransform.x) * (newScale / canvasTransform.scale);
    canvasTransform.y = mouseY - (mouseY - canvasTransform.y) * (newScale / canvasTransform.scale);
    canvasTransform.scale = newScale;
}

function fitCanvas() {
    if (!steps.value.length) return;
    const minX = Math.min(...steps.value.map(s => s.canvas_x));
    const minY = Math.min(...steps.value.map(s => s.canvas_y));
    const maxX = Math.max(...steps.value.map(s => s.canvas_x + NODE_WIDTH.value));
    const maxY = Math.max(...steps.value.map(s => s.canvas_y + NODE_HEIGHT.value));
    const rect = canvasEl.value.getBoundingClientRect();
    const scaleX = (rect.width  - 80) / (maxX - minX + 100);
    const scaleY = (rect.height - 80) / (maxY - minY + 100);
    canvasTransform.scale = Math.min(1.5, Math.max(0.25, Math.min(scaleX, scaleY)));
    canvasTransform.x = (rect.width  - (maxX - minX) * canvasTransform.scale) / 2 - minX * canvasTransform.scale + 40;
    canvasTransform.y = (rect.height - (maxY - minY) * canvasTransform.scale) / 2 - minY * canvasTransform.scale + 40;
}

// Auto-layout: arrange steps in a horizontal row with proper spacing
function autoLayoutSteps() {
    if (!steps.value.length) return;
    const startX = 80;
    const startY = 150;
    const spacingX = 500; // More space between steps for connection lines
    const spacingY = NODE_HEIGHT.value + 200;

    steps.value.forEach((step, idx) => {
        step.canvas_x = startX + idx * spacingX;
        step.canvas_y = startY;
    });

    // Fit after layout
    nextTick(() => fitCanvas());
}

// Step colors based on order
const STEP_COLORS = [
    { bg: 'bg-gradient-to-br from-blue-500 to-blue-600', text: 'text-white', border: 'border-blue-400', shadow: 'shadow-blue-200' },
    { bg: 'bg-gradient-to-br from-violet-500 to-violet-600', text: 'text-white', border: 'border-violet-400', shadow: 'shadow-violet-200' },
    { bg: 'bg-gradient-to-br from-emerald-500 to-emerald-600', text: 'text-white', border: 'border-emerald-400', shadow: 'shadow-emerald-200' },
    { bg: 'bg-gradient-to-br from-amber-500 to-amber-600', text: 'text-white', border: 'border-amber-400', shadow: 'shadow-amber-200' },
    { bg: 'bg-gradient-to-br from-rose-500 to-rose-600', text: 'text-white', border: 'border-rose-400', shadow: 'shadow-rose-200' },
    { bg: 'bg-gradient-to-br from-cyan-500 to-cyan-600', text: 'text-white', border: 'border-cyan-400', shadow: 'shadow-cyan-200' },
    { bg: 'bg-gradient-to-br from-indigo-500 to-indigo-600', text: 'text-white', border: 'border-indigo-400', shadow: 'shadow-indigo-200' },
    { bg: 'bg-gradient-to-br from-teal-500 to-teal-600', text: 'text-white', border: 'border-teal-400', shadow: 'shadow-teal-200' },
];

function getStepColor(idx) {
    return STEP_COLORS[idx % STEP_COLORS.length];
}

// Step icons based on order
const STEP_ICONS = ['⭐', '🔷', '✨', '🔶', '🌟', '💎', '🔹', '🔸'];

function getStepIcon(idx) {
    return STEP_ICONS[idx % STEP_ICONS.length];
}

function zoomIn()  { canvasTransform.scale = Math.min(2, canvasTransform.scale * 1.2); }
function zoomOut()  { canvasTransform.scale = Math.max(0.25, canvasTransform.scale / 1.2); }
function resetView(){ canvasTransform.x = 0; canvasTransform.y = 0; canvasTransform.scale = 1; }

// ─── Step CRUD ───────────────────────────────────────────────────────────────

function openAddStep() {
    showAddStep.value = true;
    selectedStepId.value = null;
    // Reset personnel search
    personnelSearch.value = '';
    personnelDirectInput.value = '';
    // Set editing step directly - no null intermediate state that would unmount panel
    editingStep.value = {
        name: '',
        role_key: '',
        roles: [],
        approvable_modules: [],
        personnel_ids: [],
        visibility_config: [],
        approval_config: { mode: 'any' },
        signature_config: null,
        action_type: 'onay',
        is_active: true,
        canvas_x: 100 + steps.value.length * 40,
        canvas_y: 100 + steps.value.length * 40,
    };
}

function closeAddStep() {
    showAddStep.value = false;
    editingStep.value = null;
}

function saveStep() {
    if (!editingStep.value) return;
    const isNew = editingStep.value.id == null;

    if (isNew) {
        // Create via API
        router.post(route('admin.processes.store-step'), {
            process_definition_id: props.process.id,
            name: editingStep.value.name,
            role_key: editingStep.value.role_key || null,
            roles: editingStep.value.roles || [],
            approvable_modules: editingStep.value.approvable_modules || [],
            module_permissions: editingStep.value.module_permissions || null,
            personnel_ids: editingStep.value.personnel_ids || [],
            visibility_config: editingStep.value.visibility_config || [],
            approval_config: editingStep.value.approval_config || { mode: 'any' },
            signature_config: editingStep.value.signature_config || null,
            action_type: editingStep.value.action_type || 'onay',
            is_active: editingStep.value.is_active ? 1 : 0,
            canvas_x: Math.round(editingStep.value.canvas_x),
            canvas_y: Math.round(editingStep.value.canvas_y),
            step_order: steps.value.length,
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                const newStep = page.props.flash?.new_step;
                if (newStep) {
                    const savedStep = { ...editingStep.value, id: newStep.id, step_order: newStep.step_order };
                    steps.value.push(savedStep);
                    // Select the new step in sidebar after redirect/reload
                    selectedStepId.value = newStep.id;
                    editingStep.value = { ...savedStep };
                }
                // Close add form - after redirect page reloads and state resets
                showAddStep.value = false;
            }
        });
    } else {
        // Update via API
        router.put(route('admin.processes.update-step', editingStep.value.id), {
            name: editingStep.value.name,
            role_key: editingStep.value.role_key || null,
            roles: editingStep.value.roles || [],
            approvable_modules: editingStep.value.approvable_modules || [],
            module_permissions: editingStep.value.module_permissions || null,
            personnel_ids: editingStep.value.personnel_ids || [],
            visibility_config: editingStep.value.visibility_config || [],
            approval_config: editingStep.value.approval_config || { mode: 'any' },
            signature_config: editingStep.value.signature_config || null,
            action_type: editingStep.value.action_type || 'onay',
            is_active: editingStep.value.is_active ? 1 : 0,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                const idx = steps.value.findIndex(s => s.id === editingStep.value.id);
                if (idx !== -1) {
                    steps.value[idx] = { ...steps.value[idx], ...editingStep.value };
                }
                // Keep edit panel open after save
                showAddStep.value = false;
            }
        });
    }
}

function deleteStep() {
    if (!editingStep.value?.id) return;
    if (!confirm('Bu adımı silmek istediğinize emin misiniz?')) return;
    router.delete(route('admin.processes.destroy-step', editingStep.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            steps.value = steps.value.filter(s => s.id !== editingStep.value.id);
            connections.value = connections.value.filter(
                c => c.from_step_id !== editingStep.value.id && c.to_step_id !== editingStep.value.id
            );
            closeAddStep();
            selectedStepId.value = null;
        }
    });
}

// ─── Save Canvas State ───────────────────────────────────────────────────────

const saving = ref(false);

function saveCanvas() {
    saving.value = true;
    router.post(route('admin.processes.save-canvas', props.process.id), {
        steps: steps.value.map(s => ({ id: s.id, canvas_x: Math.round(s.canvas_x), canvas_y: Math.round(s.canvas_y) })),
        connections: connections.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { saving.value = false; },
        onError: () => { saving.value = false; }
    });
}

function publishWorkflow() {
    if (!confirm('Bu iş akışını yayınlamak üzeresiniz. Yayınlandıktan sonra yeni bir versiyon oluşur. Devam edilsin mi?')) return;
    router.post(route('admin.processes.publish', props.process.id), {}, {
        preserveScroll: true,
    });
}

// ─── Process Name Edit ────────────────────────────────────────────────────────

function startEditProcessName() {
    processNameInput.value = props.process?.name || '';
    isEditingProcessName.value = true;
}

function saveProcessName() {
    if (!processNameInput.value.trim()) {
        alert('Süreç adı boş olamaz.');
        return;
    }
    if (processNameInput.value.trim() === props.process?.name) {
        isEditingProcessName.value = false;
        return;
    }
    savingProcessName.value = true;
    router.put(route('admin.processes.update-definition', props.process.id), {
        name: processNameInput.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            savingProcessName.value = false;
            isEditingProcessName.value = false;
        },
        onError: () => {
            savingProcessName.value = false;
        }
    });
}

// ─── Connection Delete ────────────────────────────────────────────────────────

function deleteConnection(conn) {
    connections.value = connections.value.filter(c => c !== conn);
}

// ─── Mobile/Tablet Check ───────────────────────────────────────────────────────

function checkMobile() {
    isMobile.value = window.innerWidth < 768;
    isTablet.value = window.innerWidth >= 768 && window.innerWidth < 1024;
    // Auto-close sidebar on mobile/tablet
    if (isMobile.value || isTablet.value) {
        isSidebarOpen.value = false;
    }
    // Update node dimensions for responsive layout
    updateNodeDimensions();
}

function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
}

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
    document.addEventListener('mousemove', onCanvasMouseMove);
    document.addEventListener('mouseup', onCanvasMouseUp);

    // Ensure clean state on mount - clear any stale data from previous session
    selectedStepId.value = null;
    editingStep.value = null;
    showAddStep.value = false;
    personnelSearch.value = '';
    personnelDirectInput.value = '';

    // Auto-layout steps in horizontal row when page loads
    nextTick(() => autoLayoutSteps());
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
    document.removeEventListener('mousemove', onCanvasMouseMove);
    document.removeEventListener('mouseup', onCanvasMouseUp);
});

function togglePersonnel(userId) {
    const arr = editingStep.value.personnel_ids || [];
    const idx = arr.indexOf(userId);
    if (idx === -1) arr.push(userId);
    else arr.splice(idx, 1);
    editingStep.value.personnel_ids = [...arr];
    // Auto-derive roles from personnel
    autoDeriveRolesFromPersonnel();
}

function toggleModule(key) {
    const arr = editingStep.value.visibility_config || [];
    const idx = arr.indexOf(key);
    if (idx === -1) arr.push(key);
    else arr.splice(idx, 1);
    editingStep.value.visibility_config = [...arr];
}

function toggleApproverRole(key) {
    const arr = editingStep.value.roles || [];
    const idx = arr.indexOf(key);
    if (idx === -1) arr.push(key);
    else arr.splice(idx, 1);
    editingStep.value.roles = [...arr];
}

function toggleApprovableModule(key) {
    const arr = editingStep.value.approvable_modules || [];
    const idx = arr.indexOf(key);
    if (idx === -1) arr.push(key);
    else arr.splice(idx, 1);
    editingStep.value.approvable_modules = [...arr];
}

// ─── Step Reorder ─────────────────────────────────────────────────────────────

function reorderStep(step, direction) {
    router.post(route('admin.processes.reorder-step', { step: step.id, direction }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            nextTick(() => autoLayoutSteps());
        }
    });
}
</script>

<template>
    <Head title="Süreç Editörü — AYKOME" />

    <!-- Full-screen app shell -->
    <div class="flex h-screen flex-col overflow-hidden bg-slate-100">

        <!-- ── Toolbar ──────────────────────────────────────────────────────── -->
        <header class="flex flex-shrink-0 items-center justify-between border-b border-slate-200 bg-white px-2 sm:px-4 py-2 shadow-sm z-50 gap-1 sm:gap-3">
            <div class="flex items-center gap-1 sm:gap-3 min-w-0">
                <!-- Sidebar toggle for mobile/tablet -->
                <button @click="toggleSidebar" class="lg:hidden flex-shrink-0 rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">
                    ☰
                </button>
                <Link href="/admin" class="flex items-center gap-1.5 text-[10px] sm:text-xs font-bold text-slate-500 hover:text-slate-800 flex-shrink-0">
                    <span>←</span> <span class="hidden sm:inline">Panel</span>
                </Link>
                <span class="text-slate-300 hidden sm:inline">|</span>
                <!-- Process name - editable -->
                <div v-if="isEditingProcessName" class="flex items-center gap-1 flex-shrink-0">
                    <input
                        v-model="processNameInput"
                        @keydown.enter="saveProcessName"
                        @keydown.escape="isEditingProcessName = false"
                        type="text"
                        class="text-[10px] sm:text-sm font-bold text-slate-800 border border-cyan-400 rounded px-1 py-0.5 w-32 sm:w-48 bg-white focus:outline-none focus:ring-1 focus:ring-cyan-500"
                        autofocus
                    />
                    <button
                        @click="saveProcessName"
                        :disabled="savingProcessName"
                        class="text-emerald-600 hover:text-emerald-700 disabled:opacity-50 text-xs font-bold"
                        title="Kaydet"
                    >
                        ✓
                    </button>
                    <button
                        @click="isEditingProcessName = false"
                        class="text-red-500 hover:text-red-600 text-xs"
                        title="İptal"
                    >
                        ✕
                    </button>
                </div>
                <h1
                v-else
                    @click="startEditProcessName"
                    class="text-[10px] sm:text-sm font-bold text-slate-800 truncate cursor-pointer hover:text-cyan-600 flex-shrink-0"
                    title="Adı değiştirmek için tıklayın"
                >
                    ⚙️ {{ process?.name }}
                </h1>
                <span v-if="process?.is_default" class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] sm:text-[10px] font-bold text-emerald-700 flex-shrink-0">Varsayılan</span>
                <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[9px] sm:text-[10px] font-bold uppercase text-slate-500 flex-shrink-0">v{{ process?.version ?? 1 }}</span>
            </div>

            <div class="flex items-center gap-1 sm:gap-2 flex-wrap sm:flex-nowrap">
                <!-- Zoom controls -->
                <button @click="zoomOut" class="rounded-lg border border-slate-200 px-1.5 sm:px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">−</button>
                <span class="text-[10px] sm:text-xs text-slate-500 w-10 sm:w-12 text-center">{{ Math.round(canvasTransform.scale * 100) }}%</span>
                <button @click="zoomIn" class="rounded-lg border border-slate-200 px-1.5 sm:px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">+</button>
                <button @click="fitCanvas" class="hidden sm:inline rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">Fit</button>
                <button @click="resetView" class="rounded-lg border border-slate-200 px-1.5 sm:px-2 py-1 text-[10px] sm:text-xs text-slate-600 hover:bg-slate-50">1:1</button>
                <button @click="autoLayoutSteps" class="hidden md:inline rounded-lg border border-violet-200 px-2 py-1 text-xs font-semibold text-violet-600 hover:bg-violet-50">📐 Layout</button>

                <span class="text-slate-300 hidden sm:inline">|</span>

                <!-- Add step -->
                <button @click="openAddStep" class="rounded-lg bg-cyan-600 px-2 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold text-white hover:bg-cyan-700 whitespace-nowrap">
                    ＋ Adım Ekle
                </button>

                <!-- Save -->
                <button @click="saveCanvas" :disabled="saving" class="rounded-lg border border-slate-300 px-2 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50 whitespace-nowrap">
                    {{ saving ? '...' : '💾' }} <span class="hidden sm:inline">{{ saving ? 'Kaydediliyor…' : 'Kaydet' }}</span>
                </button>

                <!-- Publish -->
                <button @click="publishWorkflow" class="rounded-lg bg-emerald-600 px-2 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold text-white hover:bg-emerald-700 whitespace-nowrap">
                    🚀 <span class="hidden sm:inline">Yayınla</span>
                </button>
            </div>
        </header>

        <!-- ── Body ─────────────────────────────────────────────────────────── -->
        <div class="flex flex-1 overflow-hidden">

            <!-- Mobile backdrop overlay -->
            <div
                v-if="isSidebarOpen && (isMobile || isTablet)"
                class="fixed inset-0 bg-black/30 z-30 md:hidden"
                @click="isSidebarOpen = false"
            ></div>

            <!-- ── Left Sidebar: Steps List ──────────────────────────────────── -->
            <aside
                :class="[
                    'flex-shrink-0 overflow-y-auto border-r border-slate-200 bg-white transition-all duration-200',
                    // Desktop: always visible with proper width
                    'w-64 lg:relative',
                    // Mobile/tablet: hidden by default
                    (isMobile || isTablet) ? 'hidden' : '',
                    // Mobile/tablet when open: fixed overlay
                    (isMobile || isTablet) && isSidebarOpen ? '!hidden fixed inset-y-0 left-0 top-[52px] z-40' : '',
                    // Mobile/tablet width when open
                    isMobile ? 'w-64' : '',
                    isTablet && isSidebarOpen ? 'w-48' : '',
                ]"
            >
                <div class="p-2 sm:p-3 border-b border-slate-100 min-w-[200px]">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500">Süreç Adımları</h2>
                            <p class="mt-0.5 text-[9px] sm:text-[10px] text-slate-400">{{ steps.length }} adım · {{ connections.length }} bağlantı</p>
                        </div>
                        <button @click="isSidebarOpen = false" class="md:hidden p-1 text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                </div>

                <div class="p-1 sm:p-2 space-y-2 min-w-[200px]">
                    <div
                        v-for="(step, idx) in steps"
                        :key="step.id"
                        class="rounded-xl border-2 shadow-sm overflow-hidden transition-all"
                        :class="[
                            selectedStepId === step.id
                                ? 'border-cyan-400 bg-cyan-50 shadow-cyan-100'
                                : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-md'
                        ]"
                    >
                        <!-- Step card clickable area -->
                        <div
                            @click="selectedStepId = step.id; editingStep = { ...step }; showAddStep = false; if (isMobile || isTablet) isSidebarOpen = false"
                            class="cursor-pointer px-2 sm:px-3 py-2 flex items-start gap-2"
                        >
                            <!-- Reorder buttons -->
                            <div class="flex flex-col gap-0.5 flex-shrink-0 mt-0.5" @click.stop>
                                <button @click="reorderStep(step, 'up')" class="text-slate-300 hover:text-cyan-600 text-[8px] leading-none p-0.5 rounded hover:bg-cyan-50 transition-colors" title="Yukarı Taşı">▲</button>
                                <button @click="reorderStep(step, 'down')" class="text-slate-300 hover:text-cyan-600 text-[8px] leading-none p-0.5 rounded hover:bg-cyan-50 transition-colors" title="Aşağı Taşı">▼</button>
                            </div>
                            <!-- Header row with icon and name -->
                            <div class="flex items-center gap-2 mb-1.5">
                                <span :class="['flex h-6 w-6 sm:h-7 sm:w-7 flex-shrink-0 items-center justify-center rounded-full text-[11px] sm:text-xs font-bold text-white shadow-sm', getStepColor(idx).bg]">
                                    {{ getStepIcon(idx) }}
                                </span>
                                <span class="flex-1 truncate font-bold text-slate-800 text-[11px] sm:text-sm">{{ step.name }}</span>
                                <span v-if="!step.is_active" class="rounded bg-slate-100 px-1.5 py-0.5 text-[8px] sm:text-[9px] font-bold text-slate-500">PASİF</span>
                            </div>
                            <!-- Footer row with badges -->
                            <div class="flex flex-wrap gap-1">
                                <span :class="['rounded px-1.5 py-0.5 text-[8px] sm:text-[9px] font-semibold', getStepColor(idx).bg.replace('bg-gradient-to-br from-', 'bg-opacity-20 text-').replace(' to-', '/')]">{{ step.role_key || '—' }}</span>
                                <span
                                    v-for="mod in (step.approvable_modules || [])"
                                    :key="mod"
                                    class="rounded bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 text-[8px] sm:text-[9px] font-semibold text-emerald-700"
                                >{{ moduleOptions[mod] || mod }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!steps.length" class="py-6 sm:py-8 text-center text-[10px] sm:text-xs text-slate-400">
                        Henüz adım yok.<br>「Adım Ekle」ile başlayın.
                    </div>
                </div>
            </aside>

            <!-- ── Canvas ─────────────────────────────────────────────────────── -->
            <div
                ref="canvasEl"
                class="canvas-bg relative flex-1 overflow-hidden"
                :class="isDraggingCanvas ? 'cursor-grabbing' : 'cursor-grab'"
                @mousedown="onCanvasMouseDown"
                @wheel.prevent="onWheel"
                :style="{
                    backgroundImage: 'radial-gradient(circle, #cbd5e1 1px, transparent 1px)',
                    backgroundSize: `${20 * canvasTransform.scale}px ${20 * canvasTransform.scale}px`,
                    backgroundPosition: `${canvasTransform.x}px ${canvasTransform.y}px`
                }"
            >
                <!-- SVG for connections -->
                <svg
                    ref="svgEl"
                    class="pointer-events-none absolute inset-0"
                    style="overflow: visible;"
                >
                    <!-- Existing connections -->
                    <g v-for="conn in connections" :key="`${conn.from_step_id}-${conn.to_step_id}`">
                        <path
                            v-if="getStepById(conn.from_step_id) && getStepById(conn.to_step_id)"
                            :d="bezierPath(
                                getSocketScreenPos(getStepById(conn.from_step_id), true).x,
                                getSocketScreenPos(getStepById(conn.from_step_id), true).y,
                                getSocketScreenPos(getStepById(conn.to_step_id), false).x,
                                getSocketScreenPos(getStepById(conn.to_step_id), false).y
                            )"
                            stroke="#64748b"
                            stroke-width="2.5"
                            fill="none"
                            stroke-linecap="round"
                            class="pointer-events-auto cursor-pointer hover:stroke-cyan-500"
                            @click="deleteConnection(conn)"
                        />
                        <!-- Arrow head -->
                        <circle
                            v-if="getStepById(conn.to_step_id)"
                            :cx="getSocketScreenPos(getStepById(conn.to_step_id), false).x"
                            :cy="getSocketScreenPos(getStepById(conn.to_step_id), false).y"
                            r="4"
                            fill="#64748b"
                            class="pointer-events-auto cursor-pointer hover:fill-cyan-500"
                            @click="deleteConnection(conn)"
                        />
                    </g>

                    <!-- In-progress connection line -->
                    <path
                        v-if="isConnecting"
                        :d="bezierPath(connectingLine.x1, connectingLine.y1, connectingLine.x2, connectingLine.y2)"
                        stroke="#0ea5e9"
                        stroke-width="2.5"
                        fill="none"
                        stroke-dasharray="6 4"
                        stroke-linecap="round"
                        style="pointer-events: none;"
                    />
                </svg>

                <!-- Node cards - each step in its own container -->
                <div
                    v-for="(step, idx) in steps"
                    :key="step.id"
                    class="absolute rounded-2xl border-2 shadow-lg"
                    :class="[
                        selectedStepId === step.id ? 'border-cyan-400 shadow-cyan-200 ring-4 ring-cyan-100' : 'border-slate-300',
                        !step.is_active ? 'opacity-70' : ''
                    ]"
                    :style="{
                        left: (canvasTransform.x + step.canvas_x * canvasTransform.scale) + 'px',
                        top:  (canvasTransform.y + step.canvas_y * canvasTransform.scale) + 'px',
                        width: (NODE_WIDTH * canvasTransform.scale) + 'px',
                        height: (NODE_HEIGHT * canvasTransform.scale) + 'px',
                    }"
                    @mousedown.stop="onNodeMouseDown($event, step)"
                    @click.stop="onNodeClick($event, step)"
                >
                    <!-- Step container with background -->
                    <div class="w-full h-full flex flex-col bg-slate-50 rounded-2xl">
                        <!-- Inner white card -->
                        <div class="flex-1 flex flex-col bg-white rounded-xl overflow-hidden">
                            <!-- Node header with icon badge integrated -->
                            <div :class="['flex items-center gap-2 rounded-t-xl border-b border-slate-100 px-3 py-2 pr-4', getStepColor(idx).bg]">
                                <!-- Icon badge -->
                                <div :class="['flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold text-white shadow-sm', getStepColor(idx).bg]">
                                    {{ getStepIcon(idx) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-white/80">Adım {{ idx + 1 }}</span>
                                        <span v-if="!step.is_active" class="rounded bg-slate-200/80 px-1.5 py-0.5 text-[8px] font-bold text-slate-600">PASİF</span>
                                    </div>
                                    <span class="block text-xs font-bold text-white truncate">{{ step.name }}</span>
                                </div>
                                <span class="rounded bg-white/20 px-2 py-0.5 text-[9px] font-bold text-white whitespace-nowrap">{{ step.role_key || '—' }}</span>
                            </div>

                            <!-- Node body -->
                            <div class="flex-1 px-3 py-2 text-[11px] overflow-hidden">
                                <div v-if="step.personnel_ids?.length" class="flex items-center gap-1 text-slate-600 mb-1">
                                    <span>👥</span>
                                    <span class="truncate">{{ step.personnel_ids.length }} personel atanmış</span>
                                </div>
                                <div v-if="step.visibility_config?.length" class="flex flex-wrap gap-1">
                                    <span
                                        v-for="mod in step.visibility_config"
                                        :key="mod"
                                        class="rounded bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700"
                                    >📋 {{ moduleOptions[mod] || mod }}</span>
                                </div>
                                <div v-else class="text-slate-400">Modül görünürlüğü ayarlanmamış</div>

                                <!-- Action type indicator -->
                                <div class="flex items-center gap-1 mt-1" :class="step.action_type === 'e_imza' ? 'text-amber-600' : step.action_type === 'paraf' ? 'text-blue-600' : 'text-emerald-600'">
                                    <span v-if="step.action_type === 'onay'">✅</span>
                                    <span v-else-if="step.action_type === 'paraf'">✍️</span>
                                    <span v-else>🖊️</span>
                                    <span class="text-[9px] font-semibold">
                                        {{ step.action_type === 'onay' ? 'Onay' : step.action_type === 'paraf' ? 'Paraf' : 'E-İmza' }}
                                    </span>
                                    <span v-if="step.action_type === 'e_imza' && step.signature_config?.pdf_type" class="text-[9px] opacity-70">{{ step.signature_config?.pdf_type }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Socket anchors -->
                    <!-- Input socket (left) -->
                    <div
                        class="socket-input absolute -left-1.5 top-1/2 -translate-y-1/2 w-3 h-3 rounded-full border-2 border-white bg-slate-400 hover:bg-cyan-400 hover:scale-125 transition-all cursor-crosshair z-10"
                        @mousedown.stop="onSocketMouseDown($event, step.id, false)"
                        @mouseup.stop="onSocketMouseUp($event, step.id)"
                    ></div>

                    <!-- Output socket (right) -->
                    <div
                        class="socket-output absolute -right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 rounded-full border-2 border-white bg-slate-400 hover:bg-cyan-400 hover:scale-125 transition-all cursor-crosshair z-10"
                        @mousedown.stop="onSocketMouseDown($event, step.id, true)"
                        @mouseup.stop="onSocketMouseUp($event, step.id)"
                    ></div>
                </div>

                <!-- Empty state -->
                <div v-if="!steps.length" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="text-center">
                        <p class="text-5xl mb-3">⚙️</p>
                        <p class="text-sm font-semibold text-slate-500">Henüz adım yok</p>
                        <p class="text-xs text-slate-400">「Adım Ekle」butonuna tıklayın</p>
                    </div>
                </div>
            </div>

            <!-- ── Right Property Panel ───────────────────────────────────────── -->
            <aside
                v-if="editingStep"
                :class="[
                    'flex-shrink-0 overflow-y-auto border-l border-slate-200 bg-white transition-all duration-200',
                    // Responsive widths: mobile full, tablet 2/3, desktop fixed
                    'w-full sm:w-[400px] md:w-72 lg:w-80'
                ]"
            >
                <div class="sticky top-0">
                    <!-- Add step form -->
                    <div v-if="showAddStep" class="p-4 space-y-4">
                        <h2 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">＋ Yeni Adım</h2>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Adı *</label>
                            <input v-model="editingStep.name" type="text" placeholder="örn. Fen İşleri Müdürü Onayı" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
                        </div>

                        <!-- Action Type -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">📌 İşlem Türü</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="opt in actionTypeOptions"
                                    :key="opt.value"
                                    type="button"
                                    @click="actionType = opt.value"
                                    :class="[
                                        'flex flex-col items-center rounded-lg border-2 px-2 py-2 text-xs font-semibold transition-all',
                                        actionType === opt.value
                                            ? opt.value === 'onay' ? 'border-emerald-400 bg-emerald-50 text-emerald-800' :
                                              opt.value === 'paraf' ? 'border-blue-400 bg-blue-50 text-blue-800' :
                                              'border-amber-400 bg-amber-50 text-amber-800'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                    ]"
                                >
                                    <span class="text-base mb-0.5">{{ opt.label.split(' ')[0] }}</span>
                                    <span>{{ opt.label.split(' ').slice(1).join(' ') }}</span>
                                    <span class="text-[9px] font-normal opacity-70 mt-0.5">{{ opt.description }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Personnel -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">👥 Personel Atama</label>

                            <!-- Selected personnel badges -->
                            <div v-if="(editingStep.personnel_ids || []).length" class="mb-2 flex flex-wrap gap-1">
                                <span
                                    v-for="uid in editingStep.personnel_ids"
                                    :key="uid"
                                    class="inline-flex items-center gap-1 rounded-full bg-violet-100 border border-violet-200 px-2 py-0.5 text-[10px] font-bold text-violet-700"
                                >
                                    {{ personnelOptions.find(u => u.id === uid)?.name || 'ID:' + uid }}
                                    <button @click.stop="removePersonnel(uid)" class="ml-0.5 hover:text-rose-600">✕</button>
                                </span>
                            </div>

                            <!-- Search -->
                            <div class="mb-2">
                                <input
                                    v-model="personnelSearch"
                                    type="text"
                                    placeholder="🔍 Personel ara..."
                                    class="w-full rounded-lg border border-slate-300 px-2 py-1 text-[11px] focus:border-cyan-400"
                                >
                            </div>

                            <!-- Personnel list -->
                            <div class="max-h-36 overflow-y-auto space-y-1 border border-slate-200 rounded-lg p-2 bg-slate-50">
                                <label
                                    v-for="user in filteredPersonnel"
                                    :key="user.id"
                                    :class="[
                                        'flex cursor-pointer items-center gap-2 rounded-lg border px-2 py-1.5 text-xs transition-all',
                                        (editingStep.personnel_ids || []).includes(user.id)
                                            ? 'border-violet-400 bg-violet-50 text-violet-800'
                                            : 'border-transparent bg-white text-slate-600 hover:border-slate-300'
                                    ]"
                                >
                                    <input type="checkbox" :value="user.id" v-model="editingStep.personnel_ids" class="hidden">
                                    <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-slate-800 text-white text-[9px] font-bold">
                                        {{ user.name.charAt(0) }}
                                    </span>
                                    <span class="flex-1 truncate">{{ user.name }}</span>
                                    <span v-if="(editingStep.personnel_ids || []).includes(user.id)" class="text-violet-600">✓</span>
                                </label>
                                <div v-if="!filteredPersonnel.length" class="py-2 text-center text-[11px] text-slate-400">
                                    Sonuç bulunamadı
                                </div>
                            </div>
                        </div>

                        <!-- Visibility -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Modül Görünürlüğü (Bu adımda hangi modüller görünür?)</label>
                            <div class="flex flex-wrap gap-1.5">
                                <label
                                    v-for="(label, key) in moduleOptions"
                                    :key="key"
                                    :class="[
                                        'cursor-pointer rounded-lg border px-2 py-1 text-xs font-medium transition-all',
                                        (editingStep.visibility_config || []).includes(key)
                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                                            : 'border-slate-200 bg-white text-slate-600'
                                    ]"
                                >
                                    <input type="checkbox" :value="key" v-model="editingStep.visibility_config" class="hidden">
                                    📋 {{ label }}
                                </label>
                            </div>
                        </div>

                        <!-- Approvable modules -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Onay Yetkisi (Bu adımda hangi modüller onaylanabilir?)</label>
                            <div class="flex flex-wrap gap-1.5">
                                <label
                                    v-for="(label, key) in moduleOptions"
                                    :key="key"
                                    :class="[
                                        'cursor-pointer rounded-lg border px-2 py-1 text-xs font-medium transition-all',
                                        (editingStep.approvable_modules || []).includes(key)
                                            ? 'border-amber-300 bg-amber-50 text-amber-800'
                                            : 'border-slate-200 bg-white text-slate-600'
                                    ]"
                                >
                                    <input type="checkbox" :value="key" v-model="editingStep.approvable_modules" class="hidden">
                                    ✅ {{ label }}
                                </label>
                            </div>
                        </div>

                        <!-- Active -->
                        <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600">
                            <input type="checkbox" v-model="editingStep.is_active" class="rounded border-slate-300 text-cyan-600">
                            Aktif adım
                        </label>

                        <!-- E-İmza Yetkisi -->
                        <div class="border-t border-amber-200 pt-3 mt-3">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-amber-500 text-sm">🖊️</span>
                                <label class="text-[11px] font-bold text-amber-700">E-İmza Yetkisi</label>
                            </div>

                            <!-- Enable toggle -->
                            <label class="flex items-center gap-2 text-xs font-medium text-slate-600 mb-2">
                                <input type="checkbox" v-model="signatureEnabled" class="rounded border-slate-300 text-amber-600">
                                Bu adımda e-imza gerekli
                            </label>

                            <!-- Signature options (visible when enabled) -->
                            <div v-if="signatureEnabled" class="space-y-2 pl-2 border-l-2 border-amber-200">

                                <!-- PDF Type -->
                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-500">İmzalanacak Belge</label>
                                    <select v-model="signaturePdfType" class="w-full rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                        <option v-for="opt in pdfTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                </div>

                                <!-- Signer Personnel -->
                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-500">🖊️ İmza Yetkilileri</label>
                                    <div class="max-h-32 overflow-y-auto space-y-1 border border-slate-200 rounded-lg p-2 bg-slate-50">
                                        <label
                                            v-for="user in filteredPersonnel"
                                            :key="user.id"
                                            :class="[
                                                'flex cursor-pointer items-center gap-2 rounded-lg border px-2 py-1 text-xs transition-all',
                                                (signatureSignerIds || []).includes(user.id)
                                                    ? 'border-amber-400 bg-amber-50 text-amber-800'
                                                    : 'border-transparent bg-white text-slate-600 hover:border-slate-300'
                                            ]"
                                        >
                                            <input type="checkbox" :value="user.id" v-model="signatureSignerIds" class="hidden">
                                            <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-amber-600 text-white text-[9px] font-bold">
                                                {{ user.name.charAt(0) }}
                                            </span>
                                            <span class="flex-1 truncate">{{ user.name }}</span>
                                            <span v-if="(signatureSignerIds || []).includes(user.id)" class="text-amber-600">✓</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Info box -->
                                <div class="rounded-lg bg-amber-50 border border-amber-200 p-2 text-[10px] text-amber-700">
                                    Bu adımda sadece seçili yetkililer e-imza atabilir. Onay yetkisi (roller) ayrıca tanımlanır.
                                </div>
                            </div>
                        </div>

                        <!-- Modül Yetkileri -->
                        <div class="border-t border-indigo-200 pt-3 mt-3">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-indigo-500 text-sm">🎛️</span>
                                <label class="text-[11px] font-bold text-indigo-700">Modül Bazlı Yetkiler</label>
                            </div>

                            <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
                                <div
                                    v-for="mod in modulePermissionModules"
                                    :key="mod.key"
                                    class="rounded-lg border border-slate-200 overflow-hidden"
                                >
                                    <!-- Module header row -->
                                    <div
                                        @click="toggleExpandedModule(mod.key)"
                                        class="flex items-center justify-between px-3 py-2 cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors"
                                        :class="mod.hasCustom ? 'border-l-4 border-indigo-400' : ''"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span :class="mod.hasCustom ? 'text-indigo-600' : 'text-slate-400'">{{ mod.hasCustom ? '●' : '○' }}</span>
                                            <span class="text-xs font-semibold text-slate-700">{{ mod.label }}</span>
                                            <span v-if="mod.hasCustom" class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded font-bold">ÖZEL</span>
                                        </div>
                                        <span class="text-slate-400 text-xs">{{ expandedModule === mod.key ? '▲' : '▼' }}</span>
                                    </div>

                                    <!-- Expanded config -->
                                    <div v-if="expandedModule === mod.key" class="px-3 py-2 space-y-2 bg-white border-t border-slate-100 max-h-40 overflow-y-auto">

                                        <!-- Action Type per module -->
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold text-slate-500">İşlem Türü (Bu modül için)</label>
                                            <div class="grid grid-cols-3 gap-1">
                                                <button
                                                    v-for="opt in actionTypeOptions"
                                                    :key="opt.value"
                                                    type="button"
                                                    @click="setModuleActionType(mod.key, opt.value)"
                                                    class="flex items-center justify-center gap-1 rounded border py-1 px-2 text-[10px] font-medium transition-all"
                                                    :class="getModulePerm(mod.key).action_type === opt.value
                                                        ? opt.value === 'onay' ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
                                                        : opt.value === 'paraf' ? 'border-blue-400 bg-blue-50 text-blue-800'
                                                        : 'border-amber-400 bg-amber-50 text-amber-800'
                                                        : 'border-slate-200 bg-white text-slate-500'"
                                                >
                                                    {{ opt.label.split(' ')[0] }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Approver Roles -->
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold text-slate-500">Onay Rolleri</label>
                                            <div class="flex flex-wrap gap-1">
                                                <label
                                                    v-for="(rLabel, rKey) in roleOptions"
                                                    :key="rKey"
                                                    :class="[
                                                        'cursor-pointer rounded border px-1.5 py-0.5 text-[10px] font-medium transition-all cursor-pointer',
                                                        (getModulePerm(mod.key).approver_roles || []).includes(rKey)
                                                            ? 'border-violet-400 bg-violet-50 text-violet-800'
                                                            : 'border-slate-200 bg-white text-slate-500'
                                                    ]"
                                                >
                                                    <input type="checkbox" :value="rKey" class="hidden" @change="toggleModuleApproverRole(mod.key, rKey)" :checked="(getModulePerm(mod.key).approver_roles || []).includes(rKey)">
                                                    {{ rLabel }}
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Signer Personnel -->
                                        <div v-if="getModulePerm(mod.key).action_type === 'e_imza'">
                                            <label class="mb-1 block text-[10px] font-bold text-slate-500">🖊️ E-İmza Yetkilileri</label>
                                            <div class="max-h-24 overflow-y-auto space-y-1 border border-slate-200 rounded-lg p-2 bg-slate-50">
                                                <label
                                                    v-for="user in filteredPersonnel"
                                                    :key="user.id"
                                                    :class="[
                                                        'flex cursor-pointer items-center gap-2 rounded border px-2 py-1 text-[10px] transition-all',
                                                        (getModulePerm(mod.key).signer_ids || []).includes(user.id)
                                                            ? 'border-amber-400 bg-amber-50 text-amber-800'
                                                            : 'border-transparent bg-white text-slate-600'
                                                    ]"
                                                >
                                                    <input type="checkbox" :value="user.id" class="hidden" @change="toggleModuleSignerId(mod.key, user.id)" :checked="(getModulePerm(mod.key).signer_ids || []).includes(user.id)">
                                                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-slate-700 text-white text-[8px] font-bold">{{ user.name.charAt(0) }}</span>
                                                    <span class="flex-1 truncate">{{ user.name }}</span>
                                                    <span v-if="(getModulePerm(mod.key).signer_ids || []).includes(user.id)" class="text-amber-600">✓</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Visible to Roles -->
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold text-slate-500">Görünür Roller</label>
                                            <div class="flex flex-wrap gap-1">
                                                <label
                                                    v-for="(rLabel, rKey) in roleOptions"
                                                    :key="rKey"
                                                    :class="[
                                                        'cursor-pointer rounded border px-1.5 py-0.5 text-[10px] font-medium transition-all',
                                                        (getModulePerm(mod.key).visible_to_roles || []).includes(rKey)
                                                            ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
                                                            : 'border-slate-200 bg-white text-slate-500'
                                                    ]"
                                                >
                                                    <input type="checkbox" :value="rKey" class="hidden" @change="toggleModuleVisibleRole(mod.key, rKey)" :checked="(getModulePerm(mod.key).visible_to_roles || []).includes(rKey)">
                                                    {{ rLabel }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-slate-100">
                            <button @click="closeAddStep" class="flex-1 rounded-lg border border-slate-300 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">İptal</button>
                            <button @click="saveStep" class="flex-1 rounded-lg bg-slate-900 py-2 text-xs font-bold text-white hover:bg-slate-700">Kaydet</button>
                        </div>
                    </div>

                    <!-- Existing step edit -->
                    <div v-else class="p-4 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <h2 class="text-sm font-bold text-slate-800">✏️ Adımı Düzenle</h2>
                            <button @click="selectedStepId = null; editingStep = null" class="text-slate-400 hover:text-slate-600">✕</button>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Adı</label>
                            <input v-model="editingStep.name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
                        </div>

                        <!-- Action Type -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">📌 İşlem Türü</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="opt in actionTypeOptions"
                                    :key="opt.value"
                                    type="button"
                                    @click="actionType = opt.value"
                                    :class="[
                                        'flex flex-col items-center rounded-lg border-2 px-2 py-2 text-xs font-semibold transition-all',
                                        actionType === opt.value
                                            ? opt.value === 'onay' ? 'border-emerald-400 bg-emerald-50 text-emerald-800' :
                                              opt.value === 'paraf' ? 'border-blue-400 bg-blue-50 text-blue-800' :
                                              'border-amber-400 bg-amber-50 text-amber-800'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                    ]"
                                >
                                    <span class="text-base mb-0.5">{{ opt.label.split(' ')[0] }}</span>
                                    <span>{{ opt.label.split(' ').slice(1).join(' ') }}</span>
                                    <span class="text-[9px] font-normal opacity-70 mt-0.5">{{ opt.description }}</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">role_key</label>
                            <input v-model="editingStep.role_key" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
                        </div>

                        <!-- Roles -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Yetkili Roller</label>
                            <div class="flex flex-wrap gap-1.5">
                                <label
                                    v-for="(label, key) in roleOptions"
                                    :key="key"
                                    :class="[
                                        'cursor-pointer rounded-lg border px-2 py-1 text-xs font-medium transition-all',
                                        (editingStep.roles || []).includes(key)
                                            ? 'border-violet-300 bg-violet-50 text-violet-800'
                                            : 'border-slate-200 bg-white text-slate-600'
                                    ]"
                                >
                                    <input type="checkbox" :value="key" v-model="editingStep.roles" class="hidden">
                                    {{ label }}
                                </label>
                            </div>
                        </div>

                        <!-- Personnel -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">👥 Atanmış Personel</label>

                            <!-- Selected personnel badges -->
                            <div v-if="(editingStep.personnel_ids || []).length" class="mb-2 flex flex-wrap gap-1">
                                <span
                                    v-for="uid in editingStep.personnel_ids"
                                    :key="uid"
                                    class="inline-flex items-center gap-1 rounded-full bg-violet-100 border border-violet-200 px-2 py-0.5 text-[10px] font-bold text-violet-700"
                                >
                                    {{ personnelOptions.find(u => u.id === uid)?.name || 'ID:' + uid }}
                                    <button @click.stop="removePersonnel(uid)" class="ml-0.5 hover:text-rose-600">✕</button>
                                </span>
                            </div>

                            <!-- Search -->
                            <div class="mb-2">
                                <input
                                    v-model="personnelSearch"
                                    type="text"
                                    placeholder="🔍 Personel ara..."
                                    class="w-full rounded-lg border border-slate-300 px-2 py-1 text-[11px] focus:border-cyan-400"
                                >
                            </div>

                            <!-- Personnel list -->
                            <div class="max-h-36 overflow-y-auto space-y-1 border border-slate-200 rounded-lg p-2 bg-slate-50">
                                <label
                                    v-for="user in filteredPersonnel"
                                    :key="user.id"
                                    :class="[
                                        'flex cursor-pointer items-center gap-2 rounded-lg border px-2 py-1.5 text-xs transition-all',
                                        (editingStep.personnel_ids || []).includes(user.id)
                                            ? 'border-violet-400 bg-violet-50 text-violet-800'
                                            : 'border-transparent bg-white text-slate-600 hover:border-slate-300'
                                    ]"
                                >
                                    <input type="checkbox" :value="user.id" v-model="editingStep.personnel_ids" class="hidden">
                                    <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-slate-800 text-white text-[9px] font-bold">
                                        {{ user.name.charAt(0) }}
                                    </span>
                                    <span class="flex-1 truncate">{{ user.name }}</span>
                                    <span v-if="(editingStep.personnel_ids || []).includes(user.id)" class="text-violet-600">✓</span>
                                </label>
                                <div v-if="!filteredPersonnel.length" class="py-2 text-center text-[11px] text-slate-400">
                                    Sonuç bulunamadı
                                </div>
                            </div>
                        </div>

                        <!-- Visibility config -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">📋 Modül Görünürlüğü</label>
                            <div class="flex flex-wrap gap-1.5">
                                <label
                                    v-for="(label, key) in moduleOptions"
                                    :key="key"
                                    :class="[
                                        'cursor-pointer rounded-lg border px-2 py-1 text-xs font-medium transition-all',
                                        (editingStep.visibility_config || []).includes(key)
                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                                            : 'border-slate-200 bg-white text-slate-600'
                                    ]"
                                >
                                    <input type="checkbox" :value="key" v-model="editingStep.visibility_config" class="hidden">
                                    {{ label }}
                                </label>
                            </div>
                        </div>

                        <!-- Approvable modules -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">✅ Onay Yetkisi (Modüller)</label>
                            <div class="flex flex-wrap gap-1.5">
                                <label
                                    v-for="(label, key) in moduleOptions"
                                    :key="key"
                                    :class="[
                                        'cursor-pointer rounded-lg border px-2 py-1 text-xs font-medium transition-all',
                                        (editingStep.approvable_modules || []).includes(key)
                                            ? 'border-amber-300 bg-amber-50 text-amber-800'
                                            : 'border-slate-200 bg-white text-slate-600'
                                    ]"
                                >
                                    <input type="checkbox" :value="key" v-model="editingStep.approvable_modules" class="hidden">
                                    {{ label }}
                                </label>
                            </div>
                        </div>

                        <!-- Approval mode -->
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Onay Modu</label>
                            <select v-model="editingStep.approval_config.mode" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="any">Herhangi bir yetkili onaylayabilir</option>
                                <option value="all">Tüm yetkililer onaylamalı</option>
                                <option value="assigned_only">Sadece atanan personel</option>
                            </select>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600">
                            <input type="checkbox" v-model="editingStep.is_active" class="rounded border-slate-300 text-cyan-600"> Aktif adım
                        </label>

                        <!-- E-İmza Yetkisi -->
                        <div class="border-t border-amber-200 pt-3 mt-3">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-amber-500 text-sm">🖊️</span>
                                <label class="text-[11px] font-bold text-amber-700">E-İmza Yetkisi</label>
                            </div>

                            <!-- Enable toggle -->
                            <label class="flex items-center gap-2 text-xs font-medium text-slate-600 mb-2">
                                <input type="checkbox" v-model="signatureEnabled" class="rounded border-slate-300 text-amber-600">
                                Bu adımda e-imza gerekli
                            </label>

                            <!-- Signature options (visible when enabled) -->
                            <div v-if="signatureEnabled" class="space-y-2 pl-2 border-l-2 border-amber-200">

                                <!-- PDF Type -->
                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-500">İmzalanacak Belge</label>
                                    <select v-model="signaturePdfType" class="w-full rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                        <option v-for="opt in pdfTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                </div>

                                <!-- Signer Personnel -->
                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-500">🖊️ İmza Yetkilileri</label>
                                    <div class="max-h-32 overflow-y-auto space-y-1 border border-slate-200 rounded-lg p-2 bg-slate-50">
                                        <label
                                            v-for="user in filteredPersonnel"
                                            :key="user.id"
                                            :class="[
                                                'flex cursor-pointer items-center gap-2 rounded-lg border px-2 py-1 text-xs transition-all',
                                                (signatureSignerIds || []).includes(user.id)
                                                    ? 'border-amber-400 bg-amber-50 text-amber-800'
                                                    : 'border-transparent bg-white text-slate-600 hover:border-slate-300'
                                            ]"
                                        >
                                            <input type="checkbox" :value="user.id" v-model="signatureSignerIds" class="hidden">
                                            <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-amber-600 text-white text-[9px] font-bold">
                                                {{ user.name.charAt(0) }}
                                            </span>
                                            <span class="flex-1 truncate">{{ user.name }}</span>
                                            <span v-if="(signatureSignerIds || []).includes(user.id)" class="text-amber-600">✓</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Info box -->
                                <div class="rounded-lg bg-amber-50 border border-amber-200 p-2 text-[10px] text-amber-700">
                                    Bu adımda sadece seçili yetkililer e-imza atabilir. Onay yetkisi (roller) ayrıca tanımlanır.
                                </div>
                            </div>
                        </div>

                        <!-- Modül Yetkileri -->
                        <div class="border-t border-indigo-200 pt-3 mt-3">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-indigo-500 text-sm">🎛️</span>
                                <label class="text-[11px] font-bold text-indigo-700">Modül Bazlı Yetkiler</label>
                            </div>

                            <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
                                <div
                                    v-for="mod in modulePermissionModules"
                                    :key="mod.key"
                                    class="rounded-lg border border-slate-200 overflow-hidden"
                                >
                                    <!-- Module header row -->
                                    <div
                                        @click="toggleExpandedModule(mod.key)"
                                        class="flex items-center justify-between px-3 py-2 cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors"
                                        :class="mod.hasCustom ? 'border-l-4 border-indigo-400' : ''"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span :class="mod.hasCustom ? 'text-indigo-600' : 'text-slate-400'">{{ mod.hasCustom ? '●' : '○' }}</span>
                                            <span class="text-xs font-semibold text-slate-700">{{ mod.label }}</span>
                                            <span v-if="mod.hasCustom" class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded font-bold">ÖZEL</span>
                                        </div>
                                        <span class="text-slate-400 text-xs">{{ expandedModule === mod.key ? '▲' : '▼' }}</span>
                                    </div>

                                    <!-- Expanded config -->
                                    <div v-if="expandedModule === mod.key" class="px-3 py-2 space-y-2 bg-white border-t border-slate-100 max-h-40 overflow-y-auto">

                                        <!-- Action Type per module -->
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold text-slate-500">İşlem Türü (Bu modül için)</label>
                                            <div class="grid grid-cols-3 gap-1">
                                                <button
                                                    v-for="opt in actionTypeOptions"
                                                    :key="opt.value"
                                                    type="button"
                                                    @click="setModuleActionType(mod.key, opt.value)"
                                                    class="flex items-center justify-center gap-1 rounded border py-1 px-2 text-[10px] font-medium transition-all"
                                                    :class="getModulePerm(mod.key).action_type === opt.value
                                                        ? opt.value === 'onay' ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
                                                        : opt.value === 'paraf' ? 'border-blue-400 bg-blue-50 text-blue-800'
                                                        : 'border-amber-400 bg-amber-50 text-amber-800'
                                                        : 'border-slate-200 bg-white text-slate-500'"
                                                >
                                                    {{ opt.label.split(' ')[0] }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Approver Roles -->
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold text-slate-500">Onay Rolleri</label>
                                            <div class="flex flex-wrap gap-1">
                                                <label
                                                    v-for="(rLabel, rKey) in roleOptions"
                                                    :key="rKey"
                                                    :class="[
                                                        'cursor-pointer rounded border px-1.5 py-0.5 text-[10px] font-medium transition-all',
                                                        (getModulePerm(mod.key).approver_roles || []).includes(rKey)
                                                            ? 'border-violet-400 bg-violet-50 text-violet-800'
                                                            : 'border-slate-200 bg-white text-slate-500'
                                                    ]"
                                                >
                                                    <input type="checkbox" :value="rKey" class="hidden" @change="toggleModuleApproverRole(mod.key, rKey)" :checked="(getModulePerm(mod.key).approver_roles || []).includes(rKey)">
                                                    {{ rLabel }}
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Signer Personnel -->
                                        <div v-if="getModulePerm(mod.key).action_type === 'e_imza'">
                                            <label class="mb-1 block text-[10px] font-bold text-slate-500">🖊️ E-İmza Yetkilileri</label>
                                            <div class="max-h-24 overflow-y-auto space-y-1 border border-slate-200 rounded-lg p-2 bg-slate-50">
                                                <label
                                                    v-for="user in filteredPersonnel"
                                                    :key="user.id"
                                                    :class="[
                                                        'flex cursor-pointer items-center gap-2 rounded border px-2 py-1 text-[10px] transition-all',
                                                        (getModulePerm(mod.key).signer_ids || []).includes(user.id)
                                                            ? 'border-amber-400 bg-amber-50 text-amber-800'
                                                            : 'border-transparent bg-white text-slate-600'
                                                    ]"
                                                >
                                                    <input type="checkbox" :value="user.id" class="hidden" @change="toggleModuleSignerId(mod.key, user.id)" :checked="(getModulePerm(mod.key).signer_ids || []).includes(user.id)">
                                                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-slate-700 text-white text-[8px] font-bold">{{ user.name.charAt(0) }}</span>
                                                    <span class="flex-1 truncate">{{ user.name }}</span>
                                                    <span v-if="(getModulePerm(mod.key).signer_ids || []).includes(user.id)" class="text-amber-600">✓</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Visible to Roles -->
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold text-slate-500">Görünür Roller</label>
                                            <div class="flex flex-wrap gap-1">
                                                <label
                                                    v-for="(rLabel, rKey) in roleOptions"
                                                    :key="rKey"
                                                    :class="[
                                                        'cursor-pointer rounded border px-1.5 py-0.5 text-[10px] font-medium transition-all',
                                                        (getModulePerm(mod.key).visible_to_roles || []).includes(rKey)
                                                            ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
                                                            : 'border-slate-200 bg-white text-slate-500'
                                                    ]"
                                                >
                                                    <input type="checkbox" :value="rKey" class="hidden" @change="toggleModuleVisibleRole(mod.key, rKey)" :checked="(getModulePerm(mod.key).visible_to_roles || []).includes(rKey)">
                                                    {{ rLabel }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-slate-100">
                            <button @click="deleteStep" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50">🗑️ Sil</button>
                            <button @click="saveStep" class="flex-1 rounded-lg bg-slate-900 py-2 text-xs font-bold text-white hover:bg-slate-700">Kaydet</button>
                        </div>
                    </div>
                </div>

                <!-- Empty state when no step selected -->
                <div v-if="!editingStep && !showAddStep" class="flex h-full items-center justify-center p-6">
                    <div class="text-center">
                        <p class="text-3xl mb-2">👈</p>
                        <p class="text-xs font-semibold text-slate-500">Sol listeden bir adım seçin</p>
                        <p class="mt-1 text-[11px] text-slate-400">veya yeni adım ekleyin</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
