<?php
/**
 * Template Name: JobReady Results v2
 * 
 * JobReady Thank You Page Template
 * Display ATS and Job Fit scores after successful resume analysis
 * 
 * Usage: Create a new page in WordPress and use this template
 * or redirect to this page with ?ats=XX&fit=YY parameters
 */

get_header(); ?>

<?php
$ats_score    = isset($_GET['ats']) ? max(0, min(100, intval($_GET['ats']))) : 0;
$fit_score    = isset($_GET['fit']) ? max(0, min(100, intval($_GET['fit']))) : 0;
$wp_success   = isset($_GET['wp']) ? intval($_GET['wp']) : 0;
$email_success = isset($_GET['email']) ? intval($_GET['email']) : 0;
?>

<div class="jobready-thankyou-page">
    <div class="jobready-floating-shape shape-one"></div>
    <div class="jobready-floating-shape shape-two"></div>
    <div class="jobready-container">
        <div class="jobready-thankyou-content">
            
            <div class="jobready-hero">
                <span class="jobready-hero-pill">
                    <span class="jobready-dot"></span>
                    <?php esc_html_e('Analysis Complete', 'jobready'); ?>
                </span>
                <h1 class="jobready-thankyou-title">
                    <?php esc_html_e('Great news, your report is ready!', 'jobready'); ?>
                </h1>
                <p class="jobready-thankyou-subtitle">
                    <?php esc_html_e('Below is a quick snapshot of your results. A complete PDF breakdown with AI recommendations is on the way to your inbox.', 'jobready'); ?>
                </p>
            </div>

            <!-- Scores Display -->
            <div class="jobready-scores-display">
                <div class="jobready-score-card">
                    <div class="jobready-score-ring" data-score="<?php echo esc_attr($ats_score); ?>">
                        <svg class="jobready-progress-svg" viewBox="0 0 120 120" role="presentation" aria-hidden="true">
                            <circle class="jobready-progress-bg" cx="60" cy="60" r="52" pathLength="100"></circle>
                            <circle class="jobready-progress-bar" cx="60" cy="60" r="52" pathLength="100"></circle>
                        </svg>
                        <span class="jobready-score-number" data-target="<?php echo esc_attr($ats_score); ?>">0</span>
                    </div>
                    <div class="jobready-score-label"><?php esc_html_e('ATS Score', 'jobready'); ?></div>
                    <p class="jobready-score-description">
                        <?php esc_html_e('Likelihood your resume clears automated Applicant Tracking Systems.', 'jobready'); ?>
                    </p>
                    <div class="jobready-score-tag">
                        <?php echo esc_html($ats_score >= 75 ? __('Strong foundation', 'jobready') : __('Opportunities to optimize', 'jobready')); ?>
                    </div>
                </div>

                <div class="jobready-score-card">
                    <div class="jobready-score-ring" data-score="<?php echo esc_attr($fit_score); ?>">
                        <svg class="jobready-progress-svg" viewBox="0 0 120 120" role="presentation" aria-hidden="true">
                            <circle class="jobready-progress-bg" cx="60" cy="60" r="52" pathLength="100"></circle>
                            <circle class="jobready-progress-bar" cx="60" cy="60" r="52" pathLength="100"></circle>
                        </svg>
                        <span class="jobready-score-number" data-target="<?php echo esc_attr($fit_score); ?>">0</span>
                    </div>
                    <div class="jobready-score-label"><?php esc_html_e('Job Fit Score', 'jobready'); ?></div>
                    <p class="jobready-score-description">
                        <?php esc_html_e('How closely your resume aligns with the target job description.', 'jobready'); ?>
                    </p>
                    <div class="jobready-score-tag">
                        <?php echo esc_html($fit_score >= 75 ? __('Well aligned', 'jobready') : __('Needs tailoring', 'jobready')); ?>
                    </div>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="jobready-status-grid">
                <div class="jobready-status-card <?php echo $wp_success ? 'is-success' : 'is-warning'; ?>">
                    <div class="jobready-status-icon" aria-hidden="true">
                        <?php if ($wp_success): ?>
                            <span class="jr-icon jr-icon-check">
                                <svg viewBox="0 0 24 24" role="presentation">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M9 12.5l2.2 2.2L15.5 10"></path>
                                </svg>
                            </span>
                        <?php else: ?>
                            <span class="jr-icon jr-icon-alert">
                                <svg viewBox="0 0 24 24" role="presentation">
                                    <path d="M12 4l8 14H4z"></path>
                                    <line x1="12" y1="10" x2="12" y2="14"></line>
                                    <circle cx="12" cy="17" r="0.9"></circle>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4><?php esc_html_e('Lead Capture', 'jobready'); ?></h4>
                        <p>
                            <?php
                            echo $wp_success
                                ? esc_html__('Your information is safely stored in our system.', 'jobready')
                                : esc_html__('We could not store your information automatically. Your analysis is still complete.', 'jobready');
                            ?>
                        </p>
                    </div>
                </div>
                <div class="jobready-status-card <?php echo $email_success ? 'is-success' : 'is-warning'; ?>">
                    <div class="jobready-status-icon" aria-hidden="true">
                        <?php if ($email_success): ?>
                            <span class="jr-icon jr-icon-mail">
                                <svg viewBox="0 0 24 24" role="presentation">
                                    <rect x="4" y="6" width="16" height="12" rx="2"></rect>
                                    <polyline points="4.5 7 12 12 19.5 7"></polyline>
                                </svg>
                            </span>
                        <?php else: ?>
                            <span class="jr-icon jr-icon-alert">
                                <svg viewBox="0 0 24 24" role="presentation">
                                    <path d="M12 4l8 14H4z"></path>
                                    <line x1="12" y1="10" x2="12" y2="14"></line>
                                    <circle cx="12" cy="17" r="0.9"></circle>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4><?php esc_html_e('Email Delivery', 'jobready'); ?></h4>
                        <p>
                            <?php
                            echo $email_success
                                ? esc_html__('Expect your full PDF report in the next few minutes.', 'jobready')
                                : esc_html__('Email delivery may be delayed. Check spam or contact us if it does not arrive.', 'jobready');
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="jobready-next-steps">
                <h3><?php esc_html_e('What happens next?', 'jobready'); ?></h3>
                <ol>
                    <li>
                        <strong><?php esc_html_e('Check your inbox', 'jobready'); ?></strong>
                        <span><?php esc_html_e('We email your PDF report with personalized tips and keywords.', 'jobready'); ?></span>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Review AI recommendations', 'jobready'); ?></strong>
                        <span><?php esc_html_e('Apply the quick wins highlighted in your report to raise your score.', 'jobready'); ?></span>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Iterate & re-scan', 'jobready'); ?></strong>
                        <span><?php esc_html_e('Upload the improved version to watch your scores climb.', 'jobready'); ?></span>
                    </li>
                </ol>

                <?php if (!$wp_success || !$email_success): ?>
                <div class="jobready-integration-warning" role="alert">
                    <p><strong><?php esc_html_e('Heads up:', 'jobready'); ?></strong> <?php esc_html_e('Some automations did not finish. Reach out if you need us to resend anything manually.', 'jobready'); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tips -->
            <div class="jobready-pro-tips">
                <h3><?php esc_html_e('Quick win ideas', 'jobready'); ?></h3>
                <div class="jobready-tips-grid">
                    <div class="jobready-tip-card">
                        <h5><?php esc_html_e('Keyword Alignment', 'jobready'); ?></h5>
                        <p><?php esc_html_e('Mirror the phrasing of critical job requirements inside your experience bullets.', 'jobready'); ?></p>
                    </div>
                    <div class="jobready-tip-card">
                        <h5><?php esc_html_e('Formatting Boost', 'jobready'); ?></h5>
                        <p><?php esc_html_e('Use clean headings, bullet points, and consistent dates to keep ATS parsing flawless.', 'jobready'); ?></p>
                    </div>
                    <div class="jobready-tip-card">
                        <h5><?php esc_html_e('Metric-Driven Stories', 'jobready'); ?></h5>
                        <p><?php esc_html_e('Translate responsibilities into measurable impact to stand out to both ATS and recruiters.', 'jobready'); ?></p>
                    </div>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="jobready-cta-buttons">
                <a href="https://resume.mazindigital.com/" class="jobready-btn jobready-btn-secondary">
                    <?php esc_html_e('Analyze Another Resume', 'jobready'); ?>
                </a>
                
                <a href="https://resume.mazindigital.com/#a-contactus" class="jobready-btn jobready-btn-primary">
                    <?php esc_html_e('Contact Support', 'jobready'); ?>
                </a>
            </div>

            <!-- Additional Info -->
            <div class="jobready-additional-info">
                <p>
                    <?php esc_html_e('Didn\'t receive your email? Check your spam folder or', 'jobready'); ?>
                    <a href="mailto:support@mazindigital.com"><?php esc_html_e('contact us', 'jobready'); ?></a>
                    <?php esc_html_e('so we can resend your report.', 'jobready'); ?>
                </p>
            </div>

        </div>
    </div>
