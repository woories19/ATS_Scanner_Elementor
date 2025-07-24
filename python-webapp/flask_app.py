# ats_backend.py (Refactored Flask Backend)

from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import docx2txt
import fitz  # PyMuPDF
from datetime import datetime

app = Flask(__name__)
CORS(app)

UPLOAD_FOLDER = 'uploads'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# --- File Text Extractor ---
def extract_text(file_path):
    _, ext = os.path.splitext(file_path.lower())
    if ext == ".docx":
        return docx2txt.process(file_path)
    elif ext == ".pdf":
        text = ""
        with fitz.open(file_path) as pdf:
            for page in pdf:
                text += page.get_text()
        return text
    return None

# --- ATS Scoring Logic ---
def ats_score(text):
    score = 0
    feedback = []
    visible_recommendations = []
    locked_recommendations = []

    if "@" in text:
        score += 10
    else:
        feedback.append("Missing email address.")
        visible_recommendations.append("Add a valid email address to your contact section.")

    if any(c.isdigit() for c in text if c in '+0123456789'):
        score += 10
    else:
        feedback.append("Missing phone number.")
        visible_recommendations.append("Include a professional phone number.")

    sections = ["experience", "education", "skills", "summary"]
    found_sections = [s for s in sections if s in text.lower()]
    score += len(found_sections) * 10
    for s in sections:
        if s not in text.lower():
            feedback.append(f"Missing section: {s.capitalize()}")
            locked_recommendations.append(f"Include a clear '{s.capitalize()}' section.")

    if "-" in text or "•" in text:
        score += 10
    else:
        feedback.append("No bullet points found.")
        locked_recommendations.append("Use bullet points for clarity and structure.")

    score = min(score, 100)
    return score, feedback, visible_recommendations, locked_recommendations

# --- Keyword Matching ---
def extract_keywords(text):
    words = [w.strip(".,") for w in text.lower().split()]
    stopwords = {"and", "or", "with", "the", "a", "an", "in", "on", "to", "of", "at", "by", "for", "from", "is", "as", "that"}
    return sorted(set(w for w in words if w.isalpha() and w not in stopwords))

def compare_keywords(resume_text, job_description):
    resume_keywords = set(extract_keywords(resume_text))
    job_keywords = set(extract_keywords(job_description))
    match = resume_keywords.intersection(job_keywords)
    if not job_keywords:
        return {'match_percentage': 0, 'matching_keywords': [], 'missing_keywords': []}

    match_percentage = (len(match) / len(job_keywords)) * 100
    return {
        'match_percentage': round(match_percentage, 2),
        'matching_keywords': sorted(match),
        'missing_keywords': sorted(job_keywords - resume_keywords)
    }

# --- API Endpoint ---
@app.route('/api/analyze', methods=['POST'])
def analyze():
    file = request.files.get('resume')
    job_desc = request.form.get('job_description', '')
    user_email = request.form.get('email', '')
    user_name = request.form.get('name', '')

    if not file:
        return jsonify({'error': 'No resume file provided.'}), 400

    filepath = os.path.join(UPLOAD_FOLDER, file.filename)
    file.save(filepath)
    text = extract_text(filepath)
    if not text:
        return jsonify({'error': 'Unsupported file format.'}), 400

    ats, feedback, visible, locked = ats_score(text)
    keyword_data = compare_keywords(text, job_desc)

    response = {
        'ats_score': ats,
        'feedback': feedback,
        'visible_recommendations': visible,
        'locked_recommendations': locked,
        'keyword_analysis': keyword_data,
        'user_name': user_name,
        'user_email': user_email
    }

    return jsonify(response)

if __name__ == '__main__':
    app.run(debug=True)
