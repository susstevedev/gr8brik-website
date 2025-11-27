if ("serviceWorker" in navigator) {
  window.addEventListener("load", function () {
    navigator.serviceWorker
      .register("/lib/serviceWorker.js?v=3")
      .then((reg) => {
        console.log("sw registered");

        reg.onupdatefound = () => {
          const new_worker = reg.installing;
          if (new_worker) {
            new_worker.onstatechange = () => {
              if (
                new_worker.state === "installed" &&
                navigator.serviceWorker.controller
              ) {
                console.log("sw updated");
              }
            };
          }
        };
      })
      .catch((err) => console.error("sw not registered " + err));
  });
}

$(document).ready(function () {
  var cookie_options = {
    link: "/info/privacy.php",
    delay: "250",
    fixedCookieTypeDesc:
      "Cookie Preferences & Session Data saved to your device and our servers.",
    cookieTypes: [
      {
        type: "Site Preferences",
        value: "site-prefs",
        description:
          "These are cookies that are related to your site preferences, e.g. remembering your username, site colours, etc.",
      },
      {
        type: "Session Data",
        value: "login-data",
        description:
          "Cookies that decide whether or not we can collect data about user sessions.",
      },
    ],
  };
  $("body").ihavecookies(cookie_options);

  window.dropdown = function () {
    var dropdown = $("#gr8-dropdown");
    if (!dropdown.hasClass("w3-show")) {
      dropdown.addClass("w3-show");
    } else {
      dropdown.removeClass("w3-show");
    }
  };

  window.openTab = function (tab, btn) {
    $(".tab").addClass("w3-hide");

    if (btn) {
      $(".w3-bar-item").removeClass("w3-border-blue");
      $(btn).addClass("w3-border-blue");
    }

    $("#" + tab).removeClass("w3-hide");
  };

  window.mode = function () {
    var theme = "light";
    var auto = window.matchMedia("(prefers-color-scheme: dark)").matches;

    if ($.cookie("mode") === "dark") {
      theme = "dark";
    } else if ($.cookie("mode") === "light") {
      theme = "light";
    } else if (auto) {
      theme = "dark";
    }

    if (theme === "light") {
      $("body")
        .addClass("w3-light-blue")
        .css({ "background-color": "", color: "" });
      $("#navbar").removeClass("w3-dark-grey").addClass("w3-light-grey");
      $(".gr8-theme")
        .addClass("w3-light-grey w3-text-black")
        .removeClass("w3-dark-grey w3-text-white");
      $(".gr8-theme-opposite")
        .addClass("w3-dark-grey w3-text-white")
        .removeClass("w3-light-grey w3-text-black");
    }

    if (theme === "dark") {
      $("body")
        .removeClass("w3-light-blue")
        .css({ "background-color": "#121212", color: "#FAF9F6" });
      $("#navbar").removeClass("w3-light-grey").addClass("w3-dark-grey");
      $(".gr8-theme")
        .removeClass("w3-light-grey w3-text-grey w3-text-black")
        .addClass("w3-dark-grey w3-text-white");
      $(".gr8-theme-opposite")
        .removeClass("w3-dark-grey w3-text-white gr8-theme")
        .addClass("w3-light-grey w3-text-black");
    }
  };

  window.loadFeaturedCreations = function () {
    var featured;
    $.ajax({
      url: "/list.php",
      type: "GET",
      // use featured instead of feature_v2 for legacy api
      data: { feature_v2: true },
      success: function (res) {
        let parsed_res = res["builds"];
        console.log(parsed_res);
        featured = parsed_res.map(function (builds) {
          return `
                    <div class='gr8-theme w3-card-2 w3-padding'>
                        <span>
                        	<a href='/build/${builds.model_id}'>${builds.title}</a>
                        </span><br />
                        <span>
                        	${builds.views} views - ${builds.likes} likes - 
                            <a href='/user/${builds.user}'>
                            	<img src="/acc/users/pfps/${builds.user}.jpg?v=a" class="w3-circle" width="25px" height="25px">
                                ${builds.username}
                            </a>
                        </span>
                    </div><br />
                `;
        });
        $(".gr8-build-count")
          .addClass("w3-blue w3-tag w3-round")
          .append(res["build_count"]);
        $(".featured-builds").append(featured).fadeIn();
      },
    });
  };

  window.getWarnStatus = function () {
    let popup;
    $.ajax({
      url: "/ajax/user.php",
      type: "GET",
      data: { get_warn_status: true },
      success: function (res) {
        console.log(res);
        if (res.status == "yes") {
          console.log("status is true");
          popup = `<div id="popup" class="w3-modal w3-show">
                                        <div class="gr8-theme w3-modal-content w3-light-grey w3-card-2 w3-animate-top w3-center w3-padding-small">
                                            <header class="w3-container">
                                                <span onclick="document.getElementById('popup').classList.remove('w3-show'); setTimeout(getWarnStatus, 60000); seenWarnStatus();" 
                                                class="w3-button w3-large w3-red w3-hover-white w3-display-topright">&times;</span>
                                            </header>
                                            <div class="w3-container">
                                                <p id="popup-text">${res.text}</p>
                                                <p id="popup-reason">${res.reason}</p>
                                                <button onclick="document.getElementById('popup').classList.remove('w3-show'); setTimeout(getWarnStatus, 60000); seenWarnStatus();" 
                                                class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo">Got it</button>
                                            </div>
                                        </div>
                                    </div>`;
          $("#popup-wrapper-global").append(popup);
          mode();
          console.log(popup);
        } else {
          setTimeout(getWarnStatus, 60000);
        }
      },
    });
  };

  window.seenWarnStatus = function () {
    $.ajax({
      url: "/ajax/user.php",
      type: "GET",
      data: { seen_warn_status: true },
      success: function (res) {
        console.log(res);
      },
    });
  };

  window.load_search = function () {
    let search_term = $("#search-input").val();
    if (search_term.length > 0) {
      $.ajax({
        url: "/list",
        type: "GET",
        data: { q: search_term },
        success: function (r) {
          window.location.href =
            "/list?q=" + encodeURIComponent(search_term).replace(/%20/g, "+");
        },
      });
    } else {
      alert("Search must contain at least one character.");
    }
  };

  $("#toggleDark, .toggleDark").click(function (event) {
    $.cookie("mode", "dark", { expires: 365 });
    window.location.reload();
  });

  $("#toggleLight, .toggleLight").click(function (event) {
    $.cookie("mode", "light", { expires: 365 });
    window.location.reload();
  });

  $("#toggleAuto, .toggleAuto").click(function (event) {
    $.removeCookie("mode");
    window.location.reload();
  });

  mode();
  getWarnStatus();
  twemoji.size = "72x72";
  twemoji.parse(document.body, {
    base: "https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/",
  });
});
