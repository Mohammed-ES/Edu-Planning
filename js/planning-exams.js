/**
 * Planning Exam Modal - JavaScript Functions
 * Extracted from planning.php for better code organization
 */

/**
 * Display exam details in modal
 * @param {Object} exam - Exam data object with properties: exam_date, module_name, teacher, difficulty, career_importance, id, progress
 */
function showExamDetails(exam) {
  const examDate = new Date(exam.exam_date);
  const daysUntil = Math.floor((examDate - new Date()) / (1000 * 60 * 60 * 24));

  const difficultyColors = {
    EASY: "#1e8449",
    MEDIUM: "#b7791f",
    HARD: "#c0392b",
  };

  const importanceColors = {
    LOW: "#666",
    MEDIUM: "#b7791f",
    HIGH: "#dc2626",
  };

  // Build the modal content HTML
  let content = `
        <div style="margin-bottom: 20px;">
            <h4 class="exam-modal-title">
                ${exam.module_name}
            </h4>
            <p class="exam-modal-teacher">
                <i class="fas fa-user me-1"></i>${exam.teacher || "No teacher assigned"}
            </p>
        </div>
        
        <div class="exam-modal-info-grid">
            <div class="exam-modal-info-row">
                <div>
                    <span class="exam-modal-info-label">Date</span>
                    <p class="exam-modal-info-value exam-modal-date">
                        ${examDate.toLocaleDateString("en-US", { weekday: "short", year: "numeric", month: "short", day: "numeric" })}
                    </p>
                </div>
                <div>
                    <span class="exam-modal-info-label">Days Until</span>
                    <p class="exam-modal-info-value exam-modal-days ${daysUntil < 0 ? "passed" : daysUntil <= 7 ? "soon" : "future"}">
                        ${daysUntil < 0 ? "Passed" : daysUntil + " days"}
                    </p>
                </div>
            </div>
            
            <div class="exam-modal-info-row">
                <div>
                    <span class="exam-modal-info-label">Difficulty</span>
                    <p style="margin: 0;">
                        <span class="exam-modal-badge difficulty" style="color: ${difficultyColors[exam.difficulty] || "#666"};">
                            ${exam.difficulty}
                        </span>
                    </p>
                </div>
                <div>
                    <span class="exam-modal-info-label">Importance</span>
                    <p style="margin: 0;">
                        <span class="exam-modal-badge importance" style="color: ${importanceColors[exam.career_importance] || "#666"};">
                            ${exam.career_importance}
                        </span>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="exam-modal-progress-section">
            <span class="exam-modal-progress-label">Progress</span>
            <div class="exam-modal-progress-bar">
                <div class="exam-modal-progress-fill" style="width: ${exam.progress}%;"></div>
            </div>
            <p class="exam-modal-progress-percent">${exam.progress}%</p>
        </div>
        
        <div class="exam-modal-actions">
            <a href="modules/view.php?id=${exam.id}" class="exam-modal-btn exam-modal-view-btn">
                <i class="fas fa-eye exam-modal-icon"></i>View Module
            </a>
            <button onclick="closeExamModal()" class="exam-modal-btn exam-modal-close-btn">
                <i class="fas fa-times exam-modal-icon"></i>Close
            </button>
        </div>
    `;

  // Inject content and show modal
  document.getElementById("examModalContent").innerHTML = content;
  document.getElementById("examModal").classList.add("visible");
}

/**
 * Close the exam modal
 */
function closeExamModal() {
  document.getElementById("examModal").classList.remove("visible");
}

/**
 * Initialize modal close-on-outside-click behavior
 * Call this function after the DOM is ready
 */
function initExamModalEventListeners() {
  const modal = document.getElementById("examModal");
  if (!modal) return;

  // Close modal when clicking outside the content
  modal.addEventListener("click", function (e) {
    if (e.target === this) {
      closeExamModal();
    }
  });
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", initExamModalEventListeners);