</div>

<style>
.jobready-thankyou-page {
    position: relative;
    width: 100vw;
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
    padding: clamp(32px, 6vw, 70px) 0 clamp(36px, 8vw, 80px);
    background: radial-gradient(circle at top, #f0f2ff 0%, #ffffff 60%);
    overflow: hidden;
    min-height: 85vh;
}

.jobready-floating-shape {
    position: absolute;
    width: 240px;
    height: 240px;
    background: rgba(89, 68, 249, 0.08);
    border-radius: 50%;
    filter: blur(10px);
    animation: jrFloat 16s ease-in-out infinite;
    z-index: 0;
}

.jobready-floating-shape.shape-one {
    top: -140px;
    right: -20px;
}

.jobready-floating-shape.shape-two {
    bottom: -100px;
    left: -80px;
    animation-delay: 4s;
}

.jobready-container {
    position: relative;
    max-width: 920px;
    margin: 0 auto;
    padding: 0 24px;
    z-index: 1;
}

.jobready-thankyou-content {
    background: #fff;
    border-radius: 24px;
    padding: 44px 40px;
    box-shadow: 0 24px 80px rgba(17, 24, 39, 0.08);
    text-align: center;
}

.jobready-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    background: rgba(89, 68, 249, 0.12);
    color: #5944F9;
}

