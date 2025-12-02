// =====================================================
// DOM ELEMENTS
// =====================================================
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const guideCanvas = document.getElementById('guideCanvas');
const ctx = canvas.getContext('2d');
const guideCtx = guideCanvas.getContext('2d');

const captureBtn = document.getElementById('captureBtn');
const toggleGuides = document.getElementById('toggleGuides');
const toggleCameraBtn = document.getElementById('toggleCameraBtn');
const cameraPlaceholder = document.getElementById('cameraPlaceholder');
const cameraControls = document.getElementById('cameraControls');

const uploadInput = document.getElementById('uploadInput');
const uploadBtn = document.getElementById('uploadBtn');
const uploadBrowseBtn = document.getElementById('uploadBrowseBtn');
const uploadedImage = document.getElementById('uploadedImage');
const uploadArea = document.getElementById('uploadArea');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');

const inputSection = document.getElementById('inputSection');
const resultsSection = document.getElementById('resultsSection');
const noResult = document.getElementById('noResult');
const loadingSpinner = document.getElementById('loadingSpinner');
const reUploadBtn = document.getElementById('reUploadBtn');
const takePhotoBtn = document.getElementById('takePhotoBtn');

// =====================================================
// DEBUG CONSOLE
// =====================================================
const DEBUG_MODE = true; // Set to false to disable debug messages

function debugLog(message, data = null, type = 'info') {
    if (!DEBUG_MODE) return;
    
    const timestamp = new Date().toLocaleTimeString();
    const colors = {
        info: '#2196F3',
        success: '#4CAF50',
        warning: '#FF9800',
        error: '#F44336'
    };
    
    console.log(
        `%c[${timestamp}] ${message}`,
        `color: ${colors[type]}; font-weight: bold;`,
        data || ''
    );
    
    // Also show in UI
    updateLoadingMessage(message);
}

function updateLoadingMessage(message) {
    const loadingText = document.querySelector('#loadingSpinner p');
    if (loadingText) {
        loadingText.textContent = message;
    }
}

// =====================================================
// STATE VARIABLES
// =====================================================
let stream = null;
let isCameraActive = false;
let faceMesh = null;
let isGuidelinesEnabled = false;
let camera = null;
let faceApiModelsLoaded = true; // Not using Face-API anymore

// Analysis results storage
let analysisResults = {
    gender: null,
    faceShape: null,
};

// =====================================================
// SIMPLE GENDER SELECTION (NO FACE-API NEEDED)
// =====================================================
function showGenderSelection() {
    return new Promise((resolve) => {
        debugLog('👤 Showing gender selection...', null, 'info');
        
        const genderDiv = document.getElementById('genderSelection');
        const maleBtn = document.getElementById('selectMale');
        const femaleBtn = document.getElementById('selectFemale');
        
        // Hide loading, show gender selection
        loadingSpinner.classList.add('d-none');
        inputSection.classList.add('d-none');
        genderDiv.classList.remove('d-none');
        
        maleBtn.onclick = () => {
            genderDiv.classList.add('d-none');
            loadingSpinner.classList.remove('d-none');
            debugLog('✅ User selected: MALE', null, 'success');
            resolve('male');
        };
        
        femaleBtn.onclick = () => {
            genderDiv.classList.add('d-none');
            loadingSpinner.classList.remove('d-none');
            debugLog('✅ User selected: FEMALE', null, 'success');
            resolve('female');
        };
    });
}

async function detectGender(imageElement) {
    debugLog('👤 Asking user for gender selection...', null, 'info');
    updateLoadingMessage('Please select your gender...');
    return await showGenderSelection();
}

// =====================================================
// MEDIAPIPE FACE MESH FOR GUIDELINES
// =====================================================
const LANDMARK_INDICES = {
    JAW_LEFT: 172,
    JAW_RIGHT: 397,
    CHIN: 152,
    FOREHEAD_CENTER: 10,
    CHEEK_LEFT: 116,
    CHEEK_RIGHT: 345,
    FOREHEAD_LEFT: 54,
    FOREHEAD_RIGHT: 284
};

