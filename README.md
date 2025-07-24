# ATS Resume Scanner – Elementor Plugin

ATS Resume Scanner is a custom-built Elementor widget that allows users to upload their resumes and instantly receive feedback on how well it aligns with Applicant Tracking Systems (ATS). This plugin integrates with a Flask-powered backend to analyze resumes for structure, keyword relevance, and formatting — giving users a score and actionable suggestions to improve their chances of getting noticed.

---

## 🚀 Features

- Upload PDF or DOCX resume files.
- Analyze resumes for common ATS-friendly elements.
- Real-time ATS Score with animated progress visualization.
- Section-wise feedback (e.g., missing email, phone, experience, etc.).
- Extracted keyword list from resume text.
- Optional Job Description input for keyword comparison.
- Matching/missing keywords percentage analysis.
- Clean, mobile-friendly UI designed with Elementor.

---

## 🛠️ Tech Stack

- **Frontend:** Elementor (WordPress), jQuery, HTML5, CSS3
- **Backend:** Python Flask (deployed on PythonAnywhere)
- **File Support:** PDF, DOCX
- **Visualization:** Circular progress bar, styled feedback sections

---

## 🔧 Installation

1. Clone this repository or download the ZIP file.
2. Upload and activate the plugin on your WordPress site.
3. Make sure the Flask API backend is hosted and live (e.g., on PythonAnywhere).
4. Set the API URL in the Elementor widget settings.
5. Drag the widget into any Elementor page and publish.

---

## 📦 Folder Structure

ats-scanner-elementor/
│
├── assets/
│ ├── style.css
│ └── ats-scanner.js
├── includes/
│ └── widgets/
│   └── ats-scanner-widget.php
├── ats-scanner-elementor.php
└── README.md

---

## 📌 Roadmap

- [x] Basic functionality (upload + analyze)
- [x] Keyword comparison feature
- [x] Circular progress visualization
- [ ] Enhanced ATS logic (AI scoring, job title match)
- [ ] Resume format suggestions
- [ ] Multilingual support
- [ ] Save/download analysis as PDF

---

## 🤝 Contributing

Contributions are welcome! If you'd like to improve the UI, logic, or suggest features, feel free to fork the repo and create a pull request.

---

## 📃 License

This project is licensed under the [MIT License](LICENSE).

---

## 🌐 Live Demo

You can try out the working demo here:  
**[https://mazindigital.com/ats-scanner/](https://mazindigital.com/ats-scanner/)**

---
