// Counter animation function
function animateCounter(element, target) {
  let current = 0;
  const increment = target / 50;
  const timer = setInterval(() => {
    current += increment;
    if (current >= target) {
      element.textContent = target.toLocaleString();
      clearInterval(timer);
    } else {
      element.textContent = Math.floor(current).toLocaleString();
    }
  }, 30);
}

// Initialize counters when page loads
document.addEventListener("DOMContentLoaded", () => {
  const counters = document.querySelectorAll(".counter");
  counters.forEach((counter) => {
    const target = parseInt(counter.getAttribute("data-target"));
    animateCounter(counter, target);
  });

  // Update time
  updateTime();
  setInterval(updateTime, 60000);

  // Initialize sidebar
  initSidebar();
});

// Sidebar functionality
function initSidebar() {
  const sidebar = document.getElementById("sidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const mobileMenuBtn = document.getElementById("mobileMenuBtn");
  const mainContent = document.getElementById("mainContent");

  // Toggle sidebar on mobile
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("show");
    });
  }

  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener("click", () => {
      sidebar.classList.toggle("show");
    });
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", (e) => {
    if (window.innerWidth <= 1024) {
      if (
        !sidebar.contains(e.target) &&
        !sidebarToggle?.contains(e.target) &&
        !mobileMenuBtn?.contains(e.target)
      ) {
        sidebar.classList.remove("show");
      }
    }
  });

  // Handle sidebar menu items
  const sidebarItems = document.querySelectorAll(".sidebar-item");
  sidebarItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      // Remove active class from all items
      sidebarItems.forEach((i) => i.classList.remove("active"));
      // Add active class to clicked item
      item.classList.add("active");

      // Close sidebar on mobile after clicking
      if (window.innerWidth <= 1024) {
        sidebar.classList.remove("show");
      }
    });
  });
}

// Update last updated time
function updateTime() {
  const now = new Date();
  const hours = String(now.getHours()).padStart(2, "0");
  const minutes = String(now.getMinutes()).padStart(2, "0");
  document.getElementById("updateTime").textContent = `${hours}:${minutes}`;
}

// Refresh button functionality
document.getElementById("refreshBtn").addEventListener("click", function () {
  this.style.animation = "spin 1s linear";
  setTimeout(() => {
    location.reload();
  }, 500);
});

// Theme toggle (for future dark/light mode implementation)
document.getElementById("themeBtn").addEventListener("click", function () {
  console.log("Theme toggle clicked");
});

// Add spin animation
const style = document.createElement("style");
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Smooth scroll for navigation links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({ behavior: "smooth" });
    }
  });
});

// Add hover animation to stat cards
document.querySelectorAll(".stat-card").forEach((card) => {
  card.addEventListener("mouseenter", function () {
    this.style.transform = "translateY(-5px)";
  });
  card.addEventListener("mouseleave", function () {
    this.style.transform = "translateY(0)";
  });
});

// Responsive menu toggle (for future mobile menu implementation)
const checkViewportWidth = () => {
  const width = window.innerWidth;
  console.log(`Current viewport width: ${width}px`);
};

window.addEventListener("resize", checkViewportWidth);
checkViewportWidth();

// Add data refresh simulation
function simulateDataRefresh() {
  const counters = document.querySelectorAll(".counter");
  counters.forEach((counter) => {
    const originalTarget = parseInt(counter.getAttribute("data-target"));
    const newTarget = originalTarget + Math.floor(Math.random() * 100);
    animateCounter(counter, newTarget);
  });
}

// Auto-refresh data every 5 minutes
setInterval(simulateDataRefresh, 300000);