function initFaceMesh() {
    if (typeof FaceMesh === 'undefined') {
        debugLog('❌ MediaPipe Face Mesh not loaded', null, 'error');
        return;
    }

    debugLog('🎭 Initializing MediaPipe Face Mesh...', null, 'info');
    
    faceMesh = new FaceMesh({
        locateFile: (file) => {
            return `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`;
        }
    });

    faceMesh.setOptions({
        maxNumFaces: 1,
        refineLandmarks: true,
        minDetectionConfidence: 0.5,
        minTrackingConfidence: 0.5
    });

    faceMesh.onResults(onFaceMeshResults);
    debugLog('✅ MediaPipe Face Mesh initialized', null, 'success');
}

function onFaceMeshResults(results) {
    if (!isGuidelinesEnabled || !isCameraActive) {
        return;
    }

    guideCtx.clearRect(0, 0, guideCanvas.width, guideCanvas.height);

    if (results.multiFaceLandmarks && results.multiFaceLandmarks.length > 0) {
        const landmarks = results.multiFaceLandmarks[0];
        
        if (typeof FACEMESH_TESSELATION !== 'undefined') {
            drawConnectors(guideCtx, landmarks, FACEMESH_TESSELATION, {
                color: '#f5deb350', 
                lineWidth: 0.5
            });
        }
    }
}

function drawConnectors(ctx, landmarks, connections, style) {
    ctx.strokeStyle = style.color;
    ctx.lineWidth = style.lineWidth;
    
    for (const connection of connections) {
        const start = landmarks[connection[0]];
        const end = landmarks[connection[1]];
        
        if (start && end) {
            ctx.beginPath();
            ctx.moveTo(start.x * guideCanvas.width, start.y * guideCanvas.height);
            ctx.lineTo(end.x * guideCanvas.width, end.y * guideCanvas.height);
            ctx.stroke();
        }
    }
}

// =====================================================
// CAMERA CONTROLS
// =====================================================
toggleCameraBtn.addEventListener('click', async () => {
    if (!isCameraActive) {
        await startCamera();
    } else {
        stopCamera();
    }
});

async function startCamera() {
    try {
        debugLog('📷 Starting camera...', null, 'info');
        
        if (!faceMesh) {
            initFaceMesh();
        }

        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            } 
        });
        
        video.srcObject = stream;
        isCameraActive = true;
        
        video.classList.remove('d-none');
        cameraPlaceholder.classList.add('d-none');
        cameraControls.classList.remove('d-none');
        toggleCameraBtn.innerHTML = '<i class="fas fa-video-slash me-2"></i>Stop Camera';
        toggleCameraBtn.classList.remove('btn-outline-primary');
        toggleCameraBtn.classList.add('btn-danger');
        
        await video.play();
        
        video.addEventListener('loadedmetadata', () => {
            const width = video.videoWidth;
            const height = video.videoHeight;
            canvas.width = width;
            canvas.height = height;
            
            const container = document.querySelector('.video-container');
            guideCanvas.width = container.offsetWidth;
            guideCanvas.height = container.offsetHeight;
        });
        
        if (typeof Camera !== 'undefined') {
            camera = new Camera(video, {
                onFrame: async () => {
                    if (isGuidelinesEnabled && isCameraActive) {
                        await faceMesh.send({image: video});
                    }
                },
                width: 640,
                height: 480
            });
            camera.start();
        }
        
        debugLog('✅ Camera started successfully', null, 'success');
        
    } catch (err) {
        debugLog('❌ Error accessing camera', err, 'error');
        console.error("Camera error:", err);
        alert("Cannot access camera. Please check permissions.");
    }
}

