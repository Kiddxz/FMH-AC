/**
 * Shared 3D dental model viewer.
 * - Frames the model so it is centred and fills the box at any size.
 * - Drag rotates (limited so the mouth never flips upside down), scroll zooms gently.
 * - A short click (not a drag) on a tooth calls onToothClick(toothNumber).
 *
 * createToothViewer(holder, { modelUrl, colorFor(tooth) -> hex|null, onToothClick, interactive })
 * returns { sync(), reset() }.
 */
import * as THREE from './three.module.js';
import { GLTFLoader } from './loaders/GLTFLoader.js';
import { OrbitControls } from './controls/OrbitControls.js';

const BASE_COLOR = 0xf7f1e5;
const FDI_ORDER = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28','48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];

// Mesh names in model.glb look like "5P" (upper) / "12D" (lower); convert them to FDI tooth numbers.
function toothIdFromName(name) {
    const match = String(name || '').match(/^(\d+)([DP])(?:\.\d+)?$/i);
    if (!match) return null;
    const n = +match[1], arch = match[2].toUpperCase();
    if (n < 1 || n > 16) return null;
    if (arch === 'P') return n <= 8 ? `2${9 - n}` : `1${n - 8}`;
    return n <= 8 ? `4${9 - n}` : `3${n - 8}`;
}

