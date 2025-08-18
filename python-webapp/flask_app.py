"""
flask_app.py
JobReady - Resume ATS & Job-Fit analyzer backend (Flask)

Requirements (example):
pip install flask flask-cors python-docx PyPDF2 openai

Notes:
- Set OPENAI_API_KEY below (or better: export into environment and use os.getenv).
- Configure ALLOWED_ORIGINS to your frontend domain (e.g. "https://mazindigital.com").
- The app writes a simple log file to ./logs/jobready.log — make sure the process can write there.
"""

import os
import re
import json
import traceback
from fpdf import FPDF
from datetime import datetime

from flask import Flask, request, jsonify
from flask_cors import CORS
from werkzeug.utils import secure_filename

from PyPDF2 import PdfReader
from docx import Document

# OpenAI new client
from openai import OpenAI

# -------------------------
# Configuration
# -------------------------
UPLOAD_FOLDER = "uploads"
LOG_FOLDER = "logs"
LOG_FILE = os.path.join(LOG_FOLDER, "jobready.log")

# Allowed origins for CORS — put your production domain(s) here.
ALLOWED_ORIGINS = [
    "https://mazindigital.com",
    "https://www.mazindigital.com",
    # add more origins if needed for testing
]

# OpenAI key (recommended: set via env var in production)
OPENAI_API_KEY = os.environ.get("OPENAI_API_KEY")
OPENAI_MODEL = "gpt-4o-mini"  # change to preferred model (gpt-5-nano after billing etc.)

# Create minimal folders
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
os.makedirs(LOG_FOLDER, exist_ok=True)

# Initialize Flask and CORS
app = Flask(__name__)
app.config["UPLOAD_FOLDER"] = UPLOAD_FOLDER

# Setup CORS with specified origins
CORS(app, resources={r"/*": {"origins": ALLOWED_ORIGINS}})

# Initialize OpenAI client
client = OpenAI(api_key=OPENAI_API_KEY)

# -------------------------
# Logging helper
# -------------------------
def app_log(msg: str):
    ts = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
    entry = f"[{ts} UTC] {msg}\n"
    try:
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(entry)
    except Exception:
        # fallback to stderr
        print("LOG FAIL:", entry)

# -------------------------
# File text extraction
# -------------------------
def extract_text_from_file(file_storage):
    """
    Accepts werkzeug FileStorage (file) and returns extracted text (string).
    Supports .pdf and .docx.
    """
    filename = secure_filename(file_storage.filename or "")
    name_lower = filename.lower()
    try:
        # Read directly from file object
        if name_lower.endswith(".pdf"):
            reader = PdfReader(file_storage.stream)
            pages_text = []
            for p in reader.pages:
                txt = p.extract_text()
                if txt:
                    pages_text.append(txt)
            return "\n".join(pages_text).strip()
        elif name_lower.endswith(".docx"):
            # python-docx can accept a file-like object
            file_storage.stream.seek(0)
            doc = Document(file_storage.stream)
            paragraphs = [p.text for p in doc.paragraphs if p.text]
            return "\n".join(paragraphs).strip()
        else:
            return ""  # unsupported
    except Exception as e:
        app_log(f"Error extracting text from {filename}: {e}\n{traceback.format_exc()}")
        return ""

# -------------------------
# Scoring & rule-based checks
# -------------------------
WORD_RE = re.compile(r"\b[a-zA-Z0-9+\-#\.]+\b")  # token pattern (keeps words, numbers, python-like tokens)

def tokenize(text):
    return [w.lower() for w in WORD_RE.findall(text or "")]

def keyword_match_score(resume_text, job_description):
    job_words = set(tokenize(job_description))
    if not job_words:
        return 0.0
    resume_words = set(tokenize(resume_text))
    matches = job_words & resume_words
    return round((len(matches) / len(job_words)) * 100, 2)

SECTION_KEYWORDS = {
    "experience": ["experience", "work experience", "employment", "professional experience"],
    "education": ["education", "academic", "degree", "university", "college"],
    "skills": ["skills", "technical skills", "skillset"],
    "certifications": ["certification", "certifications", "certificate"],
    "projects": ["projects", "portfolio", "personal projects"]
}

