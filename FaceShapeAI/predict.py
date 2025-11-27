import sys
import cv2
import mediapipe as mp
import numpy as np
import joblib
import os

# -------------------- LOAD MODEL AND PREPROCESSOR --------------------
# Get the folder where predict.py is located
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# Model folder relative to the script
MODEL_DIR = os.path.join(BASE_DIR, "model")

ensemble_file = os.path.join(MODEL_DIR, "ensemble_with_rf_svc_compressed.pkl")
preproc_file = os.path.join(MODEL_DIR, "preproc_scaler_pca.pkl")

ensemble = joblib.load(ensemble_file)
preproc = joblib.load(preproc_file)

scaler = preproc["scaler"]
pca = preproc["pca"]  # Could be None if PCA skipped

# -------------------- GET IMAGE --------------------
if len(sys.argv) < 2:
    print("Usage: python predict.py <image_path>")
    sys.exit()

img_path = sys.argv[1]
img = cv2.imread(img_path)
if img is None:
    print("Image not found:", img_path)
    sys.exit()

img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)

# -------------------- EXTRACT LANDMARKS --------------------
mp_face_mesh = mp.solutions.face_mesh
face_mesh = mp_face_mesh.FaceMesh(static_image_mode=True, max_num_faces=1)
result = face_mesh.process(img_rgb)

if not result.multi_face_landmarks:
    print("No face detected")
    sys.exit()

landmarks = result.multi_face_landmarks[0].landmark
landmarks = np.array([[lm.x, lm.y, lm.z] for lm in landmarks])

# -------------------- FEATURE ENGINEERING --------------------
def euclidean(a, b):
    return np.linalg.norm(a - b)

x, y = landmarks[:, 0], landmarks[:, 1]
xmin, xmax = x.min(), x.max()
ymin, ymax = y.min(), y.max()
x_span = xmax - xmin if (xmax - xmin) != 0 else 1.0
y_span = ymax - ymin if (ymax - ymin) != 0 else 1.0

x_norm = (x - xmin) / x_span
y_norm = (y - ymin) / y_span

# Key landmarks
p_jl = landmarks[234, :2]
p_jr = landmarks[454, :2]
p_chin = landmarks[152, :2]
p_fore = landmarks[10, :2]
p_lcheek = landmarks[127, :2]
p_rcheek = landmarks[356, :2]
p_leye = landmarks[33, :2]
p_reye = landmarks[263, :2]

jaw_width = euclidean(p_jl, p_jr)
face_height = euclidean(p_fore, p_chin)
cheek_width = euclidean(p_lcheek, p_rcheek)
eye_distance = euclidean(p_leye, p_reye)
forehead_to_jaw = euclidean(p_fore, (p_jl + p_jr)/2.0)

ratio_jaw_face = jaw_width / face_height if face_height != 0 else 0
ratio_cheek_face = cheek_width / face_height if face_height != 0 else 0
ratio_eyes_face = eye_distance / face_height if face_height != 0 else 0
ratio_forehead_jaw = forehead_to_jaw / face_height if face_height != 0 else 0
ratio_jaw_cheek = jaw_width / cheek_width if cheek_width != 0 else 0

features = np.concatenate([
    x_norm, y_norm,
    [ratio_jaw_face, ratio_cheek_face, ratio_eyes_face, ratio_forehead_jaw, ratio_jaw_cheek]
]).reshape(1, -1)

# -------------------- PREPROCESS FEATURES --------------------
features_scaled = scaler.transform(features)
if pca is not None:
    features_scaled = pca.transform(features_scaled)

# -------------------- PREDICTION --------------------
prediction = ensemble.predict(features_scaled)[0]
print(prediction)
