/**
 * Generate Plan - Module Selection & Form Management
 * Extracted from generate_plan.php for better code organization
 */

// Global array to track selected module IDs
let selectedModuleIds = [];

/**
 * Toggle module selection
 * @param {HTMLElement} element - The module item element
 * @param {number} id - The module ID
 */
function toggleModule(element, id) {
  const index = selectedModuleIds.indexOf(id);

  if (index > -1) {
    // Deselect
    selectedModuleIds.splice(index, 1);
    element.style.borderColor = "var(--border-color)";
    element.style.boxShadow = "none";
  } else {
    // Select
    selectedModuleIds.push(id);
    element.style.borderColor = "var(--gold-primary)";
    element.style.boxShadow = "0 0 0 2px rgba(184, 134, 11, 0.2)";
  }

  // Update hidden input and counter
  document.getElementById("selectedModules").value =
    selectedModuleIds.join(",");
  document.getElementById("selectedCount").textContent =
    selectedModuleIds.length;
}

/**
 * Confirm plan deletion with SweetAlert2
 * @param {HTMLElement} button - The delete button element
 */
function confirmDelete(button) {
  Swal.fire({
    title: "Delete Study Plan?",
    text: "This action cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, delete it",
    cancelButtonText: "Cancel",
    background: "#ffffff",
    color: "#1a1a1a",
  }).then((result) => {
    if (result.isConfirmed) {
      // Submit the delete form
      button.closest("form").submit();
    }
  });
}

/**
 * Initialize module list scroll behavior
 * Call after DOM is ready
 */
function initModuleListBehavior() {
  const modulesList = document.getElementById("modulesList");
  if (!modulesList) return;

  // Make modules taller when there's space
  const items = modulesList.querySelectorAll(".selectable-module");
  items.forEach((item) => {
    item.addEventListener("click", function () {
      // Animation on select
      this.style.transform = "scale(1.01)";
      setTimeout(() => {
        this.style.transform = "scale(1)";
      }, 100);
    });
  });
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", initModuleListBehavior);
