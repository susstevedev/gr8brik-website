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

        function renderComment(elm, comment, depth) {
            let $clone = $($('#comment-template').html());
            let marg = depth * 25;
            let width = Math.max(40, 60 - (depth * 2));

            $clone.find(".text").text(comment.text);
            $clone.find(".comment-user").text(comment.username);
            $clone.find(".comment-user").attr('href', '/@' + comment.username);
            $clone.css({"margin-left": marg + 'px', "width": width + '%'});

            $clone.find(".reply-btn").attr('data-id', comment.id);
            $clone.find(".edit-btn").attr('data-id', comment.id);

            if (comment.admin) {
                $clone.find(".comment-user").addClass('w3-text-red w3-hover-text-yellow');
            }

            if (comment.picture) {
                $clone.find(".comment-profile-picture img").attr('src', comment.picture);
            }

            if (comment.op) {
                $clone.find(".is-op").html('<b>OP</b> -');
            }

            $clone.find(".date").text(comment.date);
            $clone.find(".upvote-btn").attr('data-id', comment.id);
            $clone.attr('data-testid', comment.id);
            $clone.attr('data-level', depth);
            $clone.attr('id', 'comment' + comment.id);

            $("#comment-count").text(comment.replies);
            elm.after($clone);
        }

        $("#comment-form").on("focusin focusout", function(event) {
            var form_inside = $(event.relatedTarget).closest("#comment-form").length > 0;

            if (event.type === 'focusin') {
                $("#comment-form [name='comment-box']").stop().animate({ height: "80px" }, 'fast');
                $("#comment-form #post-comment").stop().show();
            } else if (event.type === 'focusout' && !form_inside) {
                $("#comment-form [name='comment-box']").stop().animate({ height: "60px" }, 'fast'); 
                $("#comment-form #post-comment").stop().fadeOut("fast");
            }
        });

        $('.comment').on('click', function (event) {
            if (event.target === this && event.offsetX < 10) {
                const $comment = $(this);
                const level = $comment.data('level');
                const collapsed = !$comment.hasClass('collapsed');

                $comment.toggleClass('collapsed', collapsed);

                let $next = $comment.next();
                while ($next.length && $next.data('level') > level) {
                    $next.toggle(!collapsed);
                    $next = $next.next();
                }

                $comment.find('.comment-body, [data-testid="gr8-comment-divider"]').slideToggle('fast');
                $comment.find('.comment-profile-picture img').animate({width: collapsed ? '25px' : '50px', height: collapsed ? '25px' : '50px'}, 'fast');
            }
        });

        $(document).on("click", "#post-comment", function (event) {
            event.preventDefault();
            const btn = $(this);

            fetchCSRFToken(function () {
                const commentBox = $("#comment-form [name='comment-box']").val();
                const prevCommentBtnText = $("#comment-btn-text").html();
                const commentBtnText = $("#comment-btn-text");
                const csrf = window.csrf_token;

                if(!commentBox) {
                    btn.hide();
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

                            if (response.comment) {
                                renderComment(elm, response.comment, 0);
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

        $(document).on('click', '.edit-btn, .cancel-btn', function(event) {
            event.preventDefault();

            let btn = $(this);
            let container = btn.closest('.comment');

            container.find('.comment-body .edit').toggleClass('w3-hide');
            container.find('.comment-body .text').toggleClass('w3-hide');
        });

        $(document).on('click', '.save-btn', function(event) {
            event.preventDefault();
            let container = $(this).closest('.comment');
            let commentId = container.attr('data-testid');
            let newText = container.find('.edit-textarea').val().trim();

            if(newText === "") {
                showError("Comment cannot be empty");
                return;
            }

            fetchCSRFToken(function () {
                $.ajax({
                    url: '/ajax/build',
                    type: 'POST',
                    data: {
                        edit_comment: true,
                        id: commentId,
                        commentbox: newText,
                        csrf_token: window.csrf_token
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success && response.comment && response.comment.text && response.comment.edited_at) {
                            if(container.find('.comment-body .edited-at')) {
                                container.find('.comment-body .edited-at').text('(edited)');
                                container.find('.comment-body .edited-at').attr('title', response.comment.edited_at);
                            }

                            container.find('.comment-body .text').html(response.comment.text).toggleClass('w3-hide');
                            container.find('.edit-textarea').val(response.comment.text);
                            container.find('.edit').toggleClass('w3-hide');
                        } else if(response.error) {
                            showError(response.error);
                        }
                    }
                });
            });
        });

        $(document).on('click', '.delete-btn', function() {
            let btn = $(this);
            let commentId = btn.data('id');
            let container = $(`#comment-${commentId}`);

            container.find('.delete').toggleClass('w3-hide');
        });

        $(document).on("click", ".reply-btn", function (event) {
            event.preventDefault();
            let container = $(this).closest('.comment .comment-body');
            let comment = $(this).data('id');
            let box = container.find('#reply-form-container-' + comment);

            if (box.length > 0) {
                box.slideUp('fast', function() {
                    $(this).remove();
                });
            } else {
                let $clone = $($('#reply-box').html());

                $clone.attr('id', 'reply-form-container-' + comment);
                $clone.find('.reply-box').attr('id', 'reply-box-' + comment);
                $clone.find('.post-reply').attr('data-parent', comment);

                $clone.hide();
                container.append($clone);
                $clone.slideDown('fast');
            }
        });

        $(document).on("click", ".post-reply", function (event) {
            event.preventDefault();
            const btn = $(this);
            const parent = btn.attr('data-parent');
            const $parent_elm = $(this).closest('.comment');
            const depth = parseInt($parent_elm.attr('data-level')) + 1;

            fetchCSRFToken(function () {
                const replyBox = $('#reply-box-' + parent).val();
                const prevText = $("#comment-btn-text").html();
                const commentText = $("#comment-btn-text");
                const csrf = window.csrf_token;

                if(!replyBox) {
                    return;
                }

                commentText.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
                btn.prop("disabled", true);

                const data = {
                    comment: true,
                    buildId: embed_model,
                    commentbox: replyBox,
                    parent: parent,
                    csrf_token: csrf
                }

                $.ajax({
                    url: "/ajax/build",
                    method: "POST",
                    dataType: 'json',
                    data: data,
                    success: function (response) {
                        if (response.success) {
                            commentText.html(prevText);
                            btn.prop("disabled", false);

                            if (response.comment) {
                                renderComment($parent_elm, response.comment, depth);
                                window.mode();
                                $parent_elm.find('#reply-form-container-' + parent).remove();
                            } else {
                                window.location.reload();
                            }
                        } else if (response.error) {
                            commentText.html(prevText);
                            btn.prop("disabled", false);
                            showError(response.error);
                        }
                    },
                    error: (xhr, text, err) => {
                        commentText.html(prevText);
                        console.error("error:", text, err, xhr);
                    },
                });
            });
        });
    });
})();