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
import time
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
    "https://resume.mazindigital.com",
    # add more origins if needed for testing
]

ALLOWED_EXTS = {".pdf", ".docx"}

def is_allowed_file(filename: str) -> bool:
    _, ext = os.path.splitext((filename or "").lower())
    return ext in ALLOWED_EXTS

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

def _contains_bullets(text: str) -> bool:
    t = text or ""
    return ("-" in t) or ("•" in t)

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
    if not _contains_bullets(resume_text):
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
        response = client.chat.completions.create(
            model=OPENAI_MODEL,
            messages=[{"role": "user", "content": prompt}],
            max_tokens=512,
        )
        text = (response.choices[0].message.content or "").strip()
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
# PDF Report Generation
# -------------------------
BRAND_COLORS = {
    "brand": (0x4c, 0x3b, 0xd0),      # #4c3bd0
    "secondary": (0x1f, 0x17, 0x53),  # #1f1753
    "tertiary": (0x9f, 0xc2, 0xcc),   # #9fc2cc
    "text": (0x00, 0x00, 0x00),       # #000000
}


def _safe_truncate(text: str, max_len: int) -> str:
    s = (text or "").strip()
    return s if len(s) <= max_len else s[: max_len - 3] + "..."


def sanitize_for_pdf(text: str) -> str:
    """Replace unsupported unicode with ASCII equivalents and strip the rest."""
    t = str(text or "")
    replacements = {
        "•": "- ",
        "–": "-",
        "—": "-",
        "…": "...",
        "“": '"',
        "”": '"',
        "‘": "'",
        "’": "'",
        "·": "-",
        "×": "x",
        "✓": "",
        "✔": "",
        "▶": ">",
        "►": ">",
    }
    for k, v in replacements.items():
        t = t.replace(k, v)
    try:
        t = t.encode("latin-1", errors="ignore").decode("latin-1")
    except Exception:
        # As a very last resort, drop to ascii
        t = t.encode("ascii", errors="ignore").decode("ascii")
    return t


