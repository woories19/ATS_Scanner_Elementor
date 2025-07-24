# Re-import after environment reset
from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import docx2txt
import fitz  # PyMuPDF

app = Flask(__name__)
CORS(app)

UPLOAD_FOLDER = 'uploads'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# --- Utility functions ---
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

def ats_score(text):
    score = 0
    recommendations = []

    if "@" in text:
        score += 10
    else:
        recommendations.append("Add a professional email address.")

    if "phone" in text.lower() or "+" in text:
        score += 10
    else:
        recommendations.append("Include a valid phone number.")

    for section in ["experience", "education", "skills", "summary"]:
        if section in text.lower():
            score += 10
        else:
            recommendations.append(f"Add a clear '{section.capitalize()}' section.")

    if "-" in text or "•" in text:
        score += 10
    else:
        recommendations.append("Use bullet points for readability.")

    return min(score, 100), recommendations

def extract_keywords(text):
    words = [w.strip(".,") for w in text.lower().split()]
    stopwords = {"and", "or", "with", "the", "a", "an", "in", "on", "to", "of", "at", "by", "for", "from", "is", "as", "that"}
    return sorted(set(w for w in words if w.isalpha() and w not in stopwords))

def compare_keywords(resume_text, job_description):
    resume_keywords = set(extract_keywords(resume_text))
    job_keywords = set(extract_keywords(job_description))
    matching = resume_keywords.intersection(job_keywords)

    match_percentage = (len(matching) / len(job_keywords)) * 100 if job_keywords else 0

    return {
        'match_percentage': round(match_percentage, 2),
        'matching_keywords': sorted(matching),
        'missing_keywords': sorted(job_keywords - resume_keywords),
    }

# --- Main API Endpoint ---
@app.route('/api/analyze', methods=['POST'])
def analyze():
    file = request.files.get('resume')
    job_description = request.form.get('job_description', '')

    if not file:
        return jsonify({'error': 'Resume not uploaded'}), 400

    filepath = os.path.join(UPLOAD_FOLDER, file.filename)
    file.save(filepath)
    text = extract_text(filepath)

    if not text:
        return jsonify({'error': 'Unsupported file format'}), 400

    score, recommendations = ats_score(text)
    keyword_data = compare_keywords(text, job_description) if job_description else None
    keywords = extract_keywords(text)

    # Split visible and locked recommendations
    visible_recommendations = recommendations[:2]
    locked_recommendations = recommendations[2:]

    return jsonify({
        'ats_score': score,
        'visible_recommendations': visible_recommendations,
        'locked_recommendations': locked_recommendations,
        'keyword_analysis': keyword_data,
        'keywords': keywords
    })

if __name__ == '__main__':
    app.run(debug=True)
