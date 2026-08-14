const express = require('express');
const cors = require('cors');
const morgan = require('morgan');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const axios = require('axios');

const app = express();
app.use(cors());
app.use(morgan('dev'));
app.use(express.json());

const BACKEND_URL = process.env.BACKEND_URL || 'http://localhost:8000';

// Ensure uploads directory exists
const uploadDir = path.join(__dirname, '../uploads');
if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
}

// Serve uploads folder statically
app.use('/uploads', express.static(uploadDir));

// Configure Multer for profile images
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, uploadDir);
    },
    filename: (req, file, cb) => {
        cb(null, Date.now() + '_' + file.originalname);
    }
});
const upload = multer({ storage });

// Profile upload handler
app.post('/api/auth/upload-avatar', upload.single('profile_image'), (req, res) => {
    try {
        if (!req.file) {
            return res.status(400).json({ detail: "No file uploaded" });
        }
        
        // Return uploaded path
        const relativePath = 'uploads/' + req.file.filename;
        res.json({ file_path: relativePath });
    } catch (err) {
        res.status(500).json({ detail: err.message });
    }
});

// Proxy handler to route API requests to FastAPI backend
app.all('/api/*', async (req, res) => {
    try {
        const token = req.headers.authorization;
        const config = {
            method: req.method,
            url: `${BACKEND_URL}${req.originalUrl}`,
            headers: {
                'Content-Type': 'application/json',
                ...(token ? { 'Authorization': token } : {})
            },
            data: req.method !== 'GET' ? req.body : undefined
        };
        
        const response = await axios(config);
        res.status(response.status).json(response.data);
    } catch (error) {
        if (error.response) {
            res.status(error.response.status).json(error.response.data);
        } else {
            res.status(500).json({ detail: "Backend microservice unreachable", error: error.message });
        }
    }
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
    console.log(`API Gateway running on port ${PORT}`);
});