def generate_pdf_report(filename, name, email, ats_score, job_fit_score, kw_score, section_score, read_score, basic_recs, ai_recs):
    """Generate a concise, single-page on-brand PDF report."""
    pdf_filename = f"{os.path.splitext(filename)[0]}_report.pdf"
    pdf_path = os.path.join(app.config["UPLOAD_FOLDER"], pdf_filename)

    pdf = FPDF(format="A4")
    pdf.add_page()

    # Layout constants
    left_margin = 18
    right_margin = 18
    pdf.set_left_margin(left_margin)
    pdf.set_right_margin(right_margin)
    top_y = 15

    # Header: Logo + Title
    y = top_y
    logo_path = os.environ.get("LOGO_PATH", "")  # absolute path to PNG on server

    # Draw a slim brand bar
    pdf.set_fill_color(*BRAND_COLORS["brand"])
    pdf.rect(0, 0, 210, 10, "F")

    # Logo (optional)
    has_logo = False
    if logo_path and os.path.exists(logo_path):
        try:
            pdf.image(logo_path, x=left_margin, y=y, w=28)  # small logo
            has_logo = True
        except Exception as e:
            app_log(f"PDF logo load failed: {e}")

    # Title block
    pdf.set_xy(left_margin + (32 if has_logo else 0), y)
    pdf.set_text_color(*BRAND_COLORS["secondary"])  # secondary for title
    pdf.set_font("Helvetica", "B", 16)
    pdf.cell(0, 8, sanitize_for_pdf("JobReady Resume Analysis"), ln=True)

    # Meta line
    pdf.set_text_color(*BRAND_COLORS["text"])
    pdf.set_font("Helvetica", "", 10)
    meta_parts = []
    if name:
        meta_parts.append(f"Name: {name}")
    if email:
        meta_parts.append(f"Email: {email}")
    meta_parts.append(datetime.utcnow().strftime("%Y-%m-%d"))
    meta_line = sanitize_for_pdf("  |  ".join(meta_parts))
    pdf.set_x(left_margin + (32 if has_logo else 0))
    pdf.cell(0, 6, meta_line, ln=True)

    # Divider
    pdf.set_draw_color(*BRAND_COLORS["tertiary"])  # soft divider
    pdf.set_line_width(0.3)
    pdf.line(left_margin, pdf.get_y() + 2, 210 - right_margin, pdf.get_y() + 2)
    pdf.ln(6)

    # Scores section (compact badges)
    badge_h = 12
    gap = 6
    col_w = (210 - left_margin - right_margin - gap) / 2
    start_x = left_margin
    start_y = pdf.get_y()

    def draw_badge(x, y, title, value, fill_rgb):
        pdf.set_xy(x, y)
        # Background box (light tint from brand)
        pdf.set_fill_color(245, 245, 255)
        pdf.set_draw_color(*fill_rgb)
        pdf.set_line_width(0.3)
        pdf.rect(x, y, col_w, badge_h * 2 + 2, "FD")
        # Left accent strip
        pdf.set_fill_color(*fill_rgb)
        pdf.rect(x, y, 3, badge_h * 2 + 2, "F")
        # Title
        pdf.set_text_color(60, 60, 60)
        pdf.set_font("Helvetica", "", 10)
        pdf.set_xy(x + 6, y + 3)
        pdf.cell(col_w - 8, badge_h - 2, sanitize_for_pdf(title), ln=1)
        # Value
        pdf.set_text_color(*fill_rgb)
        pdf.set_font("Helvetica", "B", 14)
        pdf.set_x(x + 6)
        pdf.cell(col_w - 8, badge_h, sanitize_for_pdf(f"{value}%"), ln=0)

    draw_badge(start_x, start_y, "ATS Score", ats_score, BRAND_COLORS["brand"])
    draw_badge(start_x + col_w + gap, start_y, "Job Fit Score", job_fit_score, BRAND_COLORS["secondary"])

    pdf.set_y(start_y + badge_h * 2 + 6)
    pdf.set_x(left_margin)

    # Detail metrics as chips
    pdf.set_font("Helvetica", "", 10)
    pdf.set_text_color(50, 50, 50)

    def chip(text, x, y):
        txt = sanitize_for_pdf(text)
        tw = pdf.get_string_width(txt) + 10
        pdf.set_fill_color(244, 244, 244)  # Background from provided palette
        pdf.set_draw_color(200, 205, 220)
        pdf.set_line_width(0.2)
        pdf.rect(x, y, tw, 7, "FD")
        pdf.set_xy(x + 3, y + 1.5)
        pdf.cell(tw - 6, 4, txt)
        return x + tw + 4

    line_y = pdf.get_y()
    x = left_margin
    x = chip(f"Keyword Match: {kw_score}%", x, line_y)
    x = chip(f"Sections: {section_score}%", x, line_y)
    x = chip(f"Readability: {read_score}%", x, line_y)

    pdf.set_y(line_y + 10)
    pdf.set_x(left_margin)

    # Recommendations (single page, no truncation)
    pdf.ln(2)
    pdf.set_font("Helvetica", "B", 12)
    pdf.set_text_color(*BRAND_COLORS["secondary"])  # section heading color
    pdf.cell(0, 7, sanitize_for_pdf("Top Recommendations"), ln=True)

    pdf.set_font("Helvetica", "", 10)
    pdf.set_text_color(*BRAND_COLORS["text"])

    # Higher caps; prevent overflow by checking bottom limit
    max_basic = 5
    max_ai = 5
    bottom_limit = 297 - 18  # A4 height - bottom margin (mm)

    def add_list(label, items, max_count):
        if not items:
            return
        # If close to bottom, skip header
        if pdf.get_y() + 8 > bottom_limit:
            return
        pdf.set_font("Helvetica", "B", 10)
        pdf.set_text_color(*BRAND_COLORS["brand"])  # label color
        pdf.cell(0, 6, sanitize_for_pdf(label), ln=True)
        pdf.set_text_color(*BRAND_COLORS["text"])
        pdf.set_font("Helvetica", "", 10)
        shown = 0
        for rec in items:
            if shown >= max_count:
                break
            if pdf.get_y() + 7 > bottom_limit:
                break
            clean = str(rec).strip()
            txt = sanitize_for_pdf(f"- {clean}")
            # Ensure left margin and full width
            pdf.set_x(left_margin)
            try:
                pdf.multi_cell(0, 5, txt)
            except Exception:
                pdf.cell(0, 5, sanitize_for_pdf(f"- {clean}"), ln=True)
            shown += 1
        pdf.ln(1)

    add_list("Basic", basic_recs or [], max_basic)
    add_list("AI", ai_recs or [], max_ai)

    # Footer (fixed position at bottom of A4)
    footer_margin_bottom = 18  # bottom margin in mm
    footer_block_h = 12        # total footer block height
    footer_y = 297 - footer_margin_bottom - footer_block_h
    if pdf.get_y() > footer_y - 5:
        # If content is too close to footer area, nudge footer slightly lower safeguard
        footer_y = min(297 - footer_margin_bottom - 6, pdf.get_y() + 2)
    pdf.set_y(footer_y)
    pdf.set_draw_color(220, 220, 220)
    pdf.line(left_margin, footer_y, 210 - right_margin, footer_y)
    pdf.ln(3)
    pdf.set_font("Helvetica", "I", 9)
    pdf.set_text_color(110, 110, 110)
    footer = sanitize_for_pdf("Generated by JobReady | Mazin Digital | support@mazindigital.com")
    pdf.cell(0, 5, footer, ln=True, align="C")

    try:
        pdf.output(pdf_path)
        return pdf_filename
    except Exception as e:
        app_log(f"PDF generation failed: {e}")
        return f"{os.path.splitext(filename)[0]}_report_failed.txt"

