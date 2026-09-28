$(document).ready(function () {
    function load_message_group() {
        $.ajax({
            url: 'messages.php',
            type: 'GET',
            data: {
                group: true
            },
            success: function (res) {
                var elm = $('#groups');
                elm.children().not('template').not('#group-form').remove();

                if (res && res.length > 0) {
                    res.forEach(function(r) {
                        var $clone = $($('#message-group').html());
                        console.log($clone);

                        $clone.find(".user").text(r.title);
                        $clone.find(".user-joined").text(r.joined);

                        if(r.pictureurl) {
                            $clone.find(".user-image").attr("src", r.pictureurl);
                        } else {
                            $clone.find(".user-image").hide();
                        }

                        $clone.on('click', function() {
                            load_message(r.id);
                        });

                        elm.append($clone);
                    });
                }

                window.mode();
                elm.fadeIn('fast');
            },
            error: function(xhr, text, error) {
                console.error(xhr.status, error);
                ui_error(error);
            }
        });
    }
    load_message_group();

    window.load_message = function (id) {
        $.ajax({
            url: 'messages.php',
            type: 'GET',
            dataType: 'json',
            data: {
                message: id
            },
            success: function (res) {
                if(res.success) {
                    var elm = $("#messages");
                    elm.hide();
                    elm.children().not('template').remove();

                    var url = new URL(window.location.href);
                    url.searchParams.set("m", id);
                    window.history.pushState(null, '', url);

                    var $form = $($('#message-group-form').html());
                    $form.find("#group-name").text(res.name);
                    elm.append($form);

                    if(res.admin) {
                        if(res.admin === true) {
                            $("#post-delete-group").attr('onclick', `delete_group(${id})`);
                            $("#post-delete-group").show();
                        }
                    }

                    if (res.msgs && res.msgs.length > 0) {
                        res.msgs.forEach(function(r) {
                            var $clone = $($('#message-group-msg').html());
                            $clone.find(".user").text(r.user);
                            $clone.find(".timestamp").text(r.timestamp);
                            $clone.find(".message").html(r.message);
                            $clone.css("background-color", (r.color ?? '#ffffff'));
                            $clone.find(".report-message-button").attr('data-testid', r.id);
                            $clone.attr('id', 'g' + r.id);

                            if(r.url) {
                                $clone.find(".user").attr("href", r.url);
                            }

                            elm.append($clone);
                        });
                    } else {
                        elm.append('<p>No messages yet. Try sending one!</p>');
                    }

                    elm.fadeIn('fast');
                    $("#post-comment").attr('onclick', `send_comment(${id})`);
                } else if(res.message) {
                    ui_error(res.message);
                }
            },
            error: function(xhr, text, error) {
                console.error(xhr.status, error);
                ui_error(error);
            }
        });
    }

    window.send_comment = function (groupid) {
        var btn = $(this);
        var commentBox = $("#comment-box").val();
        var commentBtnText = $("#post-comment span");

        commentBtnText.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
        btn.prop("disabled", true);

        $.ajax({
            url: "messages.php",
            method: "POST",
            data: {
                comment: true,
                groupid: groupid,
                commentbox: commentBox
            },
            success: function (response) {
                if (response.success) {
                    commentBtnText.html('Send');
                    btn.prop("disabled", false);

                    var url = new URL(window.location.href);
                    url.searchParams.set("m", response.groupid);
                    window.history.pushState(null, '', url);

                    load_message(response.groupid);
                    load_message_group();
                } else {
                    commentBtnText.html('Send');
                    btn.prop("disabled", false);
                    ui_error(response.error);
                }
            },
            error: function(xhr, text, error) {
                commentBtnText.html('Send');
                btn.prop("disabled", false);
                console.error(xhr.status, error);
                ui_error(error);
            }
        });
    }

    window.create_group = function () {
        var btn = $(this);
        var commentBox = $("[name='direct-box']").val();
        var commentBtnText = $("#group_create span");
        var prevBtnText = $("#group_create span").html();

        commentBtnText.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
        btn.prop("disabled", true);

        $.ajax({
            url: "messages.php",
            method: "POST",
            data: {
                group_create: true,
                commentbox: commentBox
            },
            success: function (response) {
                if (response.success) {
                    commentBtnText.html(prevBtnText);
                    btn.prop("disabled", false);

                    var url = new URL(window.location.href);
                    url.searchParams.set("m", response.groupid);
                    window.history.pushState(null, '', url);

                    load_message(response.groupid);
                    load_message_group();
                } else {
                    commentBtnText.html(prevBtnText);
                    btn.prop("disabled", false);
                    ui_error(response.error);
                }
            },
            error: function(xhr, text, error) {
                commentBtnText.html(prevBtnText);
                btn.prop("disabled", false);
                console.error(xhr.status, error);
                ui_error(error);
            }
        });
    }

    window.delete_group = function (id) {
        var btn = $(this);
        var btntext = $("#post-delete-group span");

        btntext.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
        btn.prop("disabled", true);

        if(confirm('Are you sure you want to permenantly delete this group?')) {
            $.ajax({
                url: "messages.php",
                method: "POST",
                dataType: 'json',
                data: {
                    group_delete: true,
                    groupid: id
                },
                success: function (response) {
                    if (response.success) {
                        var url = new URL(window.location.href);
                        var elm = $("#messages");

                        url.searchParams.set("m", response.groupid);
                        window.history.pushState(null, '', url);

                        elm.hide();
                        elm.children().not('template').remove();

                        load_message(response.groupid);
                        load_message_group();
                    } else {
                        btntext.html('Delete');
                        btn.prop("disabled", false);
                        load_message(id);
                        load_message_group();
                        ui_error(response.error);
                    }
                },
                error: function(xhr, text, error) {
                    btntext.html('Delete');
                    btn.prop("disabled", false);
                    console.error(xhr.status, error);
                    ui_error(error);
                }
            });
        } else {
            btntext.html('Delete');
            btn.prop("disabled", false);
            load_message(id);
            load_message_group();
        }
    }

    window.ui_error = function(text) {
        $err = $("#ajax-error");
        $err.text(text).slideToggle('fast');
        $err.delay(2500).slideToggle('fast');
        $('html, body').animate({scrollTop: 0}, 'slow');
    }

    $(document).on("click", ".report-message-button", function (e) {
        e.preventDefault();
        var message_id = $(this).attr('data-testid');
        $('#modal-report').show();

        $("#reportForm").submit(function (e) {
            e.preventDefault();

            $.get("/ajax/config.php", {
                get_csrf_token: true
            }, function (d) {
                let csrf_token = d.csrf_token;

                let payload = {
                    report_type: 'direct_message',
                    csrf_token: csrf_token,
                    reportv2: true,
                    reportable_id: message_id,
                    other: $("#reportForm #otherReason").val(),
                    reason: $("#reportForm [name='reason']:checked").val(),
                }

                $.ajax({
                    url: "/creation.php?id=null",
                    type: "POST",
                    data: payload,
                    dataType: "json",
                    success: function (response) {
                        $("#modal-report").hide();

                        if (response.success) {
                            alert(response.success);
                            $("#reportForm")[0].reset();
                        } else {
                            ui_error(response.error);
                        }
                    },
                    error: function () {
                        $("#modal-report").hide();
                        ui_error("An error occurred. Please try again later.");
                    }
                });
            }, "json").fail(function (xhr, text, err) {
                ui_error(text);
            });
        });
    });

    var url = new URL(window.location.href);
    var message = url.searchParams.get("m");

    if (message) {
        load_message(message);
    }
});