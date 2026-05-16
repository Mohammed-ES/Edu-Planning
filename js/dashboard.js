function initProgressChart() {
  if (typeof Chart === "undefined") {
    console.warn("Chart.js not available, retrying...");
    setTimeout(initProgressChart, 500);
    return;
  }

  const moduleProgressCanvas = document.getElementById("moduleProgressChart");
  if (moduleProgressCanvas && typeof MODULE_PROGRESS_DATA !== "undefined") {
    const moduleNames = MODULE_PROGRESS_DATA.map((m) => m.name);
    const moduleProgress = MODULE_PROGRESS_DATA.map((m) => m.progress);

    new Chart(moduleProgressCanvas, {
      type: "bar",
      data: {
        labels: moduleNames,
        datasets: [
          {
            label: "Progress %",
            data: moduleProgress,
            backgroundColor: "#D4AF37",
            borderColor: "#B8860B",
            borderWidth: 2,
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: "y",
        scales: {
          x: {
            beginAtZero: true,
            max: 100,
            ticks: { font: { size: 12 }, color: "#666" },
            grid: { color: "#e0e0e0" },
          },
          y: {
            ticks: { font: { size: 12, weight: "600" }, color: "#333" },
          },
        },
        plugins: {
          legend: { display: false },
        },
      },
    });
  }

  const tasksProgressCanvas = document.getElementById("tasksProgressChart");
  if (
    tasksProgressCanvas &&
    typeof TASKS_PROGRESS_DATA !== "undefined" &&
    TASKS_PROGRESS_DATA.length > 0
  ) {
    const planNames = TASKS_PROGRESS_DATA.map((p) => p.name);
    const planProgress = TASKS_PROGRESS_DATA.map((p) => p.percentage);

    new Chart(tasksProgressCanvas, {
      type: "bar",
      data: {
        labels: planNames,
        datasets: [
          {
            label: "Completion %",
            data: planProgress,
            backgroundColor: "#6B4423",
            borderColor: "#8B5A2B",
            borderWidth: 2,
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: { font: { size: 12 }, color: "#666" },
            grid: { color: "#e0e0e0" },
          },
          x: {
            ticks: { font: { size: 12, weight: "600" }, color: "#333" },
          },
        },
        plugins: {
          legend: {
            display: true,
            labels: { font: { size: 13 }, color: "#333" },
          },
        },
      },
    });
  }
}

// Initialize chart when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initProgressChart);
} else {
  initProgressChart();
}