function stopCamera() {
    debugLog('🛑 Stopping camera...', null, 'info');
    
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    
    if (camera) {
        camera.stop();
        camera = null;
    }
    
    isCameraActive = false;
    video.srcObject = null;
    
    video.classList.add('d-none');
    guideCanvas.classList.add('d-none');
    cameraPlaceholder.classList.remove('d-none');
    cameraControls.classList.add('d-none');
    toggleCameraBtn.innerHTML = '<i class="fas fa-video me-2"></i>Start Camera';
    toggleCameraBtn.classList.remove('btn-danger');
    toggleCameraBtn.classList.add('btn-outline-primary');
    toggleGuides.checked = false;
    isGuidelinesEnabled = false;
    
    guideCtx.clearRect(0, 0, guideCanvas.width, guideCanvas.height);
    debugLog('✅ Camera stopped', null, 'success');
}

toggleGuides.addEventListener('change', () => {
    isGuidelinesEnabled = toggleGuides.checked;
    
    if (isGuidelinesEnabled && isCameraActive) {
        guideCanvas.classList.remove('d-none');
        debugLog('👁️ Face guidelines enabled', null, 'info');
    } else {
        guideCanvas.classList.add('d-none');
        guideCtx.clearRect(0, 0, guideCanvas.width, guideCanvas.height);
        debugLog('👁️ Face guidelines disabled', null, 'info');
    }
});

// =====================================================
// IMAGE CAPTURE FROM CAMERA
// =====================================================
captureBtn.addEventListener('click', async () => {
    if (!isCameraActive) {
        alert("Please start the camera first.");
        return;
    }
    
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        debugLog('📸 Capturing image from camera...', null, 'info');
        setButtonLoading(captureBtn, true);
        
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataURL = canvas.toDataURL('image/png');
        
        debugLog('✅ Image captured, starting analysis...', null, 'success');
        await performDualAnalysis(dataURL, captureBtn);
    } else {
        alert("Camera not ready. Please wait a moment.");
    }
});

// =====================================================
// IMAGE UPLOAD HANDLING
// =====================================================
uploadBrowseBtn.addEventListener('click', () => {
    uploadInput.click();
});

uploadArea.addEventListener('click', (e) => {
    if (e.target !== uploadArea && e.target !== uploadPlaceholder && 
        !uploadPlaceholder.contains(e.target)) {
        return;
    }
    uploadInput.click();
});

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0 && files[0].type.startsWith('image/')) {
        handleFileUpload(files[0]);
    }
});

uploadInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        handleFileUpload(file);
    }
});

function handleFileUpload(file) {
    debugLog('📁 File uploaded', { name: file.name, size: `${(file.size/1024).toFixed(2)} KB` }, 'info');
    
    const reader = new FileReader();
    reader.onload = function(e) {
        uploadedImage.src = e.target.result;
        uploadedImage.classList.remove('d-none');
        uploadPlaceholder.classList.add('d-none');
        uploadBtn.classList.remove('d-none');
        debugLog('✅ Image loaded and ready for analysis', null, 'success');
    };
    reader.readAsDataURL(file);
}

uploadBtn.addEventListener('click', async () => {
    if (uploadedImage.src) {
        debugLog('🖼️ Starting analysis of uploaded image...', null, 'info');
        setButtonLoading(uploadBtn, true);
        await performDualAnalysis(uploadedImage.src, uploadBtn);
    }
});

