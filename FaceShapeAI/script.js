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

const predictionResult = document.getElementById('predictionResult');
const predictionContainer = document.getElementById('predictionContainer');
const noResult = document.getElementById('noResult');
const loadingSpinner = document.getElementById('loadingSpinner');

let stream = null;
let isCameraActive = false;
let faceMesh = null;
let isGuidelinesEnabled = false;
let camera = null;

// Simplified landmark indices - only what we need!
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

// Initialize MediaPipe Face Mesh
function initFaceMesh() {
    if (typeof FaceMesh === 'undefined') {
        console.error('MediaPipe Face Mesh not loaded');
        return;
    }

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
}

// Handle Face Mesh results - MUCH SIMPLER!
function onFaceMeshResults(results) {
    if (!isGuidelinesEnabled || !isCameraActive) {
        return;
    }

    // Clear previous frame
    guideCtx.clearRect(0, 0, guideCanvas.width, guideCanvas.height);

    if (results.multiFaceLandmarks && results.multiFaceLandmarks.length > 0) {
        const landmarks = results.multiFaceLandmarks[0];
        
        // Draw subtle face mesh using MediaPipe's built-in connections
        if (typeof FACEMESH_TESSELATION !== 'undefined') {
            drawConnectors(guideCtx, landmarks, FACEMESH_TESSELATION, {
                color: '#f5deb350', 
                lineWidth: 0.5
            });
        }
        
        // Draw professional measurement lines (NO MIRRORING - CSS handles it)
     //   drawMeasurementLines(guideCtx, landmarks);
    }
}

// Simplified measurement line drawing
function drawMeasurementLines(ctx, landmarks) {
    const points = {
        jawLeft: landmarks[LANDMARK_INDICES.JAW_LEFT],
        jawRight: landmarks[LANDMARK_INDICES.JAW_RIGHT],
        chin: landmarks[LANDMARK_INDICES.CHIN],
        foreheadCenter: landmarks[LANDMARK_INDICES.FOREHEAD_CENTER],
        cheekLeft: landmarks[LANDMARK_INDICES.CHEEK_LEFT],
        cheekRight: landmarks[LANDMARK_INDICES.CHEEK_RIGHT],
        foreheadLeft: landmarks[LANDMARK_INDICES.FOREHEAD_LEFT],
        foreheadRight: landmarks[LANDMARK_INDICES.FOREHEAD_RIGHT]
    };
    
    const w = guideCanvas.width;
    const h = guideCanvas.height;
    
    // Draw measurement lines with your color scheme
    ctx.setLineDash([5, 5]);
    
    // 1. Face height line (forehead to chin) - Pinkish red
    drawLine(ctx, points.foreheadCenter, points.chin, '#ff6b8a', 2.5, w, h);
    
    // 2. Jaw width line - Rose pink
    drawLine(ctx, points.jawLeft, points.jawRight, '#ff849e', 2.5, w, h);
    
    // 3. Cheek width line - Light grey
    drawLine(ctx, points.cheekLeft, points.cheekRight, '#e8e8e8', 2, w, h);
    
    // 4. Forehead width line - White
    drawLine(ctx, points.foreheadLeft, points.foreheadRight, '#ffffff', 2, w, h);
    
    ctx.setLineDash([]);
    
    // Draw key measurement points
    const keyPoints = [
        { point: points.foreheadCenter, color: '#ff6b8a' },
        { point: points.chin, color: '#ff6b8a' },
        { point: points.jawLeft, color: '#ff849e' },
        { point: points.jawRight, color: '#ff849e' },
        { point: points.cheekLeft, color: '#e8e8e8' },
        { point: points.cheekRight, color: '#e8e8e8' },
        { point: points.foreheadLeft, color: '#ffffff' },
        { point: points.foreheadRight, color: '#ffffff' }
    ];
    
    keyPoints.forEach(({ point, color }) => {
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.arc(point.x * w, point.y * h, 4, 0, 2 * Math.PI);
        ctx.fill();
        
        // Add border for visibility
        ctx.strokeStyle = '#2a2a2a';
        ctx.lineWidth = 1.5;
        ctx.stroke();
    });
}

// Helper function to draw a line
function drawLine(ctx, p1, p2, color, width, w, h) {
    ctx.strokeStyle = color;
    ctx.lineWidth = width;
    ctx.beginPath();
    ctx.moveTo(p1.x * w, p1.y * h);
    ctx.lineTo(p2.x * w, p2.y * h);
    ctx.stroke();
}

// Simplified connector drawing using MediaPipe's format
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

// Toggle Camera On/Off
toggleCameraBtn.addEventListener('click', async () => {
    if (!isCameraActive) {
        await startCamera();
    } else {
        stopCamera();
    }
});

