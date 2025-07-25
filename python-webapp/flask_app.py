import os
import requests
import fitz  # PyMuPDF
import docx
from flask import Flask, request, jsonify
from werkzeug.utils import secure_filename
from dotenv import load_dotenv

# Load .env for Hugging Face token
load_dotenv()
HUGGINGFACE_API_TOKEN = os.getenv("HF_API_TOKEN")

app = Flask(__name__)
UPLOAD_FOLDER = 'uploads'
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER

# Ensure upload folder exists
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# Hugging Face inference settings
HUGGINGFACE_API_URL = "https://api-inference.huggingface.co/models/google/flan-t5-small"
headers = {
    "Authorization": f"Bearer {HUGGINGFACE_API_TOKEN}"
}

# === Helper functions ===

def extract_text_from_pdf(file_path):
    text = ""
    with fitz.open(file_path) as doc:
        for page in doc:
            text += page.get_text()
    return text.strip()

def extract_text_from_docx(file_path):
    doc = docx.Document(file_path)
    return "\n".join([para.text for para in doc.paragraphs if para.text.strip()])

def extract_resume_text(file_path):
    if file_path.lower().endswith('.pdf'):
        return extract_text_from_pdf(file_path)
    elif file_path.lower().endswith('.docx'):
        return extract_text_from_docx(file_path)
    else:
        return ""

def generate_recommendations_with_hf(resume_text, job_description):
    prompt = f"""
    You are a resume analysis assistant.
    Analyze the following resume and provide 5 concise, useful, and actionable suggestions to improve it
    for better ATS compatibility and job fit.

    Resume:
    {resume_text}

    Job Description:
    {job_description}

    Respond only with bullet points in plain text.
    """

    response = requests.post(HUGGINGFACE_API_URL, headers=headers, json={"inputs": prompt})

    if response.status_code == 200:
        result = response.json()
        if isinstance(result, list) and 'generated_text' in result[0]:
            lines = result[0]['generated_text'].split("\n")
            return [line.strip("-• ") for line in lines if line.strip()]
    return ["Unable to generate recommendations at the moment. Please try again later."]

# === Main route ===

@app.route('/api/analyze', methods=['POST'])
def analyze_resume():
    file = request.files.get('resume')
    job_desc = request.form.get('job_description')

    if not file or not job_desc:
        return jsonify({'error': 'Missing resume or job description'}), 400

    filename = secure_filename(file.filename)
    filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
    file.save(filepath)

    resume_text = extract_resume_text(filepath)
    if not resume_text:
        return jsonify({'error': 'Could not extract text from resume'}), 500

    recommendations = generate_recommendations_with_hf(resume_text, job_desc)

    return jsonify({
        "ats_score": 57,  # Placeholder, will be dynamic in later phases
        "job_fit_score": 62,  # Placeholder
        "feedback": [
            "Use standard section headings like 'Experience' and 'Education'.",
            "Avoid graphics, tables, and non-standard fonts.",
            "Include measurable achievements and keywords from the job description."
        ],
        "recommendations": recommendations
    })

# === Run server ===

if __name__ == '__main__':
    app.run(debug=True)
