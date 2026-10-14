import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.152.2/build/three.module.js';
import { GLTFLoader } from 'https://cdn.jsdelivr.net/npm/three@0.152.2/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'https://cdn.jsdelivr.net/npm/three@0.152.2/examples/jsm/controls/OrbitControls.js';


/*
|--------------------------------------------------------------------------
| TOOTH DATA
|--------------------------------------------------------------------------
*/

const toothData = {
    1:  { name: 'Upper Right Third Molar' },
    2:  { name: 'Upper Right Second Molar' },
    3:  { name: 'Upper Right First Molar' },
    4:  { name: 'Upper Right Second Premolar' },
    5:  { name: 'Upper Right First Premolar' },
    6:  { name: 'Upper Right Canine' },
    7:  { name: 'Upper Right Lateral Incisor' },
    8:  { name: 'Upper Right Central Incisor' },

    9:  { name: 'Upper Left Central Incisor' },
    10: { name: 'Upper Left Lateral Incisor' },
    11: { name: 'Upper Left Canine' },
    12: { name: 'Upper Left First Premolar' },
    13: { name: 'Upper Left Second Premolar' },
    14: { name: 'Upper Left First Molar' },
    15: { name: 'Upper Left Second Molar' },
    16: { name: 'Upper Left Third Molar' },

    17: { name: 'Lower Left Third Molar' },
    18: { name: 'Lower Left Second Molar' },
    19: { name: 'Lower Left First Molar' },
    20: { name: 'Lower Left Second Premolar' },
    21: { name: 'Lower Left First Premolar' },
    22: { name: 'Lower Left Canine' },
    23: { name: 'Lower Left Lateral Incisor' },
    24: { name: 'Lower Left Central Incisor' },

    25: { name: 'Lower Right Central Incisor' },
    26: { name: 'Lower Right Lateral Incisor' },
    27: { name: 'Lower Right Canine' },
    28: { name: 'Lower Right First Premolar' },
    29: { name: 'Lower Right Second Premolar' },
    30: { name: 'Lower Right First Molar' },
    31: { name: 'Lower Right Second Molar' },
    32: { name: 'Lower Right Third Molar' }
};


/*
|--------------------------------------------------------------------------
| GLOBAL VARIABLES
|--------------------------------------------------------------------------
*/

window.appScene = null;
window.appCamera = null;
window.appRenderer = null;
window.appControls = null;
window.appRaycaster = null;
window.appMouse = null;

window.appInteractableTeeth = [];
window.appSelectedTooth = null;
window.appSelectedTeeth = new Set();
window.appToothMeshMap = new Map();


/*
|--------------------------------------------------------------------------
| INITIALIZE 3D MODEL
|--------------------------------------------------------------------------
*/

