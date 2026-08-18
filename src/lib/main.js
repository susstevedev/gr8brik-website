if ("serviceWorker" in navigator) {
    window.addEventListener("load", function() {
        navigator.serviceWorker.register("/lib/serviceWorker.js?v=42826")
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
        link: "/privacy",
        delay: 150,
        fixedCookieTypeDesc: "Essential cookies enable core functionality like security and network management. Your preferences (like light/dark mode) are stored directly on your device.",
        cookieTypes: [
            {
                type: 'Preferences',
                value: 'site-prefs',
                description: 'Remembers your interface settings, themes, and display options.'
            },
            {
                type: 'Analytics & Performance',
                value: 'analytics', 
                description: 'Helps us understand how visitors interact with the site so we can improve it.'
            }
        ],
    }
    $('body').ihavecookies(cookie_options);

    window.dropdown = function(id) {
        let dropdown = $("#" + id);
        dropdown.slideToggle(250);
    }

    window.openTab = function(tab, btn) {
        $(".tab").addClass("w3-hide");

        if (btn) {
            $(".w3-bar-item").removeClass("w3-border-blue");
            $(btn).addClass("w3-border-blue");
        }

        $("#" + tab).removeClass("w3-hide");
    }
    
    window.escapeHtml = function(text) {
        return text.innerText;
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
            $("body").addClass("w3-light-blue mode-light").removeClass("mode-dark").css({"background-color": "", "color": ""});
            $('#navbar').removeClass('w3-dark-grey').addClass('w3-light-grey');
            $('.gr8-theme').addClass('w3-light-grey w3-text-black').removeClass('w3-dark-grey w3-text-white');
            $('.gr8-theme-opposite').addClass('w3-dark-grey w3-text-white').removeClass('w3-light-grey w3-text-black');
        }

        if(theme === 'dark') {
            $("body").removeClass("w3-light-blue").removeClass("mode-light").addClass("mode-dark").css({"background-color": "#121212", "color": "#FAF9F6"});
            $('#navbar').removeClass('w3-light-grey').addClass('w3-dark-grey');
            $('.gr8-theme').removeClass('w3-light-grey w3-text-grey w3-text-black').addClass('w3-dark-grey w3-text-white');
            $('.gr8-theme-opposite').removeClass('w3-dark-grey w3-text-white gr8-theme').addClass('w3-light-grey w3-text-black');
        }
    }

    window.loadFeaturedCreations = function() {
        var featured;
        $.ajax({
            url: '/list.php',
            type: 'GET',
            data: { feature_v3: true },
            dataType: 'json',
            success: function(res) {
                let creations = [];
                res.creations.forEach(function(creation) {
                    let cre = `
                    <div class='gr8-theme w3-card-2 w3-padding'>
                        <b>
                        	<a href='/build/${creation.model_id}'>${creation.title}</a>
                        </b><br />
                        <span>
                        	<i class="fa fa-eye"></i>${creation.views} • <i class="fa fa-star"></i>${creation.likes} • 
                            <a href='/@${encodeURIComponent(creation.username)}'><i class="fa fa-at"></i>${creation.username}</a>
                        </span>
                    </div><br />`;
                    creations.push(cre);
                });
                $(".gr8-build-count").addClass('w3-blue w3-tag w3-round').append(res['build_count']);
                $(".featured-builds").append(creations).fadeIn();
            }
        });
    }
    
    window.getWarnStatus = function() {
        let popup;
        $.ajax({
            url: '/ajax/user.php',
            type: 'GET',
            data: { get_warn_status: true },
            success: function(res) {
                if(res.status == "yes" && res.success == true) {
                    popup = `<div id="popup" class="w3-modal w3-show">
                                <div class="gr8-theme w3-modal-content w3-light-grey w3-card-2 w3-center w3-animate-bottom">
                                    <header class="w3-container">
                                        <h4 id="popup-text">${res.text}</h4>
                                        <span onclick="document.getElementById('popup').classList.remove('w3-show'); setTimeout(getWarnStatus, 120000); seenWarnStatus();" 
                                        class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                                    </header>
                                    <div class="w3-container">
                                        <p id="popup-reason">${res.reason}</p>
                                        <p id="popup-reason-additional">${res.additional}</p>
                                        <button onclick="document.getElementById('popup').classList.remove('w3-show'); setTimeout(getWarnStatus, 120000); seenWarnStatus();" 
                                        class="w3-btn w3-blue w3-hover-opacity w3-round w3-border w3-border-indigo">${res.button}</button><br /><br />
                                    </div>
                                </div>
                            </div>`
                    $("#popup-wrapper-global").append(popup);
                   	mode();
                } else if(res.success == false) {
                    throw new Error(res.error);
                } else {
                	setTimeout(getWarnStatus, 120000);
            	}
            }
        });
    }
    
    window.seenWarnStatus = function() {
        $.ajax({
            url: '/ajax/user.php',
            type: 'GET',
            data: { seen_warn_status: true },
            success: function(res) {
                console.log(res);
            }
        });
    }

    window.load_search = function() {
            let search_term = $('#search-input').val();
            if (search_term.length > 0) {
                $.ajax({
                    url: '/list',
                    type: 'GET',
                    data: { q: search_term },
                    success: function(r) {
                        window.location.href = "/list?q=" + encodeURIComponent(search_term).replace(/%20/g, "+");
                    }
                });
            } else {
                alert('Search must contain at least one character.');
            }
    }

    //browser-update.org script
    //make sure to update every couple of months!!! please!!! REMEMBER!!!
    var $buoop = {required:{e:-4,f:-3,o:-3,s:-1,c:-3},insecure:true,style:"corner",api:2026.3 }; 
    function $buo_f() { 
        var e = document.createElement("script"); 
        e.src = "//browser-update.org/update.js";
        document.body.appendChild(e);
    };
    try{
        document.addEventListener("DOMContentLoaded",$buo_f,false);
    }catch(e){
        window.attachEvent("onload", $buo_f)
    };

    $("#hideLoginAd").click(function(event) {
        $.cookie('hide_login_ad', true, { expires: 365, path: '/' });
        $("#loginAd").hide();
    });

    $("#toggleDark, .toggleDark").click(function(event) {
        $.cookie('mode', 'dark', { expires: 365, path: '/' });
        mode();
    });

    $("#toggleLight, .toggleLight").click(function(event) {
        $.cookie('mode', 'light', { expires: 365, path: '/' });
        mode();
    });

    $("#toggleAuto, .toggleAuto").click(function(event) {
        $.removeCookie('mode', { path: '/' });
        mode();
    });

    $(['/img/loading.gif', '/img/no_image.png']).preload();
    mode();
    getWarnStatus();
    twemoji.size = '72x72';
    twemoji.parse(document.getElementsByClassName('gr8-main')[0], {base: 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/'})
});