# Utility: filename helpers
SAFE_NAME_RE = re.compile(r"[^a-zA-Z0-9_\-]+")

def _safe_base_from_name(name: str) -> str:
    base = (name or "").strip()
    if not base:
        return "candidate"
    # collapse whitespace to single spaces, then replace spaces with underscores
    base = re.sub(r"\s+", " ", base)
    base = base.strip()
    base = base.replace(" ", "_")
    # remove unsafe chars
    base = SAFE_NAME_RE.sub("", base)
    base = base.strip("._-") or "candidate"
    return base[:60]

def _extract_candidate_name(resume_text: str) -> str:
    # Simple heuristic: first non-empty line with 2-4 words in Title Case
    lines = [l.strip() for l in (resume_text or "").splitlines() if l.strip()]
    for line in lines[:10]:
        parts = [p for p in re.split(r"\s+", line) if p]
        if 1 <= len(parts) <= 5:
            # basic check: mostly alphabetic and title-like
            alpha_ratio = sum(ch.isalpha() for ch in line) / max(1, len(line))
            if alpha_ratio > 0.6:
                return line
    return ""

def _unique_filename(directory: str, filename: str) -> str:
    base, ext = os.path.splitext(filename)
    candidate = filename
    idx = 1
    while os.path.exists(os.path.join(directory, candidate)):
        candidate = f"{base}-{idx}{ext}"
        idx += 1
    return candidate

