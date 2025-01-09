import * as THREE from 'three';
import { VRButton } from 'three/addons/webxr/VRButton.js';
import { XRControllerModelFactory } from 'three/addons/webxr/XRControllerModelFactory.js';

class VRScene {
    constructor() {
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        this.renderer = new THREE.WebGLRenderer({ antialias: true });
        
        this.controllers = [];
        this.controllerGrips = [];
        
        this.raycaster = new THREE.Raycaster();
        this.intersected = [];
        this.tempMatrix = new THREE.Matrix4();
        
        this.clock = new THREE.Clock();
        
        this.initialize();
    }
    
    initialize() {
        // Setup renderer
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setPixelRatio(window.devicePixelRatio);
        this.renderer.xr.enabled = true;
        document.body.appendChild(this.renderer.domElement);
        document.body.appendChild(VRButton.createButton(this.renderer));
        
        // Setup scene
        this.scene.background = new THREE.Color(0x505050);
        this.camera.position.set(0, 1.6, 3);
        
        // Add lights
        const ambient = new THREE.AmbientLight(0x404040);
        const directional = new THREE.DirectionalLight(0xffffff, 1);
        directional.position.set(1, 1, 1).normalize();
        this.scene.add(ambient, directional);
        
        // Add floor
        const floorGeometry = new THREE.PlaneGeometry(4, 4);
        const floorMaterial = new THREE.MeshStandardMaterial({
            color: 0x808080,
            roughness: 1.0,
            metalness: 0.0
        });
        const floor = new THREE.Mesh(floorGeometry, floorMaterial);
        floor.rotation.x = -Math.PI / 2;
        floor.receiveShadow = true;
        this.scene.add(floor);
        
        this.setupControllers();
        this.setupEnvironment();
        
        window.addEventListener('resize', this.onWindowResize.bind(this));
        
        this.renderer.setAnimationLoop(this.render.bind(this));
    }
    
    setupControllers() {
        const controllerModelFactory = new XRControllerModelFactory();
        
        // Setup controllers
        for (let i = 0; i < 2; i++) {
            const controller = this.renderer.xr.getController(i);
            controller.addEventListener('selectstart', this.onSelectStart.bind(this));
            controller.addEventListener('selectend', this.onSelectEnd.bind(this));
            this.scene.add(controller);
            this.controllers.push(controller);
            
            const controllerGrip = this.renderer.xr.getControllerGrip(i);
            controllerGrip.add(controllerModelFactory.createControllerModel(controllerGrip));
            this.scene.add(controllerGrip);
            this.controllerGrips.push(controllerGrip);
            
            // Add controller ray
            const geometry = new THREE.BufferGeometry();
            geometry.setAttribute('position', new THREE.Float32BufferAttribute([0, 0, 0, 0, 0, -1], 3));
            const material = new THREE.LineBasicMaterial({
                color: 0xffffff,
                transparent: true,
                opacity: 0.5
            });
            const line = new THREE.Line(geometry, material);
            line.scale.z = 5;
            controller.add(line);
        }
    }
    
    setupEnvironment() {
        // Add some sample interactive objects
        const geometry = new THREE.BoxGeometry(0.15, 0.15, 0.15);
        
        for (let i = 0; i < 10; i++) {
            const object = new THREE.Mesh(
                geometry,
                new THREE.MeshStandardMaterial({
                    color: Math.random() * 0xffffff
                })
            );
            
            object.position.x = (Math.random() - 0.5) * 2;
            object.position.y = (Math.random() * 2) + 0.5;
            object.position.z = (Math.random() - 0.5) * 2;
            
            object.rotation.x = Math.random() * 2 * Math.PI;
            object.rotation.y = Math.random() * 2 * Math.PI;
            object.rotation.z = Math.random() * 2 * Math.PI;
            
            object.userData.interactable = true;
            this.scene.add(object);
        }
    }
    
    onSelectStart(event) {
        const controller = event.target;
        const intersections = this.getIntersections(controller);
        
        if (intersections.length > 0) {
            const intersection = intersections[0];
            const object = intersection.object;
            
            if (object.userData.interactable) {
                object.material.emissive.b = 1;
                controller.attach(object);
                controller.userData.selected = object;
            }
        }
    }
    
    onSelectEnd(event) {
        const controller = event.target;
        
        if (controller.userData.selected !== undefined) {
            const object = controller.userData.selected;
            object.material.emissive.b = 0;
            this.scene.attach(object);
            controller.userData.selected = undefined;
        }
    }
    
    getIntersections(controller) {
        this.tempMatrix.identity().extractRotation(controller.matrixWorld);
        this.raycaster.ray.origin.setFromMatrixPosition(controller.matrixWorld);
        this.raycaster.ray.direction.set(0, 0, -1).applyMatrix4(this.tempMatrix);
        
        return this.raycaster.intersectObjects(this.scene.children, false);
    }
    
    cleanIntersected() {
        while (this.intersected.length) {
            const object = this.intersected.pop();
            object.material.emissive.r = 0;
        }
    }
    
    intersectObjects(controller) {
        if (controller.userData.selected !== undefined) return;
        
        const intersections = this.getIntersections(controller);
        
        if (intersections.length > 0) {
            const intersection = intersections[0];
            const object = intersection.object;
            
            if (object.userData.interactable) {
                object.material.emissive.r = 1;
                this.intersected.push(object);
            }
        }
    }
    
    onWindowResize() {
        this.camera.aspect = window.innerWidth / window.innerHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(window.innerWidth, window.innerHeight);
    }
    
    render() {
        this.cleanIntersected();
        
        for (const controller of this.controllers) {
            this.intersectObjects(controller);
        }
        
        this.renderer.render(this.scene, this.camera);
    }
}

// Initialize the scene when the page loads
window.addEventListener('load', () => {
    const vrScene = new VRScene();
}); 