// Start Camera
async function startCamera() {
    try {
        // Initialize Face Mesh if not already done
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
        
        // Update UI
        video.classList.remove('d-none');
        cameraPlaceholder.classList.add('d-none');
        cameraControls.classList.remove('d-none');
        toggleCameraBtn.innerHTML = '<i class="fas fa-video-slash me-2"></i>Stop Camera';
        toggleCameraBtn.classList.remove('btn-outline-primary');
        toggleCameraBtn.classList.add('btn-danger');
        
        await video.play();
        
        // Set canvas sizes after video loads
        video.addEventListener('loadedmetadata', () => {
            const width = video.videoWidth;
            const height = video.videoHeight;
            canvas.width = width;
            canvas.height = height;
            
            const container = document.querySelector('.video-container');
            guideCanvas.width = container.offsetWidth;
            guideCanvas.height = container.offsetHeight;
        });
        
        // Start MediaPipe camera processing
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
        
    } catch (err) {
        console.error("Error accessing webcam:", err);
        alert("Cannot access camera. Please check permissions.");
    }
}

// Stop Camera
function stopCamera() {
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
    
    // Update UI
    video.classList.add('d-none');
    guideCanvas.classList.add('d-none');
    cameraPlaceholder.classList.remove('d-none');
    cameraControls.classList.add('d-none');
    toggleCameraBtn.innerHTML = '<i class="fas fa-video me-2"></i>Start Camera';
    toggleCameraBtn.classList.remove('btn-danger');
    toggleCameraBtn.classList.add('btn-outline-primary');
    toggleGuides.checked = false;
    isGuidelinesEnabled = false;
    
    // Clear canvas
    guideCtx.clearRect(0, 0, guideCanvas.width, guideCanvas.height);
}

// Toggle face guidelines
toggleGuides.addEventListener('change', () => {
    isGuidelinesEnabled = toggleGuides.checked;
    
    if (isGuidelinesEnabled && isCameraActive) {
        guideCanvas.classList.remove('d-none');
    } else {
        guideCanvas.classList.add('d-none');
        guideCtx.clearRect(0, 0, guideCanvas.width, guideCanvas.height);
    }
});

// Capture snapshot from camera
captureBtn.addEventListener('click', () => {
    if (!isCameraActive) {
        alert("Please start the camera first.");
        return;
    }
    
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        setButtonLoading(captureBtn, true);
        
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataURL = canvas.toDataURL('image/png');
        sendImageToServer(dataURL, captureBtn);
    } else {
        alert("Camera not ready. Please wait a moment.");
    }
});

// Browse button click
uploadBrowseBtn.addEventListener('click', () => {
    uploadInput.click();
});

// Upload area click
uploadArea.addEventListener('click', (e) => {
    if (e.target !== uploadArea && e.target !== uploadPlaceholder && 
        !uploadPlaceholder.contains(e.target)) {
        return;
    }
    uploadInput.click();
});

// Drag and drop functionality
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

// File input change
uploadInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        handleFileUpload(file);
    }
});

// Handle file upload
function handleFileUpload(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        uploadedImage.src = e.target.result;
        uploadedImage.classList.remove('d-none');
        uploadPlaceholder.classList.add('d-none');
        uploadBtn.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
}

// Analyze uploaded image
uploadBtn.addEventListener('click', () => {
    if (uploadedImage.src) {
        setButtonLoading(uploadBtn, true);
        sendImageToServer(uploadedImage.src, uploadBtn);
    }
});

// Set button loading state
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

// Send image to server for prediction
function sendImageToServer(dataURL, button) {
    showLoading();
    
    fetch('capture.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: dataURL })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        setButtonLoading(button, false);
        
        if (data.prediction && data.prediction !== "No face detected" && data.prediction.trim() !== "") {
            displayResult(data.prediction);
        } else {
            displayError("No face detected in the image. Please try again with a clear face photo.");
        }
    })
    .catch(err => {
        console.error('Error:', err);
        hideLoading();
        setButtonLoading(button, false);
        displayError("Error analyzing image. Please try again.");
    });
}

// Show loading spinner
function showLoading() {
    noResult.classList.add('d-none');
    predictionContainer.classList.add('d-none');
    loadingSpinner.classList.remove('d-none');
}

// Hide loading spinner
function hideLoading() {
    loadingSpinner.classList.add('d-none');
}

// Display result
function displayResult(prediction) {
    predictionResult.textContent = prediction;
    predictionContainer.classList.remove('d-none');
    noResult.classList.add('d-none');
    
    setTimeout(() => {
        predictionContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
}

// Display error
function displayError(message) {
    predictionResult.textContent = message;
    predictionResult.style.color = '#dc3545';
    predictionContainer.classList.remove('d-none');
    noResult.classList.add('d-none');
    
    setTimeout(() => {
        predictionResult.style.color = '';
    }, 3000);
}