export function createToothViewer(holder, options = {}) {
    const opts = Object.assign({ modelUrl: '../assets/models/model.glb', colorFor: () => null, onToothClick: null, interactive: true }, options);
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xeaf2f3);
    const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.domElement.style.display = 'block';
    renderer.domElement.style.width = '100%';
    renderer.domElement.style.height = '100%';
    renderer.domElement.style.touchAction = 'none';
    holder.replaceChildren(renderer.domElement);

    scene.add(new THREE.HemisphereLight(0xffffff, 0xbb7e73, 1.55));
    const key = new THREE.DirectionalLight(0xffffff, 1.35); key.position.set(6, 10, 12); scene.add(key);
    const fill = new THREE.DirectionalLight(0xffffff, 0.45); fill.position.set(-8, -4, 6); scene.add(fill);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.09;
    controls.rotateSpeed = 0.65;
    controls.zoomSpeed = 0.6;
    controls.enablePan = false;
    controls.minPolarAngle = Math.PI * 0.18;
    controls.maxPolarAngle = Math.PI * 0.82;
    controls.minAzimuthAngle = -Math.PI * 0.6;
    controls.maxAzimuthAngle = Math.PI * 0.6;

    const meshes = [], toothMap = new Map();
    let radius = 8, hovered = null, loaded = false;
    const extent = new THREE.Vector3(15, 10, 10);
    const ray = new THREE.Raycaster(), pointer = new THREE.Vector2();

    function register(mesh, id) {
        if (!id || toothMap.has(id)) return;
        mesh.material = new THREE.MeshStandardMaterial({ color: BASE_COLOR, roughness: 0.43 });
        mesh.userData.tooth = id;
        toothMap.set(id, mesh);
        meshes.push(mesh);
    }
    function fallbackArch() {
        FDI_ORDER.forEach((id, index) => {
            const upper = index < 16, local = upper ? index : index - 16, a = (local - 7.5) * Math.PI / 18;
            const mesh = new THREE.Mesh(new THREE.SphereGeometry(0.7, 18, 14), new THREE.MeshStandardMaterial({ color: BASE_COLOR }));
            mesh.scale.set(1, 1.25, 0.8);
            mesh.position.set(Math.sin(a) * 7, upper ? 2 : -2, Math.cos(a) * 2);
            scene.add(mesh);
            register(mesh, id);
        });
        radius = 8;
        extent.set(16, 6, 6);
    }

    // Distance at which the model's front face fills the view with a small margin, for any box shape.
    function fitDistance() {
        const vFov = THREE.MathUtils.degToRad(camera.fov);
        const hFov = 2 * Math.atan(Math.tan(vFov / 2) * camera.aspect);
        const margin = 1.06;
        const byHeight = (extent.y / 2) * margin / Math.tan(vFov / 2);
        const byWidth = (extent.x / 2) * margin / Math.tan(hFov / 2);
        return Math.max(byHeight, byWidth) + extent.z * 0.32;
    }
    function reset() {
        const d = fitDistance();
        camera.position.set(0, 0, d);
        controls.target.set(0, 0, 0);
        controls.minDistance = d * 0.55;
        controls.maxDistance = d * 1.45;
        controls.update();
    }
    function resize() {
        const w = holder.clientWidth, h = holder.clientHeight;
        if (!w || !h) return;
        renderer.setSize(w, h, false);
        const aspectChanged = Math.abs(camera.aspect - w / h) > 0.001;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        if (aspectChanged && loaded) {
            // Keep the current viewing direction but re-frame for the new box size.
            const dir = camera.position.clone().sub(controls.target).normalize();
            const d = fitDistance();
            controls.minDistance = d * 0.55;
            controls.maxDistance = d * 1.45;
            camera.position.copy(dir.multiplyScalar(d));
        }
    }
    new ResizeObserver(resize).observe(holder);

    new GLTFLoader().load(opts.modelUrl, gltf => {
        const root = gltf.scene;
        const box = new THREE.Box3().setFromObject(root), size = box.getSize(new THREE.Vector3()), center = box.getCenter(new THREE.Vector3());
        const scale = 15 / Math.max(size.x, size.y, size.z);
        root.scale.setScalar(scale);
        root.position.set(-center.x * scale, -center.y * scale, -center.z * scale);
        root.traverse(child => { if (child.isMesh) register(child, toothIdFromName(child.name)); });
        scene.add(root);
        const fitted = new THREE.Box3().setFromObject(root);
        fitted.getSize(extent);
        root.position.sub(fitted.getCenter(new THREE.Vector3()));
        radius = extent.length() / 2;
        if (!meshes.length) fallbackArch();
        loaded = true; resize(); reset(); sync();
    }, undefined, () => { fallbackArch(); loaded = true; resize(); reset(); sync(); });

    function sync() {
        toothMap.forEach((mesh, id) => {
            const hex = opts.colorFor(id);
            mesh.material.color.setHex(hex == null ? BASE_COLOR : hex);
            mesh.material.emissive?.setHex(mesh === hovered ? 0x1d5f66 : 0x000000);
            if (mesh.material.emissive) mesh.material.emissiveIntensity = mesh === hovered ? 0.28 : 0;
        });
    }
    function pick(event) {
        const r = renderer.domElement.getBoundingClientRect();
        pointer.x = (event.clientX - r.left) / r.width * 2 - 1;
        pointer.y = -(event.clientY - r.top) / r.height * 2 + 1;
        ray.setFromCamera(pointer, camera);
        return ray.intersectObjects(meshes, false)[0]?.object || null;
    }

    if (opts.interactive && typeof opts.onToothClick === 'function') {
        let down = null;
        renderer.domElement.addEventListener('pointerdown', e => { down = { x: e.clientX, y: e.clientY, t: performance.now() }; });
        renderer.domElement.addEventListener('pointerup', e => {
            if (!down) return;
            const moved = Math.hypot(e.clientX - down.x, e.clientY - down.y), quick = performance.now() - down.t < 450;
            down = null;
            if (moved > 6 || !quick) return; // it was a drag to rotate, not a click
            const hit = pick(e);
            if (hit) opts.onToothClick(hit.userData.tooth);
        });
        renderer.domElement.addEventListener('pointermove', e => {
            if (e.buttons) return;
            const hit = pick(e);
            if (hit !== hovered) { hovered = hit; renderer.domElement.style.cursor = hit ? 'pointer' : 'grab'; sync(); }
        });
        renderer.domElement.addEventListener('pointerleave', () => { if (hovered) { hovered = null; sync(); } });
    }
    renderer.domElement.addEventListener('dblclick', reset);

    (function loop() { controls.update(); renderer.render(scene, camera); requestAnimationFrame(loop); })();
    return { sync, reset };
}
