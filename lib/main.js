if ("serviceWorker" in navigator) {
    window.addEventListener("load", function() {
        navigator.serviceWorker.register("/lib/serviceWorker.js")
        .then(reg => {
            console.log("Service worker registered");

            reg.onupdatefound = () => {
                const new_worker = reg.installing;
                if (new_worker) {
                    new_worker.onstatechange = () => {
                        if (new_worker.state === "installed" && navigator.serviceWorker.controller) {
                            alert("Gr8brik has been updated. Please reopen your app to finish the update.");
                        }
                    };
                }
            };
        })
        .catch(err => console.error("Service worker not registered: ", err));
    });
}

function openTab(tab, btn) {
    var amount;
    var tabGroup = document.getElementsByClassName("tab");
    var btns = document.getElementsByClassName("w3-bar-item");

    for (amount = 0; amount < tabGroup.length; amount++) {
        tabGroup[amount].classList.add("w3-hide");
    }

    if(btn) {
        for (amount = 0; amount < btns.length; amount++) {
            btns[amount].classList.remove("w3-border-blue");
        }
        btn.classList.add("w3-border-blue");
    }
        
    var tabElement = document.getElementById(tab);
    tabElement.classList.remove("w3-hide");
}

document.addEventListener("DOMContentLoaded", function() {
    $(document).ready(function() {
        const cookie_options = {
            link: "/privacy",
            delay: "500",
            fixedCookieTypeDesc: "Cookie Preferences & Session Data saved to your device and not on our servers.",
            cookieTypes: [
                {
                    type: 'Site Preferences',
                    value: 'site-preferences',
                    description: 'These are cookies that are related to your site preferences, e.g. remembering your username, site colours, etc.'
                },
                {
                    type: 'Login Data',
                    value: 'login-data',
                    description: 'Cookies that decide whether or not we can collect data about user logins.'
                }
            ],
        }
        $('body').ihavecookies(cookie_options);

        const title_data = $('title').text();

        function dropdown() {
            const drop = $("#gr8-dropdown");
            if (!drop.hasClass("w3-show")) {
                drop.addClass("w3-show");
            } else {
                drop.removeClass("w3-show");
            }
        }
        window.dropdown = dropdown;
                
        function checkCookieExists(n) {
            if (document.cookie.split(';').some((item) => item.trim().startsWith(n + '='))) {
                return true;
            }
            return false;
        }

        window.mode = function() {
            let theme = 'light';
            const auto = window.matchMedia("(prefers-color-scheme: dark)").matches;

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

        function loadFeaturedCreations() {
            let featured = "";
            $.ajax({
                url: '/list.php',
                type: 'GET',
                data: { featured: true, unique: Date.now() },
                success: function(response) {
                    featured = response.map(builds => `
                        <div class='w3-padding-0 w3-card-2 w3-hover-shadow w3-border w3-border-white'>
                            <span style='display: flex; padding-left: 20px; padding-right: 20px;'><a href='/build/${builds.model_id}'>${builds.title}</a></span>
                            <span style='display: flex; padding-left: 20px; padding-right: 20px;'>${builds.views} views</span>
                            <span style='display: flex; padding-left: 20px; padding-right: 20px;'>By <a href='/user/${builds.user}'>${builds.username}</a></span>
                        </div><br />
                    `);
                    $(".featured-builds").fadeIn(1000).append(featured);
                }
            });
        }
        window.loadFeaturedCreations = loadFeaturedCreations();

        $("#toggleDark, .toggleDark").click(function(event) {
            $.cookie('mode', 'dark', { expires: 365 });
            mode();
        });

        $("#toggleLight, .toggleLight").click(function(event) {
            $.cookie('mode', 'light', { expires: 365 });
            mode();
        });

        $("#toggleAuto, .toggleAuto").click(function(event) {
            $.removeCookie('mode');
            mode();
        });

        //setInterval(function () {
            document.title = "";
            document.title = title_data + " - Gr8brik";
            mode();
            twemoji.parse(document.body);
        //}, 1000/60);
    });
});