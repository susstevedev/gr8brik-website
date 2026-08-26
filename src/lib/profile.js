(function () {
    $(document).ready(function () {
        $.ajax({
            url: "/ajax/profile",
            method: "GET",
            data: { followed_by: userid },
            success: function (response) {
                let followedBy = "";
                if (response.length > 0) {
                    if (response.length <= 3) {
                        followedBy = response.map(user => `
                            <a href="${user.url}">
                            <img src="${user.pfp}" width="15px" height="15px" />
                            ${user.username}</a>`).join(", ");
                    } else {
                        let first = response.slice(0, 3);
                        let others = response.length - 3;
                        followedBy = first.map(user => `<a href="${user.url}">
                            <img src="${user.pfp}" width="17px" height="17px" class="w3-circle" />
                            ${user.username}</a>`).join(", ");
                        followedBy += ` and ${others} others you know`;
                    }
                } else {
                    followedBy = "nobody you know";
                }
                $("#followedby-wrapper").html(`Followed by ${followedBy}`);
                $("#followedby-wrapper").css({
                    "display": "inline",
                    "font-size": "15px",
                    "text-shadow": "0px 0px 0px #fff"
                })
            },
            error: function (jqXHR, textStatus, errorThrown) {
                var response = JSON.parse(jqXHR.responseText);
                console.error('Server status code: ' + textStatus + ' ' + jqXHR.status + ' ' + errorThrown);
            }
        });

        window.params = new URL(window.location.href);
        window.pages = {};

        window.getUserBuilds = function (page) {
            $.ajax({
                url: "/ajax/profile",
                method: "GET",
                data: { getUserBuilds: true, userid: userid, page: page },
                dataType: "json",
                success: function (response) {
                    window.pages.c = page;

                    let elm = $('#creationstab div')
                    elm.children().not('#gr8-creation-template').remove();

                    if (response.success === true && response.creations) {
                        response.creations.forEach(function (r) {
                            let $clone = $($('#gr8-creation-template').html());

                            $clone.find(".creation-title").text(r.name);
                            $clone.find(".creation-link").attr("href", "/build/" + r.id);
                            $clone.find(".meta-author a").text(r.username);
                            $clone.find(".meta-author a").attr("href", "/@" + r.username);
                            $clone.find(".meta-author span").text(r.date);
                            $clone.find(".creation-thumbnail").attr("src", r.screenshot);

                            elm.append($clone);
                            window.mode();
                        });
                    } else if (response.creations === null) {
                        $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div>`).appendTo(elm);
                    } else if (response.error) {
                        console.error(response.error);
                        $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div>`).appendTo(elm);
                    } else {
                        console.log(response);
                    }
                },
                error: function (xhr, stat, err) {
                    console.error(stat, xhr.status, err);
                }
            });
        }

        window.getUserLiked = function () {
            $.ajax({
                url: "/ajax/profile",
                method: "GET",
                data: { getUserLiked: true, userid: userid },
                dataType: "json",
                success: function (response) {
                    let elm = $('#likestab div')
                    elm.children().not('#gr8-likes-template').remove();

                    if (response.success === true && response.creations) {
                        response.creations.forEach(function (r) {
                            let $clone = $($('#gr8-likes-template').html());

                            $clone.find(".creation-title").text(r.name);
                            $clone.find(".creation-link").attr("href", "/build/" + r.id);
                            $clone.find(".meta-author a").text(r.username);
                            $clone.find(".meta-author a").attr("href", "/@" + r.username);
                            $clone.find(".meta-author span").text(r.date);
                            $clone.find(".creation-thumbnail").attr("src", r.screenshot);

                            elm.append($clone);
                            window.mode();
                        });
                    } else if (response.creations === null) {
                        $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div>`).appendTo(elm);
                    } else if (response.error) {
                        console.error(response.error);
                        $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div>`).appendTo(elm);
                    } else {
                        console.log(response);
                    }
                },
                error: function (xhr, stat, err) {
                    console.error(stat, xhr.status, err);
                }
            });
        }

        window.getUserForums = function (page) {
            $.ajax({
                url: "/ajax/profile",
                method: "GET",
                data: { getUserForums: true, userid: userid, page: page },
                dataType: "json",
                success: function (response) {
                    window.pages.f = page;

                    var elm = $('#poststab .w3-row')
                    elm.children().not('#gr8-posts-template').remove();

                    if (response.success === true && response.posts) {
                        response.posts.forEach(function (r) {
                            let $clone = $($('#gr8-posts-template').html());

                            $clone.find(".text").text(r.title);
                            $clone.find(".user").text(r.username);
                            $clone.find(".user").attr("href", "/@" + r.username);
                            $clone.find(".time").text(r.date);
                            $clone.find(".link-name").attr("href", "/topic/" + r.id);

                            elm.append($clone);
                        });
                    } else if (response.posts === null) {
                        $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div><br />`).appendTo(elm);
                    } else if (response.error) {
                        console.error(response.error);
                        $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div><br />`).appendTo(elm);
                    } else {
                        console.log(response);
                    }
                },
                error: function (xhr, stat, err) {
                    console.error(stat, xhr.status, err);
                }
            });
        }

        window.getUserComments = function (page) {
            $.ajax({
                url: "/ajax/profile",
                method: "GET",
                data: { getUserComments: true, userid: userid, page: page },
                dataType: "json",
                success: function (response) {
                    window.pages.r = page;

                    var elm = $('#commentstab .w3-row')
                    elm.children().not('#gr8-comment-template').remove();

                    if (response.success === true && response.comments) {
                        response.comments.forEach(function (r) {
                            let $clone = $($('#gr8-comment-template').html());

                            $clone.find(".text").text(r.content);
                            $clone.find(".title").text(r.parent_name);
                            $clone.find(".user").text(r.username);
                            $clone.find(".user").attr("href", "/@" + r.username);
                            $clone.find(".time").text(r.date);

                            if (r.type === 'forum') {
                                $clone.find(".link-name").attr("href", "/topic/" + r.parent);
                                $clone.find(".title").attr("href", "/topic/" + r.parent);
                            } else if (r.type === 'model') {
                                $clone.find(".link-name").attr("href", "/build/" + r.parent);
                                $clone.find(".title").attr("href", "/build/" + r.parent);
                            }

                            elm.append($clone);
                        });
                    } else if (response.comments === null) {
                        $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div><br />`).appendTo(elm);
                    } else if (response.error) {
                        console.error(response.error);
                        $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div><br />`).appendTo(elm);
                    } else {
                        console.log(response);
                    }
                },
                error: function (xhr, stat, err) {
                    console.error(stat, xhr.status, err);
                }
            });
        }


        $("#reportForm").submit(function (e) {
            e.preventDefault();

            $.get("/ajax/config.php", {
                get_csrf_token: true
            }, function (d) {
                let csrf_token = d.csrf_token;

                let payload = {
                    report_type: 'profile',
                    csrf_token: csrf_token,
                    reportv2: true,
                    reportable_id: userid,
                    other: $("#reportForm #otherReason").val(),
                    reason: $("#reportForm [name='reason']:checked").val(),
                }

                $.ajax({
                    url: "/creation.php?id=null",//placeholder, will move to seperate api page probably
                    type: "POST",
                    data: payload,
                    dataType: "json",
                    success: function (response) {
                        $("#modal-report").hide();

                        if (response.success) {
                            alert(response.success);
                            $("#reportForm")[0].reset();
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function () {
                        $("#modal-report").hide();
                        alert("An error occurred. Please try again later.");
                    }
                });
            }, "json").fail(function (xhr, text, err) {
                alert(text);
            });
        });

        window.pages.c = 1;
        window.pages.f = 1;
        window.pages.r = 1;

        var tabPages = {
            '#creationstab': { page: parseInt(window.pages.c), fetch: getUserBuilds },
            '#commentstab': { page: parseInt(window.pages.r), fetch: getUserComments },
            '#poststab': { page: parseInt(window.pages.f), fetch: getUserForums }
        };

        $(".foward-button, .back-button").on("click", function () {
            var $tab = $(this).closest("#creationstab, #commentstab, #poststab");
            var tabId = `#${$tab.attr('id')}`;
            var tabConfig = tabPages[tabId];

            if (!tabConfig) {
                return;
            }

            tabConfig.page += $(this).hasClass("foward-button") ? 1 : -1;
            tabConfig.fetch(tabConfig.page);
        });

        window.openTab = function (tab) {
            var tabGroup = $('.tab');

            tabGroup.each(function () {
                $(this).hide();
            });

            var tabElement = $(`#${tab}`);
            tabElement.show();
            $('html, body').animate({ scrollTop: 0 }, 'fast');
        }

        openTab('creationstab');
        getUserBuilds(1);
        getUserForums(1);
        getUserComments(1);
        getUserLiked();
    });
})();