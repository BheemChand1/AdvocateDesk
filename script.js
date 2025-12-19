// Display counters instantly (no animation)
function displayCounter(element, target) {
  element.textContent = target.toLocaleString();
}

// Initialize counters when page loads
document.addEventListener("DOMContentLoaded", () => {
  const counters = document.querySelectorAll(".counter");
  counters.forEach((counter) => {
    const target = parseInt(counter.getAttribute("data-target"));
    displayCounter(counter, target);
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
      // Check if item has dropdown
      if (item.classList.contains("has-dropdown")) {
        e.preventDefault();
        const dropdown = item.closest(".sidebar-dropdown");
        dropdown.classList.toggle("active");
        return;
      }

      // Close sidebar on mobile after clicking
      if (window.innerWidth <= 1024) {
        sidebar.classList.remove("show");
      }
    });
  });

  // Handle submenu items
  const submenuItems = document.querySelectorAll(".submenu-item");
  submenuItems.forEach((item) => {
    item.addEventListener("click", (e) => {
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
  location.reload();
});

// Theme toggle (for future dark/light mode implementation)
document.getElementById("themeBtn").addEventListener("click", function () {
  console.log("Theme toggle clicked");
});

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

// Handle Create Client Form Submission
const createClientForm = document.getElementById("createClientForm");
if (createClientForm) {
  createClientForm.addEventListener("submit", function (e) {
    e.preventDefault();

    // Get form data
    const formData = new FormData(this);
    const clientData = Object.fromEntries(formData.entries());

    // Show success message
    alert(
      "Client created successfully!\n\nClient Details:\n" +
        JSON.stringify(clientData, null, 2)
    );

    // Reset form
    this.reset();

    // Optional: Redirect to view clients page
    // window.location.href = 'view-clients.html';
  });
}

// Stat cards have no animations for better performance

// Responsive menu toggle (for future mobile menu implementation)
const checkViewportWidth = () => {
  const width = window.innerWidth;
  console.log(`Current viewport width: ${width}px`);
};

window.addEventListener("resize", checkViewportWidth);
checkViewportWidth();

// Data refresh disabled for better performance