def section_completeness_score(resume_text):
    text_lower = (resume_text or "").lower()
    total = len(SECTION_KEYWORDS)
    found = 0
    for sec, keywords in SECTION_KEYWORDS.items():
        if any(k in text_lower for k in keywords):
            found += 1
    return round((found / total) * 100, 2)

def readability_score(resume_text):
    # Very basic heuristic:
    words = tokenize(resume_text)
    wc = len(words)
    if wc < 150:
        return 40.0
    if 150 <= wc < 400:
        return 85.0
    if 400 <= wc < 1200:
        return 95.0
    return 80.0

def generate_rule_based_recommendations(resume_text, job_description):
    recs = []
    t = (resume_text or "").lower()
    if "experience" not in t:
        recs.append("Add an 'Experience' section with your most recent roles and responsibilities.")
    if "education" not in t:
        recs.append("Include an 'Education' section with degrees and institutions.")
    if "skills" not in t:
        recs.append("Add a 'Skills' section listing relevant technical and soft skills.")
    # bullet check
    if "•" not in resume_text and "-" not in resume_text and "•" not in resume_text:
        recs.append("Use bullet points for responsibilities and achievements for better readability.")
    # keyword match low
    kw_score = keyword_match_score(resume_text, job_description)
    if kw_score < 50:
        recs.append("Add role-specific keywords from the job description to improve ATS matching.")
    # length check
    wc = len(tokenize(resume_text))
    if wc < 200:
        recs.append("Your resume looks short — add more detail about your achievements and responsibilities.")
    return recs

# -------------------------
# OpenAI (GPT) recommendations (strict JSON response)
# -------------------------
def generate_ai_recommendations(resume_text, job_description, max_recs=3):
    """
    Ask OpenAI to return strict JSON: {"recommendations": ["...", "...", "..."]}
    Returns list of recommendations or empty list on failure.
    """
    prompt = f"""
You are an expert resume/ATS optimization assistant. Output STRICT JSON only (no extra commentary).
Return exactly one JSON object with a top-level key "recommendations" containing an array of {max_recs} strings.

Example response format:
{{
  "recommendations": [
    "First actionable recommendation here.",
    "Second actionable recommendation here.",
    "Third actionable recommendation here."
  ]
}}

Resume:
{resume_text}

Job Description:
{job_description}
"""
    try:
        response = client.responses.create(
            model=OPENAI_MODEL,
            input=prompt,
            max_output_tokens=512,
            # store=False  # optional
        )
        text = (response.output_text or "").strip()
        # try to find JSON object in the output
        try:
            data = json.loads(text)
        except Exception:
            # fallback: try to extract JSON substring
            start = text.find("{")
            end = text.rfind("}") + 1
            if start != -1 and end != -1 and end > start:
                try:
                    data = json.loads(text[start:end])
                except Exception:
                    app_log("AI response JSON parse failed. Raw output:\n" + text)
                    return []
            else:
                app_log("AI response had no JSON. Raw output:\n" + text)
                return []
        recs = data.get("recommendations", [])
        if not isinstance(recs, list):
            app_log("AI returned recommendations key but not a list. Data: " + str(data))
            return []
        # Trim and return up to max_recs
        return [r.strip() for r in recs][:max_recs]
    except Exception as e:
        app_log("OpenAI error: " + str(e) + "\n" + traceback.format_exc())
        return []