// =====================================================
// DUAL ANALYSIS: GENDER + FACE SHAPE
// =====================================================
async function performDualAnalysis(imageDataURL, button) {
    showLoading();
    debugLog('🔬 === STARTING DUAL ANALYSIS ===', null, 'info');
    
    // Reset results
    analysisResults = {
        gender: null,
        faceShape: null,
    };
    
    try {
        // ============= STEP 1: GENDER DETECTION =============
        debugLog('📍 STEP 1/2: Gender Detection', null, 'info');
        updateLoadingMessage('🔍 Detecting gender...');
        
        const tempImg = new Image();
        tempImg.src = imageDataURL;
        
        debugLog('⏳ Waiting for image to load...', null, 'info');
        await new Promise((resolve, reject) => {
            tempImg.onload = () => {
                debugLog('✅ Image loaded successfully', null, 'success');
                resolve();
            };
            tempImg.onerror = () => {
                debugLog('❌ Image failed to load', null, 'error');
                reject();
            };
        });
        
        const detectedGender = await detectGender(tempImg);
        
        if (!detectedGender) {
            throw new Error('No face detected in the image. Please use a clear photo with your face visible.');
        }
        
        analysisResults.gender = detectedGender;
        debugLog(`✅ STEP 1 COMPLETE: Gender = ${detectedGender.toUpperCase()}`, null, 'success');
        
        // ============= STEP 2: FACE SHAPE DETECTION =============
        debugLog('📍 STEP 2/2: Face Shape Detection', null, 'info');
        updateLoadingMessage('🎭 Detecting face shape...');
        
        debugLog('📤 Sending image to server...', null, 'info');
        const startTime = Date.now();
        
        const response = await fetch('capture.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image: imageDataURL })
        });
        
        const responseTime = Date.now() - startTime;
        debugLog(`📥 Server responded in ${responseTime}ms`, null, 'info');
        
        if (!response.ok) {
            debugLog(`❌ Server error: ${response.status} ${response.statusText}`, null, 'error');
            throw new Error(`Server error: ${response.status}`);
        }
        
        debugLog('📋 Parsing response...', null, 'info');
        const data = await response.json();
        
        debugLog('📊 Server response:', data, 'info');
        
        if (!data.success) {
            debugLog('❌ Analysis failed', data, 'error');
            throw new Error(data.error || 'Face shape could not be determined');
        }
        
        if (!data.prediction || data.prediction === "No face detected") {
            debugLog('❌ No face detected by Python model', null, 'error');
            throw new Error('Face shape could not be determined. Please try a different photo.');
        }
        
        analysisResults.faceShape = data.prediction.trim().toLowerCase();
        
        debugLog(`✅ STEP 2 COMPLETE: Face Shape = ${analysisResults.faceShape.toUpperCase()}`, null, 'success');
        
        // ============= STEP 3: DISPLAY RESULTS =============
        debugLog('📍 STEP 3/3: Displaying Results', null, 'info');
        updateLoadingMessage('✨ Preparing your analysis...');
        
        debugLog('🎨 Fetching recommendations...', null, 'info');
        displayAnalysisResults();
        
        debugLog('🎉 === ANALYSIS COMPLETE ===', {
            gender: analysisResults.gender,
            faceShape: analysisResults.faceShape,
        }, 'success');
        
    } catch (error) {
        debugLog('❌ === ANALYSIS FAILED ===', error, 'error');
        console.error('Full error:', error);
        displayError(error.message || "Error analyzing image. Please try again with a clear face photo.");
    } finally {
        hideLoading();
        setButtonLoading(button, false);
    }
}

