FROM python:3.11-slim

LABEL service="sabana-nlp"
LABEL description="NLP Text Matching Service for SABANA Document Cross-Check"

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash sabana && chown -R sabana:sabana /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY --chown=sabana:sabana app.py .

USER sabana

EXPOSE 5002

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://localhost:5002/health')" || exit 1

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "5002", "--workers", "1"]