window.initialize3DModel = function () {

    const container = document.getElementById('canvas-container');

    if (!container) {
        console.error('canvas-container not found.');
        return;
    }

    /*
    Prevent duplicate canvas
    */
    if (container.querySelector('canvas')) {
        return;
    }


    const width = Math.max(container.clientWidth, 520);
    const height = Math.max(container.clientHeight, 280);


    /*
    |--------------------------------------------------------------------------
    | SCENE
    |--------------------------------------------------------------------------
    */

    const scene = new THREE.Scene();

    scene.background = new THREE.Color(0xeef2f6);


    /*
    |--------------------------------------------------------------------------
    | CAMERA
    |--------------------------------------------------------------------------
    */

    const camera = new THREE.PerspectiveCamera(
        34,
        width / height,
        0.1,
        1000
    );

    camera.position.set(
        0,
        -0.45,
        24.6
    );


    /*
    |--------------------------------------------------------------------------
    | RENDERER
    |--------------------------------------------------------------------------
    */

    const renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true
    });

    renderer.setSize(width, height);

    renderer.setPixelRatio(
        Math.min(window.devicePixelRatio, 2)
    );

    renderer.shadowMap.enabled = true;

    renderer.outputColorSpace = THREE.SRGBColorSpace;

    renderer.toneMapping = THREE.ACESFilmicToneMapping;

    renderer.toneMappingExposure = 0.92;

    container.appendChild(renderer.domElement);


    /*
    |--------------------------------------------------------------------------
    | CONTROLS
    |--------------------------------------------------------------------------
    */

    const controls = new OrbitControls(
        camera,
        renderer.domElement
    );

    controls.enableDamping = true;

    controls.enablePan = false;

    controls.minDistance = 18;

    controls.maxDistance = 34;

    controls.minPolarAngle = 1.05;

    controls.maxPolarAngle = 2.05;

    controls.target.set(
        0,
        -1.7,
        0.2
    );


    /*
    |--------------------------------------------------------------------------
    | GLOBAL REFERENCES
    |--------------------------------------------------------------------------
    */

    window.appScene = scene;
    window.appCamera = camera;
    window.appRenderer = renderer;
    window.appControls = controls;

    window.appRaycaster = new THREE.Raycaster();

    window.appMouse = new THREE.Vector2();

    window.appInteractableTeeth = [];

    window.appSelectedTooth = null;

    window.appSelectedTeeth = new Set();

    window.appToothMeshMap = new Map();


    /*
    |--------------------------------------------------------------------------
    | LIGHTING
    |--------------------------------------------------------------------------
    */

    scene.add(
        new THREE.AmbientLight(
            0xffffff,
            0.75
        )
    );


    const hemiLight =
        new THREE.HemisphereLight(
            0xf8fbff,
            0xc08f88,
            0.68
        );

    scene.add(hemiLight);


    const dirLight =
        new THREE.DirectionalLight(
            0xffffff,
            0.95
        );

    dirLight.position.set(
        8,
        12,
        14
    );

    dirLight.castShadow = true;

    scene.add(dirLight);


    const fillLight =
        new THREE.DirectionalLight(
            0xffffff,
            0.45
        );

    fillLight.position.set(
        -10,
        4,
        12
    );

    scene.add(fillLight);


    /*
    |--------------------------------------------------------------------------
    | COLORS
    |--------------------------------------------------------------------------
    */

    const naturalToothColor =
        new THREE.Color(0xf8f6ef);

    const selectedToothColor =
        new THREE.Color(0x5b9cff);


    const gumMaterial =
        new THREE.MeshStandardMaterial({

            color: 0x9d4f55,

            roughness: 0.72,

            metalness: 0.03,

            side: THREE.FrontSide

        });


    /*
    |--------------------------------------------------------------------------
    | TOOTH GROUPS
    |--------------------------------------------------------------------------
    */

    const teethGroup =
        new THREE.Group();

    const upperTeethGroup =
        new THREE.Group();

    const lowerTeethGroup =
        new THREE.Group();


    teethGroup.add(
        upperTeethGroup,
        lowerTeethGroup
    );

    scene.add(teethGroup);


    const interactableTeeth = [];

    const toothMeshMap = new Map();


    /*
    |--------------------------------------------------------------------------
    | TOOTH MATERIAL
    |--------------------------------------------------------------------------
    */

    function buildToothMaterial() {

        return new THREE.MeshStandardMaterial({

            color: naturalToothColor,

            roughness: 0.24,

            metalness: 0.04,

            side: THREE.FrontSide,

            emissive: 0x000000,

            emissiveIntensity: 0

        });

    }


    /*
    |--------------------------------------------------------------------------
    | TOOTH OUTLINE
    |--------------------------------------------------------------------------
    */

    function addToothOutline(mesh) {

        if (
            !mesh ||
            !mesh.geometry ||
            mesh.userData.outline
        ) {
            return;
        }


        const outline =
            new THREE.LineSegments(

                new THREE.EdgesGeometry(
                    mesh.geometry,
                    28
                ),

                new THREE.LineBasicMaterial({

                    color: 0xb8bec8,

                    transparent: true,

                    opacity: 0.82

                })

            );


        outline.name =
            `${mesh.name || 'tooth'}-outline`;


        outline.renderOrder = 2;


        /*
        Prevent outline from receiving clicks
        */

        outline.raycast = () => {};


        mesh.add(outline);

        mesh.userData.outline = outline;
    }


    /*
    |--------------------------------------------------------------------------
    | REGISTER TOOTH
    |--------------------------------------------------------------------------
    */

    function registerToothMesh(mesh, id) {

        if (
            !toothData[id] ||
            toothMeshMap.has(id)
        ) {
            return false;
        }


        mesh.material =
            buildToothMaterial();


        mesh.castShadow = true;

        mesh.receiveShadow = true;


        mesh.userData = {

            id: id,

            name: toothData[id].name,

            baseColor:
                naturalToothColor.clone(),

            group:
                id <= 16
                    ? 'upper'
                    : 'lower'

        };


        addToothOutline(mesh);


        interactableTeeth.push(mesh);

        toothMeshMap.set(id, mesh);


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | TOOTH VISUAL
    |--------------------------------------------------------------------------
    */

    function applyToothSelectionVisual(
        mesh,
        selected
    ) {

        if (
            !mesh ||
            !mesh.material
        ) {
            return;
        }


        mesh.material.color.copy(

            selected
                ? selectedToothColor
                : mesh.userData.baseColor

        );


        mesh.material.emissive.set(

            selected
                ? 0x215acb
                : 0x000000

        );


        mesh.material.emissiveIntensity =
            selected
                ? 0.18
                : 0;


        if (
            mesh.userData.outline &&
            mesh.userData.outline.material
        ) {

            mesh.userData.outline.material.color.set(

                selected
                    ? 0x2f67d8
                    : 0xb8bec8

            );


            mesh.userData.outline.material.opacity =
                selected
                    ? 0.95
                    : 0.82;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE HIDDEN INPUT
    |--------------------------------------------------------------------------
    */

    function updateModelSelectionField() {

        const selectedIds =
            Array.from(
                window.appSelectedTeeth
            )
            .sort(
                (a, b) => a - b
            );


        const field =
            document.getElementById(
                'modelSelections'
            );


        if (field) {

            field.value =
                selectedIds.join(',');

        }
    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE SELECTED TEETH
    |--------------------------------------------------------------------------
    */

    function restoreSelectedToothVisuals() {

        toothMeshMap.forEach(
            (mesh, id) => {

                applyToothSelectionVisual(
                    mesh,
                    window.appSelectedTeeth.has(id)
                );

            }
        );


        updateModelSelectionField();


        /*
        If odontogram.php has a synchronization function,
        call it.
        */

        if (
            typeof window.syncToothChartFrom3DSelections ===
            'function'
        ) {

            window.syncToothChartFrom3DSelections();

        }
    }


    /*
    |--------------------------------------------------------------------------
    | READ SELECTED TEETH FROM INPUT
    |--------------------------------------------------------------------------
    */

    window.sync3DSelectionsFromInput =
        function () {

            const field =
                document.getElementById(
                    'modelSelections'
                );


            const values =
                field?.value
                    ? field.value
                        .split(',')
                        .map(
                            value =>
                                Number(value.trim())
                        )
                        .filter(
                            Number.isFinite
                        )
                    : [];


            window.appSelectedTeeth =
                new Set(values);


            restoreSelectedToothVisuals();
        };


    /*
    |--------------------------------------------------------------------------
    | GET TOOTH NAME
    |--------------------------------------------------------------------------
    */

    function getToothName(id) {

        return toothData[id]
            ? toothData[id].name
            : `Tooth ${id}`;

    }


    /*
    |--------------------------------------------------------------------------
    | SHOW TOOTH DETAILS
    |--------------------------------------------------------------------------
    */

    function showToothDetails(tooth) {

        if (!tooth) {
            return;
        }


        const id =
            tooth.userData.id;


        const name =
            getToothName(id);


        /*
        Selected tooth number
        */

        const toothSelect =
            document.getElementById(
                'selectedTooth'
            );


        if (toothSelect) {

            toothSelect.value = id;

        }


        /*
        Tooth name/details
        */

        const detailInput =
            document.getElementById(
                'toothDetail'
            );


        if (detailInput) {

            detailInput.value = name;

        }


        /*
        Optional visible tooth display
        */

        const visibleNumber =
            document.getElementById(
                'selectedToothNumber'
            );


        if (visibleNumber) {

            visibleNumber.textContent =
                `Tooth ${id}`;

        }


        const visibleName =
            document.getElementById(
                'selectedToothName'
            );


        if (visibleName) {

            visibleName.textContent =
                name;

        }


        console.log(
            `Selected Tooth ${id}: ${name}`
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DETERMINE TOOTH FROM MODEL NAME
    |--------------------------------------------------------------------------
    */

    function getNamedToothInfo(mesh) {

        const match =
            String(mesh?.name || '')
                .match(
                    /^(\d+)([DP])(?:\.(\d+))?$/i
                );


        if (!match) {
            return null;
        }


        const localId =
            Number.parseInt(
                match[1],
                10
            );


        const archCode =
            match[2].toUpperCase();


        const duplicate =
            Boolean(match[3]);


        if (
            localId < 1 ||
            localId > 16
        ) {
            return null;
        }


        /*
        P = upper
        D = lower
        */

        if (archCode === 'P') {

            return {

                id: 17 - localId,

                arch: 'upper',

                isDuplicate: duplicate

            };
        }


        if (archCode === 'D') {

            return {

                id: 33 - localId,

                arch: 'lower',

                isDuplicate: duplicate

            };
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | TOOTH NAME DETECTION
    |--------------------------------------------------------------------------
    */

    const toothNamePattern =
        /(tooth|teeth|incisor|canine|premolar|molar|crown)/i;


    function isLikelyToothMesh(mesh) {

        if (
            !mesh ||
            !mesh.isMesh ||
            !mesh.geometry
        ) {
            return false;
        }


        const meshName =
            String(mesh.name || '');


        if (
            toothNamePattern.test(
                meshName
            )
        ) {

            return true;

        }


        const originalColor =
            mesh.material?.color;


        if (originalColor) {

            const hsl = {};

            originalColor.getHSL(hsl);


            if (
                hsl.l > 0.55 &&
                hsl.s < 0.4
            ) {

                return true;

            }
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD MODEL
    |--------------------------------------------------------------------------
    */

    const loader =
        new GLTFLoader();


    loader.load(

        '../assets/models/model.glb',


        function (gltf) {

            console.log(
                '3D dental model loaded.'
            );


            /*
            Scale model
            */

            const box =
                new THREE.Box3()
                    .setFromObject(
                        gltf.scene
                    );


            const size =
                box.getSize(
                    new THREE.Vector3()
                );


            const maxDim =
                Math.max(
                    size.x,
                    size.y,
                    size.z
                );


            const scale =
                12.2 / maxDim;


            gltf.scene.scale.setScalar(
                scale
            );


            /*
            Center model
            */

            const center =
                box.getCenter(
                    new THREE.Vector3()
                );


            gltf.scene.position.sub(
                center
            );


            gltf.scene.position.y =
                -1.7;


            gltf.scene.position.z =
                0.25;


            gltf.scene.rotation.x =
                -0.06;


            teethGroup.add(
                gltf.scene
            );


            /*
            Prepare unnamed teeth
            */

            const unnamedTeeth = {

                upper: [],

                lower: []

            };


            const registeredIds =
                new Set();


            /*
            Traverse model
            */

            gltf.scene.traverse(

                function (child) {

                    if (!child.isMesh) {
                        return;
                    }


                    /*
                    Check named tooth
                    */

                    const named =
                        getNamedToothInfo(
                            child
                        );


                    if (named) {

                        if (
                            named.isDuplicate ||
                            toothMeshMap.has(
                                named.id
                            )
                        ) {

                            child.visible =
                                false;

                        } else {

                            if (
                                registerToothMesh(
                                    child,
                                    named.id
                                )
                            ) {

                                registeredIds.add(
                                    named.id
                                );

                            } else {

                                child.visible =
                                    false;

                            }
                        }


                        return;
                    }


                    /*
                    Check possible unnamed tooth
                    */

                    if (
                        isLikelyToothMesh(
                            child
                        )
                    ) {

                        const position =
                            child.getWorldPosition(
                                new THREE.Vector3()
                            );


                        const arch =
                            position.y >= 0
                                ? 'upper'
                                : 'lower';


                        unnamedTeeth[
                            arch
                        ].push(
                            child
                        );


                    } else {

                        /*
                        Everything else is gum
                        */

                        child.material =
                            gumMaterial.clone();

                    }

                }

            );


            /*
            Remaining IDs
            */

            const remainingIds = {

                upper:
                    Object.keys(
                        toothData
                    )
                    .map(Number)
                    .filter(
                        id =>
                            id <= 16 &&
                            !registeredIds.has(id)
                    ),


                lower:
                    Object.keys(
                        toothData
                    )
                    .map(Number)
                    .filter(
                        id =>
                            id >= 17 &&
                            !registeredIds.has(id)
                    )

            };


            /*
            Assign unnamed meshes
            */

            Object.entries(
                unnamedTeeth
            ).forEach(
                ([arch, meshes]) => {

                    meshes.sort(
                        (a, b) => {

                            const posA =
                                a.getWorldPosition(
                                    new THREE.Vector3()
                                );


                            const posB =
                                b.getWorldPosition(
                                    new THREE.Vector3()
                                );


                            return (
                                posA.x -
                                posB.x
                            );

                        }
                    );


                    meshes.forEach(
                        (mesh, index) => {

                            const id =
                                remainingIds[
                                    arch
                                ][index];


                            if (id) {

                                registerToothMesh(
                                    mesh,
                                    id
                                );

                            } else {

                                mesh.visible =
                                    false;

                            }

                        }
                    );

                }
            );


            /*
            Save references
            */

            window.appInteractableTeeth =
                interactableTeeth;


            window.appToothMeshMap =
                toothMeshMap;


            /*
            Restore saved selections
            */

            window.sync3DSelectionsFromInput();


            console.log(
                'Interactive teeth:',
                interactableTeeth.length
            );

        },


        /*
        Loading progress
        */

        function (xhr) {

            if (xhr.total) {

                const percent =
                    (
                        xhr.loaded /
                        xhr.total
                    ) * 100;


                console.log(
                    `Loading model: ${percent.toFixed(0)}%`
                );

            }

        },


        /*
        Error
        */

        function (error) {

            console.error(
                'Error loading model.glb:',
                error
            );


            createFallbackTeeth();

        }

    );


    /*
    |--------------------------------------------------------------------------
    | FALLBACK TEETH
    |--------------------------------------------------------------------------
    */

    function createFallbackTeeth() {

        console.warn(
            'Using fallback teeth.'
        );


        const upperY = 2;

        const lowerY = -2;

        const radius = 6;


        for (
            let i = 1;
            i <= 32;
            i++
        ) {

            const geometry =
                new THREE.BoxGeometry(
                    0.8,
                    1.2,
                    0.6
                );


            const mesh =
                new THREE.Mesh(
                    geometry,
                    buildToothMaterial()
                );


            const isUpper =
                i <= 16;


            const archY =
                isUpper
                    ? upperY
                    : lowerY;


            const localIndex =
                isUpper
                    ? i - 1
                    : i - 17;


            const angle =
                (
                    localIndex - 7.5
                ) *
                (
                    Math.PI / 12
                );


            mesh.position.set(

                Math.sin(angle) *
                    radius,

                archY,

                Math.cos(angle) *
                    radius

            );


            mesh.rotation.y =
                angle +
                Math.PI / 2;


            mesh.userData = {

                id: i,

                name:
                    toothData[i].name,

                group:
                    isUpper
                        ? 'upper'
                        : 'lower',

                baseColor:
                    naturalToothColor.clone()

            };


            mesh.castShadow = true;

            mesh.receiveShadow = true;


            addToothOutline(
                mesh
            );


            interactableTeeth.push(
                mesh
            );


            toothMeshMap.set(
                i,
                mesh
            );


            if (isUpper) {

                upperTeethGroup.add(
                    mesh
                );

            } else {

                lowerTeethGroup.add(
                    mesh
                );

            }

        }


        window.appInteractableTeeth =
            interactableTeeth;


        window.appToothMeshMap =
            toothMeshMap;


        window.sync3DSelectionsFromInput();

    }


    /*
    |--------------------------------------------------------------------------
    | CLICK TOOTH
    |--------------------------------------------------------------------------
    */

    function onMouseClick(event) {

        const rect =
            renderer.domElement
                .getBoundingClientRect();


        /*
        Convert mouse position
        */

        window.appMouse.x =
            (
                (
                    event.clientX -
                    rect.left
                ) /
                rect.width
            ) * 2 - 1;


        window.appMouse.y =
            -(
                (
                    event.clientY -
                    rect.top
                ) /
                rect.height
            ) * 2 + 1;


        /*
        Raycast
        */

        window.appRaycaster.setFromCamera(
            window.appMouse,
            window.appCamera
        );


        const intersects =
            window.appRaycaster.intersectObjects(
                interactableTeeth,
                false
            );


        /*
        If tooth was clicked
        */

        if (
            intersects.length === 0
        ) {

            return;

        }


        const clickedTooth =
            intersects[0].object;


        if (
            !clickedTooth.userData ||
            !clickedTooth.userData.id
        ) {

            return;

        }


        const toothId =
            clickedTooth.userData.id;


        const selectedSet =
            window.appSelectedTeeth;


        /*
        Toggle selected tooth
        */

        if (
            selectedSet.has(
                toothId
            )
        ) {

            selectedSet.delete(
                toothId
            );

        } else {

            selectedSet.add(
                toothId
            );

        }


        window.appSelectedTooth =
            clickedTooth;


        /*
        Update color
        */

        restoreSelectedToothVisuals();


        /*
        Show tooth details
        */

        showToothDetails(
            clickedTooth
        );


        console.log(
            'Clicked tooth:',
            toothId,
            getToothName(toothId)
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */

    function onResize() {

        const newWidth =
            Math.max(
                container.clientWidth,
                520
            );


        const newHeight =
            Math.max(
                container.clientHeight,
                280
            );


        renderer.setSize(
            newWidth,
            newHeight
        );


        camera.aspect =
            newWidth /
            newHeight;


        camera.updateProjectionMatrix();

    }


    /*
    |--------------------------------------------------------------------------
    | EVENTS
    |--------------------------------------------------------------------------
    */

    renderer.domElement.addEventListener(
        'click',
        onMouseClick
    );


    window.addEventListener(
        'resize',
        onResize
    );


    /*
    |--------------------------------------------------------------------------
    | ANIMATION
    |--------------------------------------------------------------------------
    */

    function animate() {

        requestAnimationFrame(
            animate
        );


        controls.update();


        renderer.render(
            scene,
            camera
        );

    }


    animate();

};