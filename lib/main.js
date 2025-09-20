if ("serviceWorker" in navigator) {
    window.addEventListener("load", function() {
        navigator.serviceWorker.register("/lib/serviceWorker.js?v=3")
        .then(reg => {
            console.log("sw registered");

            reg.onupdatefound = () => {
                const new_worker = reg.installing;
                if (new_worker) {
                    new_worker.onstatechange = () => {
                        if (new_worker.state === "installed" && navigator.serviceWorker.controller) {
                            console.log('sw updated');
                        }
                    };
                }
            };
        })
        .catch(err => console.error("sw not registered " + err));
    });
}

$(document).ready(function() {
    var cookie_options = {
        link: "/privacy.php",
        delay: "250",
        fixedCookieTypeDesc: "Cookie Preferences & Session Data saved to your device and our servers.",
        cookieTypes: [
            {
                type: 'Site Preferences',
                value: 'site-prefs',
                description: 'These are cookies that are related to your site preferences, e.g. remembering your username, site colours, etc.'
            },
            {
                type: 'Session Data',
                value: 'login-data',
                description: 'Cookies that decide whether or not we can collect data about user sessions.'
            }
        ],
    }
    $('body').ihavecookies(cookie_options);

    window.dropdown = function() {
        var dropdown = $("#gr8-dropdown");
        if (!dropdown.hasClass("w3-show")) {
            dropdown.addClass("w3-show");
        } else {
            dropdown.removeClass("w3-show");
        }
    }

    window.openTab = function(tab, btn) {
        $(".tab").addClass("w3-hide");

        if (btn) {
            $(".w3-bar-item").removeClass("w3-border-blue");
            $(btn).addClass("w3-border-blue");
        }

        $("#" + tab).removeClass("w3-hide");
    }

    window.mode = function() {
        var theme = 'light';
        var auto = window.matchMedia("(prefers-color-scheme: dark)").matches;

        if($.cookie('mode') === 'dark') {
            theme = 'dark';
        } else if($.cookie('mode') === 'light') {
            theme = 'light';
        } else if(auto) {
            theme = 'dark';
        }

        if(theme === 'light') {
            $("body").addClass("w3-light-blue").css({"background-color": "", "color": ""});;
            $('#navbar').removeClass('w3-dark-grey').addClass('w3-light-grey'); 
            $('.gr8-theme').addClass('w3-light-grey w3-text-black').removeClass('w3-dark-grey w3-text-white'); 
            $('.gr8-theme-opposite').addClass('w3-dark-grey w3-text-white').removeClass('w3-light-grey w3-text-black'); 
        }

        if(theme === 'dark') {
            $("body").removeClass("w3-light-blue").css({"background-color": "#121212", "color": "#FAF9F6"});
            $('#navbar').removeClass('w3-light-grey').addClass('w3-dark-grey'); 
            $('.gr8-theme').removeClass('w3-light-grey w3-text-grey w3-text-black').addClass('w3-dark-grey w3-text-white'); 
            $('.gr8-theme-opposite').removeClass('w3-dark-grey w3-text-white gr8-theme').addClass('w3-light-grey w3-text-black');
        }
    }

    window.loadFeaturedCreations = function() {
        var featured = "";
        $.ajax({
            url: '/list.php',
            type: 'GET',
            data: { featured: true, unique: Date.now() },
            success: function(response) {
                featured = response.map(builds => `
                    <div class='gr8-theme w3-card-2 w3-hover-shadow w3-padding'>
                        <p><a href='/creation.php?id=${builds.model_id}'>${builds.title}</a></p>
                        <p>${builds.views} views - ${builds.likes} likes - <a href='/profile?id=${builds.user}'>${builds.username}</a></p>
                    </div><br />
                `);
                $(".featured-builds").append(featured).fadeIn();
            }
        });
    }

    $("#toggleDark, .toggleDark").click(function(event) {
        $.cookie('mode', 'dark', { expires: 365 });
        window.location.reload();
    });

    $("#toggleLight, .toggleLight").click(function(event) {
        $.cookie('mode', 'light', { expires: 365 });
        window.location.reload();
    });

    $("#toggleAuto, .toggleAuto").click(function(event) {
        $.removeCookie('mode');
        window.location.reload();
    });

    mode();
    twemoji.size = '72x72';
    twemoji.parse(document.body, {base: 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/'})
});