// =====================================================
// DISPLAY ANALYSIS RESULTS
// =====================================================
function displayAnalysisResults() {
    const { gender, faceShape } = analysisResults;
    
    debugLog('🎨 Displaying results...', { gender, faceShape }, 'info');
    
    if (!gender || !faceShape) {
        debugLog('❌ Incomplete analysis data', { gender, faceShape }, 'error');
        displayError("Analysis incomplete. Please try again.");
        return;
    }
    
    // Check if recommendations.js is loaded
    if (typeof getRecommendations !== 'function') {
        debugLog('❌ recommendations.js not loaded!', null, 'error');
        alert('Error: Recommendations database not loaded. Please refresh the page.');
        return;
    }
    
    // Get recommendations based on gender and face shape
    debugLog('📚 Getting recommendations...', null, 'info');
    const recommendations = getRecommendations(gender, faceShape);
    debugLog('✅ Recommendations loaded', recommendations, 'success');
    
    // Update Face Shape Card
    document.getElementById('faceShapeResult').textContent = 
        faceShape.charAt(0).toUpperCase() + faceShape.slice(1);
    document.getElementById('faceShapeDescription').textContent = recommendations.description;
    
    // Update Hairstyle Card
    const recommendedList = document.getElementById('hairstyleRecommended');
    const avoidList = document.getElementById('hairstyleAvoid');
    
    recommendedList.innerHTML = recommendations.hairstyle.recommended
        .map(style => `<li><i class="fas fa-star text-warning me-2"></i>${style}</li>`)
        .join('');
    
    avoidList.innerHTML = recommendations.hairstyle.avoid
        .map(style => `<li><i class="fas fa-times text-danger me-2"></i>${style}</li>`)
        .join('');
    
    // Update Eyebrows Card
    document.getElementById('eyebrowsCurrent').textContent = recommendations.eyebrows.current;
    document.getElementById('eyebrowsSuggestion').textContent = recommendations.eyebrows.suggestion;
    
    // Update Makeup Card
    if (gender === 'male') {
        document.getElementById('makeupBase').textContent = recommendations.makeup.base;
        document.getElementById('makeupEyes').textContent = recommendations.makeup.eyes;
        document.getElementById('makeupBlush').textContent = recommendations.makeup.blush;
    } else {
        document.getElementById('makeupBase').textContent = recommendations.makeup.base;
        document.getElementById('makeupEyes').textContent = recommendations.makeup.eyes;
        document.getElementById('makeupBlush').textContent = recommendations.makeup.blush;
    }
    
    // Update Facial Proportions Card
    document.getElementById('proportionsForehead').textContent = recommendations.proportions.forehead;
    document.getElementById('proportionsEyes').textContent = recommendations.proportions.eyes;
    document.getElementById('proportionsNose').textContent = recommendations.proportions.nose;
    document.getElementById('proportionsChin').textContent = recommendations.proportions.chin;
    
    // Show results section
    inputSection.classList.add('d-none');
    noResult.classList.add('d-none');
    resultsSection.classList.remove('d-none');
    
    debugLog('✅ Results displayed successfully!', null, 'success');
    
    // Scroll to results
    setTimeout(() => {
        resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

// =====================================================
// UI HELPER FUNCTIONS
// =====================================================
function setButtonLoading(button, isLoading) {
    const btnText = button.querySelector('.btn-text');
    const btnLoading = button.querySelector('.btn-loading');
    
    if (isLoading) {
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
        button.disabled = true;
    } else {
        btnText.classList.remove('d-none');
        btnLoading.classList.add('d-none');
        button.disabled = false;
    }
}

function showLoading() {
    noResult.classList.add('d-none');
    resultsSection.classList.add('d-none');
    loadingSpinner.classList.remove('d-none');
}

function hideLoading() {
    loadingSpinner.classList.add('d-none');
}

function displayError(message) {
    hideLoading();
    alert(message);
    noResult.classList.remove('d-none');
}

// =====================================================
// RESULT SECTION CONTROLS
// =====================================================
reUploadBtn.addEventListener('click', () => {
    debugLog('🔄 Re-upload requested', null, 'info');
    resultsSection.classList.add('d-none');
    inputSection.classList.remove('d-none');
    noResult.classList.remove('d-none');
    
    uploadedImage.src = '';
    uploadedImage.classList.add('d-none');
    uploadPlaceholder.classList.remove('d-none');
    uploadBtn.classList.add('d-none');
    uploadInput.value = '';
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

takePhotoBtn.addEventListener('click', async () => {
    debugLog('📷 Take photo requested', null, 'info');
    resultsSection.classList.add('d-none');
    inputSection.classList.remove('d-none');
    noResult.classList.remove('d-none');
    
    if (!isCameraActive) {
        await startCamera();
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// =====================================================
// BACK BUTTON
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    const backButton = document.getElementById('backButton');
    
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            debugLog('⬅️ Back button clicked', null, 'info');
            window.location.href = '../index.php';
        });
    }
    
    debugLog('✅ Page fully loaded and ready!', null, 'success');
});