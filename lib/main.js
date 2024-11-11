if ("serviceWorker" in navigator) {
  window.addEventListener("load", function() {
    navigator.serviceWorker
      .register("/serviceWorker.js")
      .then(res => console.log("service worker registered"))
      .catch(err => console.log("service worker not registered", err))
  })
}

document.addEventListener("DOMContentLoaded", function() {

    $(document).ready(function() {

        function mode() {
            if (localStorage.getItem('mode')) {
                $("body").addClass("w3-dark-grey");
                $('#navbar').removeClass('w3-light-grey').addClass('w3-black'); 
            } else {
                $("body").removeClass("w3-dark-grey");
                $('#navbar').removeClass('w3-black').addClass('w3-light-grey'); 
            }
        }
            
        const aYearFromNow = new Date(Date.now() + 31536000000);

        $("#toggleDark").click(function(event) {         
            localStorage.setItem("mode", "night");
            mode();
		});

		$("#toggleLight").click(function(event) {     
            localStorage.removeItem("mode");
            mode();
		});

        function load(url) {
            document.title = "";
            $("body").load(url, function(event) {
                $(".loading").fadeIn(10).animate({ width: '100%' }).fadeOut(10).animate({ width: '0%' });
                mode();
                window.scrollTo(0, 0);
                history.pushState(null, "", url);
                document.title = 'GR8BRIK | ' + $('title').text();
            });
        }

        $(document).on('click', 'a', function(event) {
            var url = $(this).attr("href");
            if (!url || url.match("^http")) {
                return;
            }
            event.preventDefault();
            load(url);
        });


		window.addEventListener("popstate", function() {
            load(location.pathname + location.search);
		});

        function dropdown() {
            var $x = $("#gr8-dropdown");
            if (!$x.hasClass("w3-show")) {
                $x.addClass("w3-show");
            } else {
                $x.removeClass("w3-show");
            }
        }

        window.dropdown = dropdown;
        document.title = 'GR8BRIK | ' + $('title').text();
        mode();
    
    });
        
});