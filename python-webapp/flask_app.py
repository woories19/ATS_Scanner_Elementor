from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import docx2txt
import fitz  # PyMuPDF

app = Flask(__name__)
CORS(app)
UPLOAD_FOLDER = 'uploads'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# --- File Reader ---
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
    else:
        return None

# --- ATS Score ---
def ats_score(text):
    score = 0
    feedback = []

    if "@" in text.lower():
        score += 10
    else:
        feedback.append("Missing email address.")

    if "phone" in text.lower() or "+" in text:
        score += 10
    else:
        feedback.append("Missing phone number.")

    sections = ["experience", "education", "skills", "summary"]
    found_sections = [s for s in sections if s in text.lower()]
    score += len(found_sections) * 10
    for s in sections:
        if s not in text.lower():
            feedback.append(f"Missing section: {s.capitalize()}")

    if "-" in text or "•" in text:
        score += 10
    else:
        feedback.append("No bullet points found.")

    return min(score, 100), feedback

# --- Keyword Logic ---
def extract_keywords(text):
    words = [w.strip(".,") for w in text.lower().split()]
    stopwords = {"and", "or", "with", "the", "a", "an", "in", "on", "to", "of", "at", "by", "for", "from", "is", "as", "that"}
    return sorted(set(w for w in words if w.isalpha() and w not in stopwords))

def compare_keywords(resume_text, job_description):
    resume_keywords = set(extract_keywords(resume_text))
    job_keywords = set(extract_keywords(job_description))
    matching_keywords = resume_keywords.intersection(job_keywords)

    if len(job_keywords) == 0:
        match_percentage = 0
    else:
        match_percentage = (len(matching_keywords) / len(job_keywords)) * 100

    return {
        'matching_keywords': sorted(matching_keywords),
        'missing_keywords': sorted(job_keywords - resume_keywords),
        'match_percentage': round(match_percentage, 2)
    }

# --- API Route ---
@app.route('/api/analyze', methods=['POST'])
def analyze():
    file = request.files.get('resume')
    job_description = request.form.get('job_description', '')

    if not file:
        return jsonify({'error': 'No resume uploaded'}), 400

    filepath = os.path.join(UPLOAD_FOLDER, file.filename)
    file.save(filepath)

    text = extract_text(filepath)
    if not text:
        return jsonify({'error': 'Unsupported file format'}), 400

    score, feedback = ats_score(text)
    keywords = extract_keywords(text)
    keyword_analysis = compare_keywords(text, job_description) if job_description else {}

    # Optional: delete file after processing
    os.remove(filepath)

    return jsonify({
        'ats_score': score,
        'feedback': feedback,
        'keywords': keywords[:25],
        'keyword_analysis': keyword_analysis
    })

# --- Start App (Only for local testing, not used in PythonAnywhere deployment) ---
if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
