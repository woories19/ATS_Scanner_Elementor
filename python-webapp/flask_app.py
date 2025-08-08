import os
from flask import Flask, request, jsonify
from flask_cors import CORS
from PyPDF2 import PdfReader
from docx import Document
from openai import OpenAI

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "https://mazindigital.com"}})

# --- CONFIG ---
OPENAI_API_KEY = "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"  # Replace with your key directly
client = OpenAI(api_key=OPENAI_API_KEY)

# --- HELPERS ---
def extract_text_from_file(file):
    """Extract plain text from PDF or DOCX resumes."""
    if file.filename.lower().endswith(".pdf"):
        reader = PdfReader(file)
        return "\n".join([page.extract_text() or "" for page in reader.pages])
    elif file.filename.lower().endswith(".docx"):
        doc = Document(file)
        return "\n".join([para.text for para in doc.paragraphs])
    else:
        return ""

def generate_recommendations(resume_text, job_description):
    """Use OpenAI to generate resume recommendations."""
    try:
        prompt = f"""
        You are an ATS (Applicant Tracking System) optimization assistant.
        Based on the resume and job description below, give 3 detailed, actionable recommendations
        to improve the resume so it has a higher chance of passing ATS screening.

        Resume:
        {resume_text}

        Job Description:
        {job_description}
        """

        response = client.responses.create(
            model="gpt-4o-mini",  # Safe for free-tier testing
            input=prompt,
            store=True,
        )

        return response.output_text.strip().split("\n")
    except Exception as e:
        return [f"Error generating recommendations: {str(e)}"]

# --- API ROUTES ---
@app.route("/analyze", methods=["POST"])
def analyze_resume():
    """
    POST request should include:
    - 'resume' file (.pdf or .docx)
    - 'job_description' text
    """
    try:
        if "resume" not in request.files or "job_description" not in request.form:
            return jsonify({"error": "Missing resume file or job description"}), 400

        resume_file = request.files["resume"]
        job_description = request.form["job_description"]

        resume_text = extract_text_from_file(resume_file)

        if not resume_text.strip():
            return jsonify({"error": "Unable to extract text from resume"}), 400

        # Basic fake ATS scoring
        ats_score = 57  # Placeholder scoring logic
        feedback = [
            "Use standard section headings like 'Experience' and 'Education'.",
            "Avoid graphics, tables, and non-standard fonts.",
            "Include measurable achievements and keywords from the job description."
        ]
        job_fit_score = 62  # Placeholder

        # AI recommendations
        recommendations = generate_recommendations(resume_text, job_description)

        return jsonify({
            "ats_score": ats_score,
            "feedback": feedback,
            "job_fit_score": job_fit_score,
            "recommendations": recommendations
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500

# --- RUN ---
if __name__ == "__main__":
    app.run(debug=True)