# -------------------------
# API route
# -------------------------
@app.route("/analyze", methods=["POST"])
def analyze():
    try:
        # Validate request
        if "resume" not in request.files:
            app_log("Bad request: 'resume' not in request.files. FORM keys: " + ", ".join(request.form.keys()))
            return jsonify({"error": "Missing resume file (field name must be 'resume')."}), 400
        
        if "job_description" not in request.form:
            app_log("Bad request: 'job_description' not in request.form.")
            return jsonify({"error": "Missing job_description (field name must be 'job_description')."}), 400

        # Get inputs
        resume_file = request.files["resume"]
        job_description = request.form.get("job_description", "").strip()
        name = request.form.get("name", "").strip()[:120]
        email = request.form.get("email", "").strip()[:120]

        # Validate inputs
        if resume_file.filename == "":
            app_log("Empty filename submitted.")
            return jsonify({"error": "Empty resume file."}), 400
        
        if not is_allowed_file(resume_file.filename):
            return jsonify({"error": "Invalid file type. Please upload PDF or DOCX only."}), 415
        
        if not job_description:
            return jsonify({"error": "Job description cannot be empty."}), 400

        # Save uploaded file with a unique name to avoid collisions
        original_name = secure_filename(resume_file.filename)
        base, ext = os.path.splitext(original_name)
        unique_name = f"{base}_{int(time.time())}{ext}"
        save_path = os.path.join(app.config["UPLOAD_FOLDER"], unique_name)
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
            app_log("Text extraction failed or returned empty for file: " + unique_name)
            return jsonify({"error": "Unable to extract text from resume. Ensure the PDF is not a scanned image."}), 400

        # Determine candidate name for filenames
        display_name = name or _extract_candidate_name(resume_text) or "candidate"
        safe_base = _safe_base_from_name(display_name)

        # Build final upload filename based on candidate name
        original_ext = os.path.splitext(secure_filename(resume_file.filename))[1].lower() or ".pdf"
        upload_final_name = _unique_filename(app.config["UPLOAD_FOLDER"], f"{safe_base}{original_ext}")
        # If different, rename saved file
        final_path = os.path.join(app.config["UPLOAD_FOLDER"], upload_final_name)
        try:
            if os.path.abspath(final_path) != os.path.abspath(save_path):
                os.replace(save_path, final_path)
                app_log(f"Renamed upload to {final_path}")
        except Exception as e:
            app_log(f"Failed to rename upload: {e}")
            final_path = save_path
            upload_final_name = unique_name

        # Calculate scores
        kw_score = keyword_match_score(resume_text, job_description)
        section_score = section_completeness_score(resume_text)
        read_score = readability_score(resume_text)

        # Weighted ATS score
        # ATS = 40% keywords + 30% sections + 30% readability
        ats_score = round(0.4 * kw_score + 0.3 * section_score + 0.3 * read_score)
        ats_score = max(0, min(100, ats_score))  # Clamp between 0-100

        job_fit_score = round(kw_score)  # percent of job description keywords found

        # Generate recommendations
        basic_recs = generate_rule_based_recommendations(resume_text, job_description)
        ai_recs = generate_ai_recommendations(resume_text, job_description, max_recs=3)
        if not ai_recs:
            # fallback if AI failed
            ai_recs = basic_recs[:3] if basic_recs else ["Improve readability and add role-specific keywords."]

        # Generate PDF report (use safe_base for report name)
        pdf_filename = generate_pdf_report(
            f"{safe_base}{original_ext}", name, email, ats_score, job_fit_score,
            kw_score, section_score, read_score, basic_recs, ai_recs
        )

        # Build response
        response = {
            "ats_score": ats_score,
            "job_fit_score": job_fit_score,
            "pdf_url": f"/uploads/{pdf_filename}",
            "status": "pdf_generated"
        }
        
        app_log(f"Analyze success for {upload_final_name}: ATS {ats_score}, Fit {job_fit_score}, PDF: {pdf_filename}")
        return jsonify(response), 200
        
    except Exception as e:
        app_log("Unhandled exception in /analyze: " + str(e) + "\n" + traceback.format_exc())
        return jsonify({"error": "Internal server error"}), 500

if __name__ == "__main__":
    app.run(debug=True)