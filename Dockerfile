FROM python:3.8

COPY requirements.txt .

RUN /usr/local/bin/python -m pip install --upgrade pip

RUN pip install -r requirements.txt \
    && apt-get update \
    && apt-get install ffmpeg libsm6 libxext6 -y \
    && apt-get install tesseract-ocr -y \
    && apt-get install poppler-utils -y \
    && apt-get install wkhtmltopdf -y

RUN mkdir -p /app

WORKDIR /app/recognition

CMD python Splice.py