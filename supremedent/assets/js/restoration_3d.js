/**
 * 3D helpers for the dentist restoration treatment plans (revision 2).
 *
 * createArchViewer(holder, { selected: Set, onToggle(tooth) })  -> { sync(), reset() }
 *   Removable.glb: meshes Tooth_18 ... Tooth_28 (upper) and Tooth_48 ... Tooth_38 (lower) are clickable.
 * createStainingViewer(holder)                                    -> { reset() }
 *   C.glb: Incisor_Zone_Grid and Molar_Zone_Grid ("chart for staining").
 * renderDesignThumbnails([{ model, nodes:[names], img }])
 *   Renders single design meshes from M1.glb / M2.glb / P.glb into <img> thumbnails.
 */
import * as THREE from './three.module.js';
import { GLTFLoader } from './loaders/GLTFLoader.js';
import { OrbitControls } from './controls/OrbitControls.js';

const MODELS = '../assets/models/';
const cache = new Map();
function loadModel(file) {
    if (!cache.has(file)) cache.set(file, new Promise((resolve, reject) => new GLTFLoader().load(MODELS + file, g => resolve(g.scene), undefined, reject)));
    return cache.get(file);
}
function lights(scene, strength = 1) {
    scene.add(new THREE.HemisphereLight(0xffffff, 0x6b6b70, 1.7 * strength));
    const key = new THREE.DirectionalLight(0xffffff, 1.25 * strength); key.position.set(4, 6, 10); scene.add(key);
    const fill = new THREE.DirectionalLight(0xffffff, 0.45 * strength); fill.position.set(-6, -3, 6); scene.add(fill);
}
function makeRenderer(holder) {
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.domElement.style.display = 'block';
    renderer.domElement.style.width = '100%';
    renderer.domElement.style.height = '100%';
    renderer.domElement.style.touchAction = 'none';
    holder.replaceChildren(renderer.domElement);
    return renderer;
}
/** Centres the object at the origin and returns its size. */
function centre(root) {
    const box = new THREE.Box3().setFromObject(root);
    const c = box.getCenter(new THREE.Vector3());
    root.position.sub(c);
    return box.getSize(new THREE.Vector3());
}
function fitCamera(camera, size, margin = 1.04) {
    const vFov = THREE.MathUtils.degToRad(camera.fov), hFov = 2 * Math.atan(Math.tan(vFov / 2) * camera.aspect);
    return Math.max((size.y / 2) * margin / Math.tan(vFov / 2), (size.x / 2) * margin / Math.tan(hFov / 2)) + size.z / 2;
}
function viewerShell(holder, background) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(background);
    const camera = new THREE.PerspectiveCamera(32, 1, 0.01, 1000);
    const renderer = makeRenderer(holder);
    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true; controls.dampingFactor = 0.09; controls.enablePan = false; controls.rotateSpeed = 0.6; controls.zoomSpeed = 0.6;
    let size = new THREE.Vector3(1, 1, 1), distance = 5;
    function reset() { distance = fitCamera(camera, size); camera.position.set(0, 0, distance); controls.target.set(0, 0, 0); controls.minDistance = distance * 0.45; controls.maxDistance = distance * 1.8; controls.update(); }
    function resize() { const w = holder.clientWidth, h = holder.clientHeight; if (!w || !h) return; renderer.setSize(w, h, false); camera.aspect = w / h; camera.updateProjectionMatrix(); }
    new ResizeObserver(() => { resize(); }).observe(holder);
    (function loop() { controls.update(); renderer.render(scene, camera); requestAnimationFrame(loop); })();
    return { scene, camera, renderer, controls, reset, resize, setSize: s => { size = s; resize(); reset(); } };
}

