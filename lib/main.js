document.addEventListener("DOMContentLoaded", function() {
        
    $(document).ready(function() {
		// Function to update margin based on window width
        function updateMargin() {
            const windowWidth = $(window).width();
            const marginPercentage = 20; // Adjust as needed
            const marginLeft = (windowWidth * marginPercentage) / 100;

		    $(".w3-main").css("margin-left", marginLeft);
		}

		// Initial call to set margin
		updateMargin();

		// Function to load content using AJAX
		function loadContent(url) {
            $(".w3-main").hide(function() {
                $(".w3-main").load(url, function() {
                    $(".w3-main").show();
                    // Update the URL without reloading the page
					history.pushState(null, "", url);
                    $("#navbar").hide();
                    $("#linkbar").hide();
                    $(".gr8-warning").hide();
                    $("#cookie-message").hide();
					const pageName = url.split('/').pop() || 'home';
					document.title = pageName.charAt(0).toUpperCase() + pageName.slice(1) + " / GR8BRIK";
				});
			});
		}

			// Attach click event handler to navigation links
			$(document).on('click', 'a', function(e) {
                e.preventDefault(); // Prevent page refresh
                var url = $(this).attr("href"); // Get the link URL
                if (!url || url.match("^http")) {
                    url = "error.php?error=404";
                }
                loadContent(url);
            });

			// Handle browser navigation (back/forward buttons)
			window.addEventListener("popstate", function() {
                loadContent(location.pathname);
			});

			// Update margin on window resize
			$(window).resize(updateMargin);

			// Function to toggle dark mode (global scope)
			function toggleDarkMode() {
				$("body").addClass("w3-dark-grey");
				localStorage.setItem("theme", "dark");
			}

			// Function to toggle light mode (global scope)
			function toggleLightMode() {
				$("body").addClass("w3-light-blue");
				localStorage.setItem("theme", "light");
			}

			// Check theme on page load
			var theme = localStorage.getItem("theme");

			if (theme === "dark") {
				toggleDarkMode();
			} else if (theme === "light") {
				toggleLightMode();
			}

			$("#toggleDark").click(function() {
				toggleDarkMode();
				location.reload();
			});

			$("#toggleLight").click(function() {
				toggleLightMode();
				location.reload();
			});

			$("#searchBtn").hide();

			$("#searchBox").hide();

			$("#search").click(function() {
				$("#searchBtn").show();
				$("#searchBox").show();
				$("#search").hide();
				$("#overlay").fadeIn();
			});

			$(document).click(function(event) {

				if (!$(event.target).closest('#search, #searchBox, #searchBtn').length) {
					$("#searchBtn").hide();
					$("#searchBox").hide();
					$("#search").show();
					$("#overlay").fadeOut();

				}
			});
		});
        
});