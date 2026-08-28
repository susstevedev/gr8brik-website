(function () {
    $(document).ready(function () {
        function fetchCSRFToken(callback) {
            $.get("/ajax/config.php", {
                get_csrf_token: true
            }, function (data) {
                console.log(data);
                $("#csrf_token").val(data.csrf_token);
                window.csrf_token = data.csrf_token;
                callback();
            }, "json")
                .fail(function (xhr, text, err) {
                    alert(text);
                });
        }

        function showError(text) {
            $('#ajax-error').text(text).slideDown("fast").delay(5000).slideUp("fast");
        }

        function showSuccess(text) {
            $('#ajax-success').text(text).slideDown('fast').delay(5000).slideUp('fast');
        }

        $(document).on("click", "#report-comment-button", function () {
            event.preventDefault();
            var $parent = $(this).closest('.comment');
            var comment_id = $parent.attr('data-testid');

            $('#report-comment').show();
            $("#reportCommentForm [name='comment_id']").val(comment_id);

            $("#reportCommentForm").submit(function (e) {
                e.preventDefault();

                fetchCSRFToken(function () {

                    let payload = {
                        report_type: 'comment',
                        csrf_token: window.csrf_token,
                        reportv2: true,
                        reportable_id: comment_id,
                        other: $("#reportCommentForm #otherReason").val(),
                        reason: $("#reportCommentForm [name='reason']:checked").val(),
                    }
                    console.log(payload);

                    $.ajax({
                        url: "",
                        type: "POST",
                        data: payload,
                        dataType: "json",
                        success: function (response) {
                            $("#report-comment").hide();

                            if (response.success) {
                                showSuccess(response.success);
                                $("#reportCommentForm")[0].reset();
                            } else {
                                showError(response.error);
                            }
                        },
                        error: function () {
                            $("#report-comment").hide();
                            showError("An error occurred. Please try again later.");
                        }
                    });
                });
            });
        });

        $(document).on("click", "#report-creation-button", function () {
            event.preventDefault();
            let creation_id = $(this).data("id");

            $('#report-comment').show();
            $("#reportCommentForm [name='comment_id']").val(creation_id);

            $("#reportCommentForm").submit(function (e) {
                e.preventDefault();

                fetchCSRFToken(function () {

                    let payload = {
                        report_type: 'creation',
                        csrf_token: window.csrf_token,
                        reportv2: true,
                        reportable_id: creation_id,
                        other: $("#reportCommentForm #otherReason").val(),
                        reason: $("#reportCommentForm [name='reason']:checked").val(),
                    }
                    console.log(payload);

                    $.ajax({
                        url: "",
                        type: "POST",
                        data: payload,
                        dataType: "json",
                        success: function (response) {
                            $("#report-comment").hide();

                            if (response.success) {
                                showSuccess(response.success);
                                $("#reportCommentForm")[0].reset();
                            } else {
                                showError(response.error);
                            }
                        },
                        error: function () {
                            $("#report-comment").hide();
                            showError("An error occurred. Please try again later.");
                        }
                    });
                });
            });
        });

        $(document).on("click", "#delete-comment-button", function () {
            event.preventDefault();
            $('#delete-comment').show();
            var $parent = $(this).closest('.comment');
            var comment_id = $parent.attr('data-testid');

            $("#deleteCommentForm").submit(function (e) {
                e.preventDefault();

                fetchCSRFToken(function () {
                    let payload = {
                        csrf_token: window.csrf_token,
                        delete_comment: true,
                        id: comment_id,
                    }
                    console.log(payload);

                    $.ajax({
                        url: "",
                        type: "POST",
                        data: payload,
                        dataType: "json",
                        success: function (response) {
                            $("#delete-comment").hide();

                            if (response.success) {
                                showSuccess(response.success);
								let comment_selector = '#comment' + comment_id;
								let comment_error_selector = '.comment-error-' + comment_id;

                                $("#deleteCommentForm")[0].reset();

								if(response.type === 'delete') {
									$(comment_selector).remove();
								} else if(response.type === 'restore') {
									$(comment_error_selector).remove();
								}
                            } else {
                                showError(response.error);
                            }
                        },
                        error: function () {
                            $("#delete-comment").hide();
                            showError("An error occurred. Please try again later.");
                        }
                    });
                });
            });
        });

        $("#deleteModelForm").submit(function (e) {
            e.preventDefault();
            fetchCSRFToken(function () {
                $.ajax({
                    url: "",
                    type: "POST",
                    data: $("#deleteModelForm").serialize(),
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            showSuccess(response.success);
                            window.location.reload();
                        } else {
                            showError(response.error);
                        }
                    },
                    error: function () {
                        showError("An error occurred. Please try again later.");
                    }
                });
            });
        });

        $(document).on("click", ".like-creation, .unlike-creation", function (event) {
            event.preventDefault();

            let btn = $(this);
            let btnspan = btn.find('span.text');
            let btnicon = btn.find('span.fa');

            let is_like = btn.hasClass('like-creation');
            let data = {
                model_id: embed_model
            };

            if (is_like) {
                data.upvote = true;
            } else {
                data.downvote = true;
            }

            $.ajax({
                url: "/ajax/build",
                method: "POST",
                dataType: 'json',
                data: data,
                success: function (res) {
                    if ((is_like && res.success) || !is_like) {
                        if (is_like) {
                            btnspan.text(res.text);
                            btnicon.removeClass('fa-star-o').addClass('fa-star');
                            btn.removeClass('like-creation w3-yellow')
                                .addClass('unlike-creation w3-red');
                        } else {
                            btnspan.text(res.text);
                            btnicon.removeClass('fa-star').addClass('fa-star-o');
                            btn.removeClass('unlike-creation w3-red')
                                .addClass('like-creation w3-yellow');
                        }
                    } else if (res.error) {
                        showError(res.error);
                    }
                },
                error: (xhr, text, err) => {
                    console.error(text, err, xhr);
                    try {
                        let res = JSON.parse(xhr.responseText);
                        let texterror = res.error || "An error occurred, try again later";
                        showError(texterror);
                    } catch (e) {
                        showError("An error occurred. Please try again later.");
                    }
                }
            });
        });

        $(document).on("click", ".creation-comment-subscribe, .creation-comment-unsubscribe, .creation-fav-subscribe, .creation-fav-unsubscribe", function (event) {
            event.preventDefault();

            let btn = $(this);
			let btntext = $('.creation-subscribe .text');
            let btnicon = $('.creation-subscribe .fa');

            let is_sub = btn.hasClass('creation-comment-subscribe') || btn.hasClass('creation-fav-subscribe');
			let is_comment = btn.hasClass('creation-comment-subscribe') || btn.hasClass('creation-comment-unsubscribe');
			let is_fav = btn.hasClass('creation-fav-subscribe') || btn.hasClass('creation-fav-unsubscribe');

			let type = null;
			if(is_fav) {
				type = 'creation_fav';
			} else if(is_comment) {
				type = 'comment';
			}

			if(!type) {
				showError('Not a valid type of action');
				return;
			}

            let data = {
                model_id: embed_model,
				type: type,
            };

            if (is_sub) {
                data.subscribe = true;
            } else {
                data.unsubscribe = true;
            }

            $.ajax({
                url: "/ajax/build",
                method: "POST",
                dataType: 'json',
                data: data,
                success: function (res) {
                    if (res.success) {
                        if (is_sub) {
							btnicon.removeClass('fa-plus-square-o').addClass('fa-plus-square');
							if(res.type === 'creation_fav') {
								btn.removeClass('creation-fav-subscribe w3-yellow').addClass('creation-fav-unsubscribe w3-red');
							} else if(res.type === 'comment') {
								btn.removeClass('creation-comment-subscribe w3-yellow').addClass('creation-comment-unsubscribe w3-red');
							}
                        } else {
                            btnicon.removeClass('fa-plus-square').addClass('fa-plus-square-o');
							if(res.type === 'creation_fav') {
								btn.removeClass('creation-fav-unsubscribe w3-red').addClass('creation-fav-subscribe w3-yellow');
							} else if(res.type === 'comment') {
								btn.removeClass('creation-comment-unsubscribe w3-red').addClass('creation-comment-subscribe w3-yellow');
							}
                        }
						btntext.text(res.textParent);
						btn.text(res.text);
                    } else if (res.error) {
                        showError(res.error);
                    }
                },
                error: (xhr, text, err) => {
                    console.error(text, err, xhr);
                    try {
                        let res = JSON.parse(xhr.responseText);
                        let texterror = res.error || "An error occurred, try again later";
                        showError(texterror);
                    } catch (e) {
                        showError("An error occurred. Please try again later.");
                    }
                }
            });
        });

        $(document).on("click", ".upvote-btn", function () {
            event.preventDefault();
            let btn = $(this);
            var $parent = $(this).closest('.comment');
            var comment_id = $parent.attr('data-testid');

            $.ajax({
                url: "/ajax/build",
                method: "POST",
                dataType: 'json',
                data: {
                    upvote_comment: true,
                    comment_id: comment_id
                },
                success: function (response) {
                    if (response.success) {
                        $parent.find(".votes .count").text(response.count);
                        btn.replaceWith(`<button data-id="${comment_id}" class="downvote-btn fa fa-star w3-btn w3-pink w3-hover-opacity w3-round w3-padding-small"></button>`);
                    } else if (response.error) {
                        showError(response.error);
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error("error:", textStatus, errorThrown, jqXHR);
                    const response = JSON.parse(jqXHR.responseText);
                    showError(response.error);
                }
            });
        });

        $(document).on("click", ".downvote-btn", function () {
            event.preventDefault();
            let btn = $(this);
            var $parent = $(this).closest('.comment');
            var comment_id = $parent.attr('data-testid');

            $.ajax({
                url: "/ajax/build",
                method: "POST",
                dataType: 'json',
                data: {
                    downvote_comment: true,
                    comment_id: comment_id
                },
                success: function (response) {
                    if (response.success) {
                        $parent.find(".votes .count").text(response.count);
                        btn.replaceWith(`<button data-id="${comment_id}" class="upvote-btn fa fa-star-o w3-btn w3-yellow w3-hover-opacity w3-round w3-padding-small"></button>`);
                    } else if (response.error) {
                        showError(response.error);
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error("error:", textStatus, errorThrown, jqXHR);
                    const response = JSON.parse(jqXHR.responseText);
                    showError(response.error);
                }
            });
        });

        $(document).on("click", "#post-comment", function () {
            event.preventDefault();

            fetchCSRFToken(function () {
                const btn = $(this);
                const commentBox = $("#comment-form [name='comment-box']").val();
                const prevCommentBtnText = $("#comment-btn-text").html();
                const commentBtnText = $("#comment-btn-text");
                const errorElm = $("#ajax-error");
                const csrf = window.csrf_token;

                if(!commentBox) {
                    return;
                }

                commentBtnText.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                btn.prop("disabled", true);

                $.ajax({
                    url: "/ajax/build",
                    method: "POST",
                    dataType: 'json',
                    data: {
                        comment: true,
                        buildId: embed_model,
                        commentbox: commentBox,
                        csrf_token: csrf
                    },
                    success: function (response) {
                        if (response.success) {
                            commentBtnText.html(prevCommentBtnText);
                            btn.prop("disabled", false);
                            var elm = $('#data-comment-wrapper');

                            if (response.success && response.comment) {
                                let $clone = $($('#comment-template').html());

                                $clone.find(".comment-text").text(response.comment.text);
                                $clone.find(".comment-user").text(response.comment.username);
                                $clone.find(".comment-user").attr('href', '/user/' + response.comment.userid);

                                if (response.comment.admin) {
                                    $clone.find(".comment-user").addClass('w3-text-red w3-hover-text-yellow');
                                }

                                if (response.comment.picture) {
                                    $clone.find(".comment-profile-picture img").attr('src', response.comment.picture);
                                }

                                $clone.find(".date").text(response.comment.date);
                                $clone.find(".upvote-btn").attr('data-id', response.comment.id);
                                $clone.attr('data-testid', response.comment.id);
                                $clone.attr('id', 'comment' + response.comment.id);

                                $("#comment-count").text(response.comment.replies);
                                elm.append($clone);

								window.mode();
                                $("html, body").animate({ scrollTop: $(document).height() }, "slow");
                            } else {
                                window.location.reload();
                            }
                        } else if (response.error) {
                            commentBtnText.html(prevCommentBtnText);
                            btn.prop("disabled", false);
                            showError(response.error);
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        commentBtnText.html(prevCommentBtnText);
                        btn.prop("disabled", false);
                        console.error("error:", textStatus, errorThrown, jqXHR);
                        const response = JSON.parse(jqXHR.responseText);
                        showError(response.error);
                    },
                });
            });
        });
    });
})();