export function createArchViewer(holder, opts = {}) {
    const selected = opts.selected || new Set();
    const shell = viewerShell(holder, 0x3a3b3d);
    lights(shell.scene);
    const teeth = new Map(), clickable = [];
    const ENAMEL = 0xf7f3ea, PICK = 0x23a58c, HOVER = 0x1d5f66;
    let hovered = null;
    const ray = new THREE.Raycaster(), pointer = new THREE.Vector2();
    function sync() {
        teeth.forEach((mesh, id) => {
            mesh.material.color.setHex(selected.has(id) ? PICK : ENAMEL);
            mesh.material.emissive.setHex(mesh === hovered ? HOVER : 0x000000);
            mesh.material.emissiveIntensity = mesh === hovered ? 0.3 : 0;
        });
    }
    loadModel('Removable.glb').then(src => {
        const root = src.clone(true);
        root.traverse(o => {
            if (!o.isMesh) return;
            const m = String(o.name).match(/^Tooth_([1-4][1-8])$/);
            if (m) {
                o.material = new THREE.MeshStandardMaterial({ color: ENAMEL, roughness: 0.45 });
                o.userData.tooth = m[1];
                teeth.set(m[1], o);
                clickable.push(o);
            } else if (o.material) {
                o.material = o.material.clone();
            }
        });
        const size = centre(root);
        // A soft grid behind the arches, like the clinic's mock-up.
        const grid = new THREE.GridHelper(Math.max(size.x, size.y) * 3, 24, 0x6b6e72, 0x4a4c4f);
        grid.rotation.x = Math.PI / 2; grid.position.z = -size.z * 0.8;
        shell.scene.add(grid, root);
        shell.setSize(size);
        sync();
    }).catch(() => { holder.insertAdjacentHTML('beforeend', '<span class="model-error">3D model unavailable. Use the tooth chart below.</span>'); });

    function pick(e) {
        const r = shell.renderer.domElement.getBoundingClientRect();
        pointer.x = (e.clientX - r.left) / r.width * 2 - 1;
        pointer.y = -(e.clientY - r.top) / r.height * 2 + 1;
        ray.setFromCamera(pointer, shell.camera);
        return ray.intersectObjects(clickable, false)[0]?.object || null;
    }
    const el = shell.renderer.domElement;
    let down = null;
    el.addEventListener('pointerdown', e => { down = { x: e.clientX, y: e.clientY, t: performance.now() }; });
    el.addEventListener('pointerup', e => {
        if (!down) return;
        const moved = Math.hypot(e.clientX - down.x, e.clientY - down.y), quick = performance.now() - down.t < 450;
        down = null;
        if (moved > 6 || !quick) return;
        const hit = pick(e);
        if (hit && typeof opts.onToggle === 'function') opts.onToggle(hit.userData.tooth);
    });
    el.addEventListener('pointermove', e => { if (e.buttons) return; const hit = pick(e); if (hit !== hovered) { hovered = hit; el.style.cursor = hit ? 'pointer' : 'grab'; sync(); } });
    el.addEventListener('pointerleave', () => { if (hovered) { hovered = null; sync(); } });
    el.addEventListener('dblclick', () => shell.reset());
    return { sync, reset: shell.reset };
}

export function createStainingViewer(holder) {
    const shell = viewerShell(holder, 0x4b4c4f);
    lights(shell.scene, 1.05);
    loadModel('C.glb').then(src => {
        const root = new THREE.Group();
        ['Incisor_Zone_Grid', 'Molar_Zone_Grid'].forEach(name => { const n = src.getObjectByName(name); if (n) root.add(n.clone(true)); });
        if (!root.children.length) root.add(src.clone(true));
        const size = centre(root);
        shell.scene.add(root);
        shell.setSize(size);
    }).catch(() => { holder.insertAdjacentHTML('beforeend', '<span class="model-error">Staining chart model unavailable.</span>'); });
    shell.renderer.domElement.addEventListener('dblclick', () => shell.reset());
    return { reset: shell.reset };
}

/** One shared off-screen renderer draws every design thumbnail, so only one WebGL context is used. */
export async function renderDesignThumbnails(specs, size = { w: 220, h: 170 }) {
    let renderer;
    try { renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true, alpha: false }); }
    catch (_) { return; }
    renderer.setPixelRatio(1);
    renderer.setSize(size.w, size.h, false);
    for (const spec of specs) {
        try {
            const src = await loadModel(spec.model);
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0x56585c);
            lights(scene, 1.15);
            const group = new THREE.Group();
            spec.nodes.forEach(name => {
                const node = src.getObjectByName(name);
                if (!node) return;
                const copy = node.clone(true);
                node.updateWorldMatrix(true, false);
                copy.applyMatrix4(node.parent ? node.parent.matrixWorld : new THREE.Matrix4());
                group.add(copy);
            });
            if (!group.children.length) continue;
            const s = centre(group);
            scene.add(group);
            const camera = new THREE.PerspectiveCamera(30, size.w / size.h, 0.01, 100);
            const d = fitCamera(camera, s, 1.25);
            // Straight-on front view so the dark metal/base regions read clearly.
            camera.position.set(d * 0.06, d * 0.02, d);
            camera.lookAt(0, 0, 0);
            renderer.render(scene, camera);
            spec.img.src = renderer.domElement.toDataURL('image/png');
            spec.img.closest('.design-tile')?.classList.add('has-render');
        } catch (_) { /* the text label stays visible */ }
    }
    renderer.dispose();
}
