(function () {
    $(document).ready(function () {
        $.ajax({
            url: "/ajax/profile",
            method: "GET",
            data: { followed_by: true, userid: userid },
            success: function (response) {
                let followedBy = "";
                if (response.length > 0) {
                    if (response.length <= 3) {
                        followedBy = response.map(user => `
                            <a href="${user.url}">
                            <img src="${user.pfp}" width="17px" height="17px" class="w3-circle" />
                            <i class="fa fa-at" aria-hidden="true"></i>${user.username}</a>`).join(", ");
                    } else {
                        let first = response.slice(0, 3);
                        let others = response.length - 3;
                        followedBy = first.map(user => `<a href="${user.url}">
                            <img src="${user.pfp}" width="17px" height="17px" class="w3-circle" />
                            <i class="fa fa-at" aria-hidden="true"></i>${user.username}</a>`).join(", ");
                        followedBy += ` and ${others} others you know`;
                    }
                } else {
                    followedBy = "nobody you know";
                }

                $("#followedby-wrapper").html(`Followed by ${followedBy}`);
            },
            error: function (xhr, text, err) {
                console.error(xhr.status + ' ' + err);
                if(xhr.status === 401) {
                    $("#followedby-wrapper").html('Login to view users that follow them');
                } else if(xhr.status === 404) {
                    $("#followedby-wrapper").html('This user\'s profile either doesn\'t exist, is private, or deactivated.');
                } else if(xhr.status === 500 || xhr.status === 502 || xhr.status === 503) {
                    $("#followedby-wrapper").html('Internal server error');
                } else {
                    $("#followedby-wrapper").html('An error has occured');
                }
            }
        });

        function UserContent() {
            this.cre = function(page) {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { user_content: true, cre: true, userid: userid, page: page },
                    dataType: "json",
                    success: function (response) {
                        let elm = $('#creationstab .contain')
                        if (response.success === true && response.creations) {
                            response.creations.forEach(function (r) {
                                let $clone = $($('#gr8-creation-template').html());

                                $clone.find(".creation-title").text(r.name);
                                $clone.find(".creation-link").attr("href", "/build/" + r.id);
                                $clone.find(".creation-thumbnail").attr("src", r.screenshot);

                                $clone.find(".meta-author a").append(r.username);
                                $clone.find(".meta-author a").attr("href", "/@" + r.username);
                                $clone.find(".meta-author span").text(r.date);

                                $clone.find(".meta-stats .views").append(`${r.views} views`);
                                $clone.find(".meta-stats .favs").append(`${r.likes} favorites`);
                                $clone.find(".meta-stats .comments").append(`${r.replies} comments`);

                                elm.append($clone);
                                window.mode();
                            });
                        } else if (response.creations === null) {
                            $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div>`).appendTo(elm);
                            $(".foward-button").hide();
                        } else if (response.error) {
                            $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div>`).appendTo(elm);
                            $(".foward-button").hide();
                        } else {
                            console.log(response);
                        }
                    },
                    error: function (xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            this.favs = function(page) {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { user_content: true, favs: true, userid: userid, page: page },
                    dataType: "json",
                    success: function (response) {
                        let elm = $('#likestab .contain')
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
                            $(".foward-button").hide();
                        } else if (response.error) {
                            $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div>`).appendTo(elm);
                            $(".foward-button").hide();
                        } else {
                            console.log(response);
                        }
                    },
                    error: function (xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            this.topics = function(page) {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { user_content: true, topics: true, userid: userid, page: page },
                    dataType: "json",
                    success: function (response) {
                        var elm = $('#poststab .contain')

                        if (response.success === true && response.posts) {
                            response.posts.forEach(function (r) {
                                let $clone = $($('#gr8-posts-template').html());

                                $clone.find(".text").text(r.title);
                                $clone.find(".user").text(r.username);
                                $clone.find(".user").attr("href", "/@" + r.username);
                                $clone.find(".time").text(r.date);
                                $clone.find(".link-name").attr("href", "/topic/" + r.id);

                                elm.append($clone);
                                window.mode();
                            });
                        } else if (response.posts === null) {
                            $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div><br />`).appendTo(elm);
                            $(".foward-button").hide();
                        } else if (response.error) {
                            $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div><br />`).appendTo(elm);
                            $(".foward-button").hide();
                        } else {
                            console.log(response);
                        }
                    },
                    error: function (xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            this.comments = function(page) {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    data: { user_content: true, comments: true, userid: userid, page: page },
                    dataType: "json",
                    success: function (response) {
                        var elm = $('#commentstab .contain')

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
                                window.mode();
                            });
                        } else if (response.comments === null) {
                            $(`<div class='message w3-padding w3-round w3-light-grey'>${response.error}</div><br />`).appendTo(elm);
                            $(".foward-button").hide();
                        } else if (response.error) {
                            $(`<div class='message w3-padding w3-round w3-red'>${response.error}</div><br />`).appendTo(elm);
                            $(".foward-button").hide();
                        } else {
                            console.log(response);
                        }
                    },
                    error: function (xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }
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
                    url: "/ajax/report.php",
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

        var uc = new UserContent();
        var tabPages = {
            'creationstab': { page: 1, fetch: uc.cre },
            'commentstab': { page: 1, fetch: uc.comments },
            'poststab': { page: 1, fetch: uc.topics },
            'likestab': { page: 1, fetch: uc.favs }
        }

        $(".foward-button").on("click", function () {
            var $tab = $(this).closest("#creationstab, #commentstab, #poststab, #likestab");
            if(!$tab) {
                return;
            }

            var tabId = $tab.attr('id') ?? null;
            if (!tabPages[tabId]) {
                return;
            }

            var tabPage = tabPages[tabId];

            tabPage.page += 1;
            tabPage.fetch(tabPage.page);
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
        var uc = new UserContent();
        uc.cre(1);
        uc.topics(1);
        uc.comments(1);
        uc.favs(1);
    });
})();