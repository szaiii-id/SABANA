"""
SABANA OCR Service v1.1
Generic OCR for all document types.
- Fixed SSL security (removed CERT_NONE)
- Added URL whitelist (SSRF protection)
- Added EasyOCR timeout
- Fixed temp file cleanup
"""

from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, HttpUrl, field_validator
import easyocr
import logging
import urllib.request
import ssl
import tempfile
import os
import concurrent.futures
import re
from typing import Optional

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("ocr-service")

app = FastAPI(
    title="SABANA OCR Service",
    description="Generic OCR for all document types",
    version="1.1.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"]
)

logger.info("Loading EasyOCR model...")
reader = easyocr.Reader(['id', 'en'], gpu=False)
logger.info("EasyOCR ready.")

# Konstanta
MAX_IMAGE_SIZE_MB = 5
REQUEST_TIMEOUT = 15
OCR_TIMEOUT = 30  # Timeout untuk EasyOCR

# Whitelist domain untuk mencegah SSRF
ALLOWED_DOMAINS = [
    'res.cloudinary.com',
    'storage.googleapis.com',
    's3.amazonaws.com',
    'localhost',
    '127.0.0.1',
    'sabana_storage',
    'minio',
]


class OcrRequest(BaseModel):
    image_url: str
    field_labels: list[str] = []
    
    @field_validator('image_url')
    @classmethod
    def validate_url(cls, v: str) -> str:
        """Validasi URL untuk mencegah SSRF."""
        from urllib.parse import urlparse
        
        parsed = urlparse(v)
        hostname = parsed.hostname
        
        if not hostname:
            raise ValueError('URL tidak valid')
        
        # Whitelist domain
        is_allowed = False
        for domain in ALLOWED_DOMAINS:
            if hostname == domain or hostname.endswith('.' + domain):
                is_allowed = True
                break
        
        if not is_allowed:
            raise ValueError(f'Domain {hostname} tidak diizinkan')
        
        return v


class OcrResponse(BaseModel):
    success: bool
    full_text: str
    matches: list[dict]


@app.get("/")
def root():
    return {"service": "SABANA OCR", "status": "running", "version": "1.1.0"}


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/ocr/extract", response_model=OcrResponse)
async def extract(request: OcrRequest):
    tmp_path: Optional[str] = None
    
    try:
        logger.info(f"Processing: {request.image_url}")

        # ✅ SSL aman (tidak override CERT_NONE)
        ctx = ssl.create_default_context()

        req = urllib.request.Request(
            request.image_url,
            headers={'User-Agent': 'SABANA-OCR/1.1.0'}
        )

        # Download gambar dengan timeout & size limit
        with urllib.request.urlopen(req, context=ctx, timeout=REQUEST_TIMEOUT) as response:
            content_length = response.headers.get('Content-Length')
            if content_length:
                size_mb = int(content_length) / (1024 * 1024)
                if size_mb > MAX_IMAGE_SIZE_MB:
                    raise HTTPException(
                        status_code=400,
                        detail=f"Ukuran gambar terlalu besar ({size_mb:.1f}MB). Maksimal {MAX_IMAGE_SIZE_MB}MB."
                    )

            # Simpan ke temp file
            with tempfile.NamedTemporaryFile(delete=False, suffix=".jpg") as tmp:
                # Stream download (chunk-based)
                chunk_size = 8192
                while True:
                    chunk = response.read(chunk_size)
                    if not chunk:
                        break
                    tmp.write(chunk)
                tmp_path = tmp.name

        # ✅ Proses OCR dengan timeout
        full_text = run_ocr_with_timeout(tmp_path, OCR_TIMEOUT)
        logger.info(f"OCR done: {len(full_text)} chars")

        # NLP Match per field (kalau ada label)
        matches = []
        for label in request.field_labels:
            match = match_field(full_text, label)
            matches.append(match)

        return OcrResponse(
            success=True,
            full_text=full_text,
            matches=matches
        )

    except HTTPException:
        raise
    except urllib.error.URLError as e:
        logger.error(f"OCR download failed: {str(e)}")
        raise HTTPException(status_code=400, detail=f"Gagal mengunduh gambar: {str(e.reason)}")
    except TimeoutError:
        logger.error("OCR timeout")
        raise HTTPException(status_code=500, detail="Proses OCR melebihi batas waktu")
    except Exception as e:
        logger.error(f"OCR failed: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        # ✅ Cleanup temp file (selalu dijalankan)
        if tmp_path and os.path.exists(tmp_path):
            try:
                os.unlink(tmp_path)
            except OSError:
                pass


def run_ocr_with_timeout(image_path: str, timeout: int) -> str:
    """
    Jalankan EasyOCR dengan timeout menggunakan concurrent.futures.
    """
    with concurrent.futures.ThreadPoolExecutor(max_workers=1) as executor:
        future = executor.submit(_read_text, image_path)
        try:
            results = future.result(timeout=timeout)
            return " ".join([item[1] for item in results])
        except concurrent.futures.TimeoutError:
            raise TimeoutError(f"OCR timeout setelah {timeout} detik")


def _read_text(image_path: str) -> list:
    """Wrapper untuk EasyOCR readtext."""
    return reader.readtext(image_path)


def match_field(ocr_text: str, field_label: str) -> dict:
    """Cari nilai di teks OCR berdasarkan label field."""
    keywords = field_label.lower().split()

    for keyword in keywords:
        if len(keyword) >= 3 and keyword in ocr_text.lower():
            value = extract_value(ocr_text, keyword)
            return {
                "label": field_label,
                "keyword_found": keyword,
                "value": value,
                "match": True
            }

    return {
        "label": field_label,
        "keyword_found": None,
        "value": None,
        "match": False
    }


def extract_value(text: str, keyword: str) -> Optional[str]:
    """Ambil teks/angka di sekitar keyword."""
    idx = text.lower().find(keyword.lower())
    if idx == -1:
        return None

    # Ambil 50 karakter setelah keyword
    snippet = text[idx:idx + 50]

    # Cari angka (untuk field numerik)
    numbers = re.findall(r'[\d.,]+', snippet)
    if numbers:
        return numbers[0]

    # Cari teks (untuk field nama, alamat)
    words = snippet.split()
    if len(words) > 1:
        return " ".join(words[1:4])

    return snippet.strip()