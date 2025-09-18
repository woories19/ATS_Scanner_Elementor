<?php
/**
 * Template Name: JobReady Results
 * 
 * JobReady Thank You Page Template
 * Display ATS and Job Fit scores after successful resume analysis
 * 
 * Usage: Create a new page in WordPress and use this template
 * or redirect to this page with ?ats=XX&fit=YY parameters
 */

get_header(); ?>

<div class="jobready-thankyou-page">
    <div class="container">
        <div class="jobready-thankyou-content">
            
            <!-- Success Icon -->
            <div class="jobready-success-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" 
                          stroke="#2e7d32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Thank You Message -->
            <h1 class="jobready-thankyou-title">
                <?php esc_html_e('Thank You!', 'jobready'); ?>
            </h1>
            
            <p class="jobready-thankyou-subtitle">
                <?php esc_html_e('Your resume has been analyzed successfully. Here are your immediate results:', 'jobready'); ?>
            </p>

            <!-- Scores Display -->
            <div class="jobready-scores-display">
                <?php
                $ats_score = isset($_GET['ats']) ? intval($_GET['ats']) : 0;
                $fit_score = isset($_GET['fit']) ? intval($_GET['fit']) : 0;
                ?>
                
                <div class="jobready-score-card">
                    <div class="jobready-score-value"><?php echo esc_html($ats_score); ?>%</div>
                    <div class="jobready-score-label"><?php esc_html_e('ATS Score', 'jobready'); ?></div>
                    <div class="jobready-score-description">
                        <?php esc_html_e('How well your resume passes through Applicant Tracking Systems', 'jobready'); ?>
                    </div>
                </div>

                <div class="jobready-score-card">
                    <div class="jobready-score-value"><?php echo esc_html($fit_score); ?>%</div>
                    <div class="jobready-score-label"><?php esc_html_e('Job Fit Score', 'jobready'); ?></div>
                    <div class="jobready-score-description">
                        <?php esc_html_e('How well your resume matches the job description', 'jobready'); ?>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="jobready-next-steps">
                <h3><?php esc_html_e('What Happens Next?', 'jobready'); ?></h3>
                <ul>
                    <?php
                    $wp_success = isset($_GET['wp']) ? intval($_GET['wp']) : 0;
                    $email_success = isset($_GET['email']) ? intval($_GET['email']) : 0;
                    
                    if ($wp_success) {
                        echo '<li>✅ Your information has been saved to our system</li>';
                    } else {
                        echo '<li>⚠️ There was an issue saving your information (but your analysis is complete)</li>';
                    }
                    
                    if ($email_success) {
                        echo '<li>📧 You\'ll receive a detailed PDF report via email within 5 minutes</li>';
                    } else {
                        echo '<li>⚠️ Email delivery may be delayed - check your spam folder</li>';
                    }
                    ?>
                    <li><?php esc_html_e('🤖 AI-powered recommendations to improve your resume', 'jobready'); ?></li>
                    <li><?php esc_html_e('📊 Detailed breakdown of keyword matches and section completeness', 'jobready'); ?></li>
                    <li><?php esc_html_e('💡 Actionable tips to boost your ATS score', 'jobready'); ?></li>
                </ul>
                
                <?php if (!$wp_success || !$email_success): ?>
                <div class="jobready-integration-warning">
                    <p><strong>Note:</strong> Some features may not be working properly. Please contact support if you don't receive your email report.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- CTA Buttons -->
            <div class="jobready-cta-buttons">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="jobready-btn jobready-btn-secondary">
                    <?php esc_html_e('Analyze Another Resume', 'jobready'); ?>
                </a>
                
                <a href="mailto:support@mazindigital.com" class="jobready-btn jobready-btn-primary">
                    <?php esc_html_e('Contact Support', 'jobready'); ?>
                </a>
            </div>

            <!-- Additional Info -->
            <div class="jobready-additional-info">
                <p>
                    <?php esc_html_e('Didn\'t receive your email? Check your spam folder or', 'jobready'); ?>
                    <a href="mailto:support@mazindigital.com"><?php esc_html_e('contact us', 'jobready'); ?></a>
                    <?php esc_html_e('for assistance.', 'jobready'); ?>
                </p>
            </div>

        </div>
    </div>
</div>

<style>
/* Thank You Page Styles */
.jobready-thankyou-page {
    padding: 60px 0;
    background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
    min-height: 80vh;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.jobready-thankyou-content {
    text-align: center;
    background: #fff;
    border-radius: 16px;
    padding: 48px 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}

.jobready-success-icon {
    margin-bottom: 24px;
}

.jobready-thankyou-title {
    font-size: 2.5rem;
    color: #2e7d32;
    margin-bottom: 16px;
    font-weight: 700;
}

.jobready-thankyou-subtitle {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 40px;
    line-height: 1.6;
}

.jobready-scores-display {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 40px;
}

.jobready-score-card {
    background: #f8f9ff;
    border-radius: 12px;
    padding: 24px;
    border: 2px solid #e8eaff;
}

.jobready-score-value {
    font-size: 3rem;
    font-weight: 700;
    color: #5944F9;
    margin-bottom: 8px;
}

.jobready-score-label {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
}

.jobready-score-description {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.4;
}

.jobready-next-steps {
    text-align: left;
    margin-bottom: 40px;
    padding: 24px;
    background: #f8f9ff;
    border-radius: 12px;
}

.jobready-next-steps h3 {
    color: #333;
    margin-bottom: 16px;
    font-size: 1.2rem;
}

.jobready-next-steps ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.jobready-next-steps li {
    padding: 8px 0;
    color: #555;
    font-size: 0.95rem;
}

.jobready-cta-buttons {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.jobready-btn {
    display: inline-block;
    padding: 14px 28px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    min-width: 160px;
}

.jobready-btn-primary {
    background: #5944F9;
    color: #fff;
}

.jobready-btn-primary:hover {
    background: #4935E8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(89, 68, 249, 0.25);
}

.jobready-btn-secondary {
    background: transparent;
    color: #5944F9;
    border: 2px solid #5944F9;
}

.jobready-btn-secondary:hover {
    background: #5944F9;
    color: #fff;
    transform: translateY(-2px);
}

.jobready-additional-info {
    padding-top: 24px;
    border-top: 1px solid #eee;
    color: #666;
    font-size: 0.9rem;
}

.jobready-additional-info a {
    color: #5944F9;
    text-decoration: none;
}

.jobready-additional-info a:hover {
    text-decoration: underline;
}

.jobready-integration-warning {
    margin-top: 16px;
    padding: 12px 16px;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    color: #856404;
}

.jobready-integration-warning p {
    margin: 0;
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .jobready-thankyou-page {
        padding: 40px 0;
    }
    
    .jobready-thankyou-content {
        padding: 32px 24px;
    }
    
    .jobready-thankyou-title {
        font-size: 2rem;
    }
    
    .jobready-scores-display {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .jobready-cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .jobready-btn {
        width: 100%;
        max-width: 280px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 16px;
    }
    
    .jobready-thankyou-content {
        padding: 24px 20px;
    }
    
    .jobready-thankyou-title {
        font-size: 1.8rem;
    }
    
    .jobready-score-value {
        font-size: 2.5rem;
    }
}
</style>

<?php get_footer(); ?>