.jobready-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #19c37d;
    box-shadow: 0 0 0 6px rgba(25, 195, 125, 0.25);
    animation: jrPulse 2.2s ease-in-out infinite;
}

.jobready-hero {
    margin-bottom: 28px;
}

.jobready-thankyou-title {
    font-size: 2.6rem;
    font-weight: 700;
    color: #111827;
    margin: 18px 0 12px;
}

.jobready-thankyou-subtitle {
    font-size: 1.1rem;
    color: #4b5563;
    max-width: 640px;
    margin: 0 auto;
    line-height: 1.7;
}

.jobready-scores-display {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.jobready-score-card {
    border: 1px solid #eef0ff;
    border-radius: 20px;
    padding: 26px 22px 30px;
    background: linear-gradient(180deg, #fbfbff 0%, #ffffff 70%);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.jobready-score-ring {
    width: 150px;
    height: 150px;
    margin: 0 auto 16px;
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle, #ffffff 62%, rgba(244, 244, 255, 0.8) 100%);
    border-radius: 50%;
    box-shadow: 0 18px 35px rgba(15, 23, 42, 0.08);
}

.jobready-progress-svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.jobready-progress-bg,
.jobready-progress-bar {
    fill: none;
    stroke-width: 12;
    stroke-dasharray: 100;
    stroke-dashoffset: 100;
}

.jobready-progress-bg {
    stroke: #e6e9fb;
}

.jobready-progress-bar {
    stroke: #5944F9;
    stroke-linecap: round;
    transition: stroke-dashoffset 0.4s ease;
}

.jobready-score-number {
    position: absolute;
    font-size: 2.4rem;
    font-weight: 700;
    color: #111827;
    z-index: 1;
    line-height: 1;
}

.jobready-score-number::after {
    content: '%';
    font-size: 1.2rem;
    color: #6b7280;
    margin-left: 4px;
}

.jobready-score-label {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 6px;
}

.jobready-score-description {
    font-size: 0.92rem;
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 12px;
}

.jobready-score-tag {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(89, 68, 249, 0.1);
    color: #4430d6;
    font-weight: 600;
    font-size: 0.86rem;
}

.jobready-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.jobready-status-card {
    display: flex;
    gap: 18px;
    padding: 24px;
    border-radius: 18px;
    border: 1px solid #edf2ff;
    background: #fff;
    text-align: left;
    align-items: center;
}

.jobready-status-card h4 {
    margin: 0 0 6px;
    color: #111827;
    font-size: 1rem;
}

.jobready-status-card p {
    margin: 0;
    color: #6b7280;
    line-height: 1.5;
    font-size: 0.92rem;
}

.jobready-status-icon {
    width: 56px;
    height: 56px;
    min-width: 56px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.jobready-status-card.is-success .jobready-status-icon {
    background: rgba(25, 195, 125, 0.14);
    color: #19c37d;
}

.jobready-status-card.is-warning .jobready-status-icon {
    background: rgba(245, 158, 11, 0.18);
    color: #f59e0b;
}

.jr-icon {
    display: inline-flex;
    width: 26px;
    height: 26px;
}

.jr-icon svg {
    width: 100%;
    height: 100%;
    stroke: currentColor;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
}

.jobready-next-steps {
    text-align: left;
    margin-bottom: 40px;
    padding: 28px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(233, 236, 255, 0.6), rgba(255, 255, 255, 0.9));
    border: 1px solid #e4e6ff;
}

.jobready-next-steps h3 {
    margin: 0 0 20px;
    font-size: 1.3rem;
    color: #111827;
}

.jobready-next-steps ol {
    list-style: none;
    padding: 0;
    margin: 16px 0 0;
    counter-reset: jr-steps;
}

.jobready-next-steps li {
    position: relative;
    padding-left: 56px;
    margin-bottom: 18px;
    counter-increment: jr-steps;
}

.jobready-next-steps li strong {
    display: block;
    color: #1f2937;
    margin-bottom: 4px;
}

.jobready-next-steps li span {
    color: #6b7280;
    font-size: 0.96rem;
}

.jobready-next-steps li::before {
    content: counter(jr-steps);
    position: absolute;
    left: 0;
    top: 4px;
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: #5944F9;
    color: #fff;
    font-weight: 600;
    display: grid;
    place-items: center;
    box-shadow: 0 8px 16px rgba(89, 68, 249, 0.25);
}

.jobready-pro-tips {
    text-align: left;
    margin-bottom: 40px;
}

.jobready-pro-tips h3 {
    margin-bottom: 18px;
    color: #111827;
}

.jobready-tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
}

.jobready-tip-card {
    border-radius: 18px;
    padding: 20px;
    background: #fff;
    border: 1px solid #f0f3ff;
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.jobready-tip-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 30px rgba(15, 23, 42, 0.12);
}

.jobready-tip-card h5 {
    margin: 0 0 8px;
    font-size: 1rem;
    color: #111827;
}

.jobready-tip-card p {
    margin: 0;
    color: #6b7280;
    line-height: 1.5;
}

.jobready-cta-buttons {
    display: flex;
    gap: 18px;
    justify-content: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
}

.jobready-btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    padding: 14px 32px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-width: 200px;
}

