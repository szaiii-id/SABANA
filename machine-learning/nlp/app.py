from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from rapidfuzz import fuzz
import logging
import re

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("nlp-service")

app = FastAPI(
    title="SABANA NLP Service",
    description="Text matching & cross-check for document validation",
    version="1.0.0"
)

app.add_middleware(CORSMiddleware, allow_origins=["*"], allow_methods=["*"], allow_headers=["*"])

logger.info("NLP Service ready.")


class MatchRequest(BaseModel):
    ocr_text: str
    fields: list[dict]


class MatchField(BaseModel):
    label: str
    key: str
    input_value: str
    ocr_value: str | None
    keyword_found: str | None
    match_score: float
    match_status: str


class MatchResponse(BaseModel):
    success: bool
    ocr_text_preview: str
    results: list[MatchField]
    summary: dict


@app.get("/")
def root():
    return {"service": "SABANA NLP", "status": "running"}


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/nlp/match", response_model=MatchResponse)
async def match_fields(request: MatchRequest):
    try:
        logger.info(f"Matching {len(request.fields)} fields against OCR text ({len(request.ocr_text)} chars)")

        normalized_ocr = normalize_text(request.ocr_text)
        valid_fields = validate_fields(request.fields)

        results = []
        matched_count = 0
        partial_count = 0
        not_found_count = 0
        not_matched_count = 0

        for field in valid_fields:
            result = match_single_field(
                ocr_text=normalized_ocr,
                label=field.get("label", field.get("key", "")),
                key=field.get("key", ""),
                input_value=str(field.get("value", ""))
            )
            results.append(result)

            if result.match_status == "cocok":
                matched_count += 1
            elif result.match_status == "parsial":
                partial_count += 1
            elif result.match_status == "tidak_ditemukan":
                not_found_count += 1
            else:
                not_matched_count += 1

        return MatchResponse(
            success=True,
            ocr_text_preview=request.ocr_text[:200],
            results=results,
            summary={
                "total": len(results),
                "cocok": matched_count,
                "parsial": partial_count,
                "tidak_cocok": not_matched_count,
                "tidak_ditemukan": not_found_count
            }
        )

    except Exception as e:
        logger.error(f"NLP match failed: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


def validate_fields(fields: list[dict]) -> list[dict]:
    """
    ✅ Dynamic filter: hanya proses field dengan key/label valid.
    Abaikan field dengan nama terlalu pendek (< 3 karakter) karena
    kemungkinan besar itu noise/sampah OCR, bukan field input asli.
    """
    valid = []
    for f in fields:
        key = f.get("key", "")
        label = f.get("label", "")
        value = str(f.get("value", ""))

        # Skip field dengan key terlalu pendek (noise OCR)
        if len(key) < 3:
            logger.info(f"Skipping short key: '{key}' (noise)")
            continue

        # Skip field dengan label terlalu pendek
        if len(label) < 3:
            logger.info(f"Skipping short label: '{label}' (noise)")
            continue

        # Skip field kosong
        if not value or value == "None":
            continue

        valid.append(f)

    return valid


def normalize_text(text: str) -> str:
    text = text.lower()
    text = re.sub(r'\s+', ' ', text)
    return text.strip()


def normalize_number(value: str) -> str:
    value = value.lower()
    value = re.sub(r'rp\.?\s*', '', value)
    value = value.replace('.', '').replace(',', '').strip()
    return value


def match_single_field(ocr_text: str, label: str, key: str, input_value: str) -> MatchField:
    """
    ✅ Dynamic matching: cari keyword di label, cek di OCR text.
    Kalau tidak ketemu keyword, coba cari angka mirip.
    """
    keywords = label.lower().split()
    ocr_value = None
    keyword_found = None

    # Cari keyword di OCR text
    for keyword in keywords:
        if len(keyword) >= 3 and keyword in ocr_text:
            keyword_found = keyword
            ocr_value = extract_value_near_keyword(ocr_text, keyword)
            break

    # Fallback: cari angka mirip di seluruh OCR text
    if not ocr_value and input_value:
        normalized_input = normalize_number(input_value)
        if normalized_input and len(normalized_input) >= 1:
            ocr_value = find_similar_number(ocr_text, normalized_input)

    # Hitung match score
    if ocr_value:
        normalized_ocr_val = normalize_number(str(ocr_value))
        normalized_input = normalize_number(input_value)
        score = fuzz.ratio(normalized_ocr_val, normalized_input)
    else:
        score = 0

    # Tentukan status
    if score >= 90:
        status = "cocok"
    elif score >= 65:
        status = "parsial"
    elif keyword_found:
        status = "tidak_cocok"
    else:
        status = "tidak_ditemukan"

    return MatchField(
        label=label,
        key=key,
        input_value=input_value,
        ocr_value=ocr_value,
        keyword_found=keyword_found,
        match_score=round(score, 1),
        match_status=status
    )


def extract_value_near_keyword(text: str, keyword: str) -> str | None:
    idx = text.find(keyword)
    if idx == -1:
        return None

    snippet = text[idx:idx + 80]

    # Cari nominal uang
    money = re.search(r'(?:rp\.?\s*)?(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d+)?)', snippet)
    if money:
        return money.group(0).strip()

    # Cari angka biasa
    numbers = re.findall(r'\d+(?:[.,]\d+)?', snippet)
    if numbers:
        return numbers[0]

    # Fallback: teks
    words = snippet.split()
    if len(words) > 1:
        return " ".join(words[1:5])

    return snippet.strip()


def find_similar_number(ocr_text: str, target: str) -> str | None:
    numbers = re.findall(r'\d+(?:[.,]\d+)?', ocr_text)
    for num in numbers:
        normalized_num = normalize_number(num)
        if fuzz.ratio(normalized_num, target) >= 80:
            return num
    return None