# -------------------------
# API route
# -------------------------
@app.route("/analyze", methods=["POST"])
def analyze():
    # Check for resume and job description
    resume_file = request.files.get("resume")
    job_description = request.form.get("job_description", "")

    if resume_file is None or resume_file.filename == "":
        return jsonify({"error": "Empty resume file."}), 400
    if not job_description:
        return jsonify({"error": "Job description cannot be empty."}), 400

    # Main logic for processing the resume and job description
    filename = secure_filename(resume_file.filename)
    save_path = os.path.join(app.config["UPLOAD_FOLDER"], filename)
    resume_file.save(save_path)
    # ... rest of your logic ...

    try:
        # basic request checks
        if "resume" not in request.files:
            app_log("Bad request: 'resume' not in request.files. FORM keys: " + ", ".join(request.form.keys()))
            return jsonify({"error": "Missing resume file (field name must be 'resume')."}), 400
        if "job_description" not in request.form:
            app_log("Bad request: 'job_description' not in request.form.")
            return jsonify({"error": "Missing job_description (field name must be 'job_description')."}), 400
    except Exception as e:
        app_log("Error in request validation: " + str(e))
        return jsonify({"error": "Invalid request parameters"}), 400


    # Get inputs
    resume_file = request.files["resume"]
    job_description = request.form.get("job_description", "").strip()
    name = request.form.get("name", "").strip()
    email = request.form.get("email", "").strip()


    if resume_file.filename == "":
        app_log("Empty filename submitted.")
        return jsonify({"error": "Empty resume file."}), 400
    if not job_description:
        return jsonify({"error": "Job description cannot be empty."}), 400

    # Save uploaded file (optional, useful for debugging)
    filename = secure_filename(resume_file.filename)
    save_path = os.path.join(app.config["UPLOAD_FOLDER"], filename)
    try:
        resume_file.stream.seek(0)
        with open(save_path, "wb") as f:
            resume_file.stream.seek(0)
            f.write(resume_file.read())
        app_log(f"Saved uploaded file to {save_path}")
        # Re-open stream for extraction (reset)
        resume_file.stream.seek(0)
    except Exception as e:
        app_log("Failed to save uploaded file: " + str(e))

    # Extract text
    resume_file.stream.seek(0)
    resume_text = extract_text_from_file(resume_file)
    if not resume_text or len(resume_text.strip()) == 0:
        app_log("Text extraction failed or returned empty for file: " + filename)
        return jsonify({"error": "Unable to extract text from resume. Ensure the PDF is not a scanned image."}), 400

    # Calculate scores
    kw_score = keyword_match_score(resume_text, job_description)
    section_score = section_completeness_score(resume_text)
    read_score = readability_score(resume_text)

    # Weighted ATS score
    # ATS = 40% keywords + 30% sections + 30% readability
    ats_score = round(0.4 * kw_score + 0.3 * section_score + 0.3 * read_score)
    if ats_score < 0:
        ats_score = 0
    if ats_score > 100:
        ats_score = 100

    job_fit_score = round(kw_score)  # percent of job description keywords found

    # Generate rule-based recommendations
    basic_recs = generate_rule_based_recommendations(resume_text, job_description)

    # Generate AI recommendations (best-effort)
    ai_recs = generate_ai_recommendations(resume_text, job_description, max_recs=3)
    if not ai_recs:
        # fallback if AI failed
        ai_recs = basic_recs[:3] if basic_recs else ["Improve readability and add role-specific keywords."]

    # Generate PDF report
    pdf_filename = f"{os.path.splitext(filename)[0]}_report.pdf"
    pdf_path = os.path.join(app.config["UPLOAD_FOLDER"], pdf_filename)
    pdf = FPDF()
    pdf.add_page()
    pdf.set_font("Helvetica", "B", 16)
    pdf.cell(0, 10, "JobReady Resume Analysis Report", ln=True, align="C")
    pdf.ln(8)
    pdf.set_font("Helvetica", size=12)
    if name:
        pdf.cell(0, 10, f"Name: {name}", ln=True)
    try:
        # basic request checks
        if "resume" not in request.files:
            app_log("Bad request: 'resume' not in request.files. FORM keys: " + ", ".join(request.form.keys()))
            return jsonify({"error": "Missing resume file (field name must be 'resume')."}), 400
        if "job_description" not in request.form:
            app_log("Bad request: 'job_description' not in request.form.")
            return jsonify({"error": "Missing job_description (field name must be 'job_description')."}), 400

        # Get inputs
        resume_file = request.files["resume"]
        job_description = request.form.get("job_description", "").strip()
        name = request.form.get("name", "").strip()
        email = request.form.get("email", "").strip()

        if resume_file.filename == "":
            app_log("Empty filename submitted.")
            return jsonify({"error": "Empty resume file."}), 400
        if not job_description:
            return jsonify({"error": "Job description cannot be empty."}), 400

        # Save uploaded file (optional, useful for debugging)
        filename = secure_filename(resume_file.filename)
        save_path = os.path.join(app.config["UPLOAD_FOLDER"], filename)
        try:
            resume_file.stream.seek(0)
            with open(save_path, "wb") as f:
                resume_file.stream.seek(0)
                f.write(resume_file.read())
            app_log(f"Saved uploaded file to {save_path}")
            # Re-open stream for extraction (reset)
            resume_file.stream.seek(0)
        except Exception as e:
            app_log("Failed to save uploaded file: " + str(e))

        # Extract text
        resume_file.stream.seek(0)
        resume_text = extract_text_from_file(resume_file)
        if not resume_text or len(resume_text.strip()) == 0:
            app_log("Text extraction failed or returned empty for file: " + filename)
            return jsonify({"error": "Unable to extract text from resume. Ensure the PDF is not a scanned image."}), 400

        # Calculate scores
        kw_score = keyword_match_score(resume_text, job_description)
        section_score = section_completeness_score(resume_text)
        read_score = readability_score(resume_text)

        # Weighted ATS score
        # ATS = 40% keywords + 30% sections + 30% readability
        ats_score = round(0.4 * kw_score + 0.3 * section_score + 0.3 * read_score)
        if ats_score < 0:
            ats_score = 0
        if ats_score > 100:
            ats_score = 100

        job_fit_score = round(kw_score)  # percent of job description keywords found

        # Generate rule-based recommendations
        basic_recs = generate_rule_based_recommendations(resume_text, job_description)

        # Generate AI recommendations (best-effort)
        ai_recs = generate_ai_recommendations(resume_text, job_description, max_recs=3)
        if not ai_recs:
            # fallback if AI failed
            ai_recs = basic_recs[:3] if basic_recs else ["Improve readability and add role-specific keywords."]

        # Generate PDF report
        pdf_filename = f"{os.path.splitext(filename)[0]}_report.pdf"
        pdf_path = os.path.join(app.config["UPLOAD_FOLDER"], pdf_filename)
        pdf = FPDF()
        pdf.add_page()
        pdf.set_font("Helvetica", "B", 16)
        pdf.cell(0, 10, "JobReady Resume Analysis Report", ln=True, align="C")
        pdf.ln(8)
        pdf.set_font("Helvetica", size=12)
        if name:
            pdf.cell(0, 10, f"Name: {name}", ln=True)
        pdf.cell(0, 10, f"Email: {email}", ln=True)
        pdf.ln(4)
        pdf.cell(0, 10, f"ATS Score: {ats_score}", ln=True)
        pdf.cell(0, 10, f"Job Fit Score: {job_fit_score}", ln=True)
        pdf.cell(0, 10, f"Keyword Match: {kw_score}%", ln=True)
        pdf.cell(0, 10, f"Section Completeness: {section_score}%", ln=True)
        pdf.cell(0, 10, f"Readability: {read_score}%", ln=True)
        pdf.ln(6)
        pdf.set_font("Helvetica", "B", 13)
        pdf.cell(0, 10, "Basic Recommendations:", ln=True)
        pdf.set_font("Helvetica", size=12)
        for rec in basic_recs:
            pdf.multi_cell(0, 8, f"- {rec}")
        pdf.ln(2)
        pdf.set_font("Helvetica", "B", 13)
        pdf.cell(0, 10, "AI Recommendations:", ln=True)
        pdf.set_font("Helvetica", size=12)
        for rec in ai_recs:
            pdf.multi_cell(0, 8, f"- {rec}")
        pdf.ln(2)
        pdf.set_font("Helvetica", "I", 11)
        pdf.multi_cell(0, 8, "Thank you for using JobReady! For questions, contact support@mazindigital.com.")
        pdf.output(pdf_path)

        # Build a public URL for the PDF (assuming /uploads/ is web-accessible)
        pdf_url = f"/uploads/{pdf_filename}"
        # If you serve uploads at a different base URL, adjust here
        base_url = os.environ.get("JOBREADY_BASE_URL", "https://yourdomain.com")
        public_pdf_url = base_url.rstrip("/") + pdf_url

        # Only return ATS/Job Fit and PDF URL for frontend/WordPress
        response = {
            "ats_score": ats_score,
            "job_fit_score": job_fit_score,
            "pdf_url": public_pdf_url,
            "status": "pdf_generated"
        }
        app_log(f"Analyze success for {filename}: ATS {ats_score}, Fit {job_fit_score}, PDF: {public_pdf_url}")
        return jsonify(response), 200
    except Exception as e:
        app_log("Unhandled exception in /analyze: " + str(e) + "\n" + traceback.format_exc())
        return jsonify({"error": "Internal server error"}), 500