.jobready-btn-primary {
    background: #5944F9;
    color: #fff;
    box-shadow: 0 12px 30px rgba(89, 68, 249, 0.3);
}

.jobready-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 36px rgba(89, 68, 249, 0.35);
    color: white;
}

.jobready-btn-secondary {
    background: #fff;
    color: #5944F9;
    border: 2px solid rgba(89, 68, 249, 0.4);
}

.jobready-btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
}

.jobready-additional-info {
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
    color: #6b7280;
    font-size: 0.95rem;
}

.jobready-additional-info a {
    color: #5944F9;
    font-weight: 600;
}

.jobready-additional-info a:hover {
    text-decoration: underline;
}

.jobready-integration-warning {
    margin-top: 18px;
    padding: 14px 16px;
    border-radius: 12px;
    background: #fff7ec;
    border: 1px solid #ffd6a8;
    color: #a16207;
}

@media (max-width: 768px) {
    .jobready-thankyou-page {
        padding: 36px 0 56px;
    }
    .jobready-thankyou-content {
        padding: 32px 22px;
    }
    .jobready-hero-pill {
        font-size: 0.78rem;
        padding: 4px 12px;
    }
    .jobready-thankyou-title {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    .jobready-thankyou-subtitle {
        font-size: 0.98rem;
        line-height: 1.5;
        margin-bottom: 26px;
    }
    .jobready-scores-display {
        gap: 18px;
    }
    .jobready-score-ring {
        width: 130px;
        height: 130px;
    }
    .jobready-score-number {
        font-size: 2.1rem;
    }
    .jobready-score-card {
        padding: 22px 18px 26px;
    }
    .jobready-score-description {
        font-size: 0.88rem;
    }
    .jobready-next-steps li {
        padding-left: 44px;
    }
    .jobready-btn {
        width: 100%;
        min-width: unset;
    }
}

@media (max-width: 600px) {
    .jobready-hero {
        margin-bottom: 18px;
    }
    .jobready-thankyou-subtitle {
        font-size: 0.95rem;
    }
    .jobready-status-grid,
    .jobready-scores-display {
        gap: 16px;
    }
    .jobready-score-card,
    .jobready-status-card,
    .jobready-next-steps,
    .jobready-pro-tips {
        padding: 20px;
    }
    .jobready-pro-tips {
        margin-bottom: 28px;
    }
    .jobready-tips-grid {
        display: flex;
        overflow-x: auto;
        gap: 12px;
        padding-bottom: 6px;
        scroll-snap-type: x proximity;
    }
    .jobready-tip-card {
        flex: 0 0 220px;
        scroll-snap-align: start;
    }
    .jobready-status-card {
        padding: 18px;
    }
    .jobready-status-icon {
        width: 48px;
        height: 48px;
    }
    .jobready-status-card h4 {
        font-size: 0.95rem;
    }
    .jobready-next-steps {
        padding: 20px;
    }
}

@keyframes jrPulse {
    0% { transform: scale(1); opacity: 1; }
    70% { transform: scale(1.3); opacity: 0.2; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes jrFloat {
    0% { transform: translate3d(0, 0, 0); }
    50% { transform: translate3d(20px, -30px, 0); }
    100% { transform: translate3d(0, 0, 0); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var duration = 1200;
    document.querySelectorAll('.jobready-score-card').forEach(function (card) {
        var ring = card.querySelector('.jobready-score-ring');
        var counter = card.querySelector('.jobready-score-number');
        var circle = card.querySelector('.jobready-progress-bar');
        if (!ring || !counter || !circle) return;

        var target = parseInt(counter.getAttribute('data-target'), 10) || 0;
        circle.style.strokeDashoffset = 100;

        var start = null;
        function animate(timestamp) {
            if (!start) start = timestamp;
            var progress = Math.min((timestamp - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3); // ease-out
            var value = Math.round(eased * target);
            var normalized = Math.min(value, target);
            counter.textContent = value;
            circle.style.strokeDashoffset = 100 - normalized;
            if (progress < 1) {
                window.requestAnimationFrame(animate);
            }
        }

        window.requestAnimationFrame(animate);
    });
});
</script>

<?php get_footer(); ?>

