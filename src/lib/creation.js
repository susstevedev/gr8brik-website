(function () {
    $(document).ready(function () {
        function fetchCSRFToken(callback) {
            $.get("/ajax/config.php", {
                get_csrf_token: true
            }, function (data) {
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

        $(document).on("click", "#report-comment-button", function (event) {
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
                        error: function(xhr, text, error) {
                            $("#report-comment").hide();
                            console.error(xhr, text, error);
                            showError(error);
                        }
                    });
                });
            });
        });

        $(document).on("click", "#report-creation-button", function (event) {
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
                        error: function(xhr, text, error) {
                            $("#report-comment").hide();
                            console.error(xhr, text, error);
                            showError(error);
                        }
                    });
                });
            });
        });

        $(document).on("click", "#delete-comment-button", function (event) {
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
                                let comment_txt_selector = '#comment' + comment_id + ' .comment-body .text';
								let comment_error_selector = '#comment' + comment_id + ' .comment-body .comment-error';

                                $("#deleteCommentForm")[0].reset();

								if(response.type === 'delete') {
                                    if(response.admin === true) {
                                        $(comment_txt_selector).addClass('w3-text-red');
                                    } else {
                                        $(comment_selector).remove();
                                    }
                                } else if(response.type === 'restore') {
									$(comment_error_selector).remove();
								}
                            } else {
                                showError(response.error);
                            }
                        },
                        error: function(xhr, text, error) {
                            $("#delete-comment").hide();
                            console.error(xhr, text, error);
                            showError(error);
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
                    error: function(xhr, text, error) {
                        console.error(xhr, text, error);
                        showError(error);
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

        $(document).on("click", ".upvote-btn, .downvote-btn", function () {
            event.preventDefault();
            let btn = $(this);
            var $parent = $(this).closest('.comment');
            var comment_id = $parent.attr('data-testid');

			var data = {
				comment_favs: true,
				comment_id: comment_id,
			}

			if(btn.hasClass('upvote-btn')) {
				data.fav = true;
			} else if(btn.hasClass('downvote-btn')) {
				data.unfav = true;
			}

            $.ajax({
                url: "/ajax/build",
                method: "POST",
                dataType: 'json',
                data: data,
                success: function (response) {
                    if (response.success) {
                        $parent.find(".votes .count").text(response.count);
						if(btn.hasClass('upvote-btn')) {
                            btn.removeClass('upvote-btn fa fa-star-o w3-yellow').addClass('downvote-btn fa fa-star w3-pink');
						} else if(btn.hasClass('downvote-btn')) {
                            btn.removeClass('downvote-btn fa fa-star w3-pink').addClass('upvote-btn fa fa-star-o w3-yellow');
						}
                    } else if (response.error) {
                        showError(response.error);
                    }
                },
                error: function(xhr, text, error) {
                    console.error(xhr, text, error);
                    showError(error);
                }
            });
        });

        function renderComment(elm, res, depth) {
            let $clone = $($('#comment-template').html());
            let marg = depth * 25 + 'px';
            let width = Math.max(40, 60 - (depth * 2)) + '%';

            $clone.find(".text").html(res.comment.text);
            $clone.find(".edit .edit-textarea").html(res.comment.text_og);

            $clone.find(".comment-user").text(res.user.name).attr('href', '/@' + res.user.name);
            $clone.find(".comment-body").css("width", width);
            $clone.css("margin-left", marg);

            $clone.find(".reply-btn").attr('data-id', res.comment.id);
            $clone.find(".edit-btn").attr('data-id', res.comment.id);

            if (res.user.admin) {
                $clone.find(".comment-user").addClass('w3-text-red w3-hover-text-yellow');
            }

            if (res.user.picture) {
                $clone.find(".comment-profile-picture img").attr('src', res.user.picture);
            }

            if (res.user.op) {
                $clone.find(".is-op").html('<b>OP</b> -');
            }

            $clone.find(".date").text(res.comment.date);
            $clone.find(".votes .count").text(res.comment.favs);
            $clone.find(".upvote-btn").attr('data-id', res.comment.id);
            $clone.attr('data-testid', res.comment.id);
            $clone.attr('data-level', depth);
            $clone.attr('id', 'comment' + res.comment.id);

            $("#comment-count").text(res.thread.replies);

            if(depth > 0) {
                elm.after($clone);
            } else {
                elm.children().last().after($clone);
            }
        }

        function formatting_init(txtarea) {
            if($(`.formatting-toolbar[data-textarea="${txtarea}"]`)[0]) {
                return;
            }

            let $clone = $($('#comment-formatting-toolbar').html());
            $clone.attr('data-textarea', txtarea);
            $(txtarea).after($clone);

            $('.formatting-toolbar').on('mousedown', function (event) {
                event.preventDefault();
                var btn = $(event.target).closest('button');
                var bar = $(this);

                if(!btn.length) {
                    return;
                }

                var type = btn.data('format');
                var textarea_sel = bar.data('textarea');

                var textarea = $(textarea_sel)[0]; 
                var sel_start = textarea.selectionStart;
                var sel_end = textarea.selectionEnd;

                var fullText = $(textarea).val();
                var selected = fullText.substring(sel_start, sel_end);

                if (sel_start === sel_end || !type) {
                    return;
                }

                var formatted = "";
                switch (type) {
                    case '':
                        break;

                    case 'italic':
                        formatted = "_" + selected + "_";
                        break;

                    case 'bold':
                        formatted = "**" + selected + "**";
                        break;

                    case 'del':
                        formatted = "~~" + selected + "~~";
                        break;

                    case 'url':
                        formatted = `[${selected}](${selected})`;
                        break;

                    case 'img':
                        formatted = `![Alt Text](${selected})`;
                        break;

                    case 'head1':
                        formatted = `# ${selected}`;
                        break;

                    case 'head2':
                        formatted = `## ${selected}`;
                        break;

                    case 'head3':
                        formatted = `### ${selected}`;
                        break;

                    default:
                        break;
                }

                var newtext = fullText.substring(0, sel_start) + formatted + fullText.substring(sel_end);
                $(textarea).val(newtext);
                textarea.focus();
                textarea.setSelectionRange(sel_start + formatted.length, sel_end + formatted.length);
            });
        }

        $("#comment-form").on("focusin focusout", function(event) {
            var form_inside = $(event.relatedTarget).closest("#comment-form").length > 0;
            var comment_box_sel = "#comment-form [name='comment-box']";
            var comment_box = $(comment_box_sel);

            if (event.type === 'focusin') {
                comment_box.stop().animate({ height: "80px" }, 'fast');
                $("#comment-form .comment-button").stop().show();
                formatting_init(comment_box_sel);
            } else if (event.type === 'focusout' && !form_inside) {
                comment_box.stop().animate({ height: "60px" }, 'fast'); 
                $("#comment-form .comment-button").stop().hide();
                $("#comment-form .formatting-toolbar").stop().remove();
            }

            if($.trim(comment_box.val()).length === 0) {
                $("#comment-view-toggle button.preview").addClass('w3-disabled');
            } else {
                $("#comment-view-toggle button.preview").removeClass('w3-disabled');
            }
        });

        $("#comment-form [name='comment-box']").on("input", function(event) {
            if($.trim($(this).val()).length <= 0) {
                $("#comment-view-toggle button.preview").addClass('w3-disabled');
            } else {
                $("#comment-view-toggle button.preview").removeClass('w3-disabled');
            }

            if($.trim($(this).val()).length >= 500) {
                $("#comment-form .comment-button").stop().addClass('w3-disabled');
            } else {
                $("#comment-form .comment-button").stop().removeClass('w3-disabled');
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

                $comment.find('.comment-body, [data-testid="gr8-comment-divider"], .comment-error').slideToggle('fast');
                $comment.find('.comment-profile-picture img').animate({width: collapsed ? '25px' : '50px', height: collapsed ? '25px' : '50px'}, 'fast');
            }
        });

        var PREVIEW_TEXT = null;

        $(document).on("click", "#comment-view-toggle button", function (event) {
            event.preventDefault();
            var btn = $(this);
            var comment_box = $("#comment-form [name='comment-box']").val();
            var preview_button = $('#comment-form .comment-preview');
            var comment_button = $('#comment-form #post');

            var preview_sels = {
                'text': $('#comment-form .comment-preview .text'),
                'image': $('#comment-form #comment-profile-picture img'),
                'user_name': $('#comment-form .comment-preview .username'),
                'date': $('#comment-form .comment-preview .date'),
                'favs': $('#comment-form .comment-preview .votes .count'),
            };

            if(btn.hasClass('edit')) {
                comment_button.show();
                preview_button.hide();
            } else if(btn.hasClass('preview') && !btn.hasClass('w3-disabled')) {
                if($.trim(comment_box) === PREVIEW_TEXT) {
                    preview_button.show();
                    comment_button.hide();
                    return;
                }

                fetchCSRFToken(function () {
                    $.ajax({
                        url: "../ajax/build",
                        dataType: 'json',
                        data: {
                            comment_preview: true,
                            commentbox: comment_box,
                            csrf_token: window.csrf_token
                        },
                        type: 'POST',
                        success: function(res) {
                            if(res) {
                                if (res.success && res.comment) {
                                    preview_sels.text.html(res.comment.text);
                                    preview_sels.image.attr('src', res.user.picture);
                                    preview_sels.user_name.html(res.user.name);
                                    preview_sels.user_name.attr('href', res.user.url);
                                    preview_sels.date.html(res.comment.edited_at);
                                    preview_sels.favs.html(res.comment.favs);
                                    PREVIEW_TEXT = comment_box;
                                } else if (res.message && !res.success) {
                                    showError(res.message);
                                    preview_sels.text.html(comment_box);
                                    PREVIEW_TEXT = comment_box;
                                }
                                preview_button.show();
                                comment_button.hide();
                            }
                        },
                        error: function(xhr, text, error) {
                            showError("Couldn\'t load the preview");
                            console.log(xhr, text, error);
                        }
                    });
                });
            }
        });

        $(document).on("click", "#post-comment, .post-reply", function (event) {
            event.preventDefault();
            var btn = $(this);

            if(btn.hasClass('post-reply')) {
				var isReply = true;
			} else {
				var isReply = false;
			}

            fetchCSRFToken(function () {
                if(isReply) {
                    var parent = btn.attr('data-parent');
                    var commentBox = $(`#reply-box-${parent}`);
                    var commentBtnText = $(".post-reply .btn-text");
                    var $parent_elm = $('#data-comment-wrapper').find(`.comment[data-testid="${parent}"]`);
                    var depth = parseInt($parent_elm.attr('data-level')) + 1;
                } else {
                    var parent = false;
                    var commentBox = $("#comment-form [name='comment-box']");
                    var commentBtnText = $("#comment-btn-text");
                    var $parent_elm = $('#data-comment-wrapper');
                    var depth = 0;
                }
                var prevText = commentBtnText.html();

                if(!commentBox.val() || btn.hasClass('w3-disabled')) {
                    if(!isReply) {
                        commentBox.stop().animate({ height: "60px" }, 'fast'); 
                        $("#comment-form .comment-button").stop().hide();
                    }
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
                        commentbox: commentBox.val(),
                        parent: parent,
                        csrf_token: window.csrf_token
                    },
                    success: function (response) {
                        if (response.success) {
                            commentBtnText.html(prevText);
                            btn.prop("disabled", false);

                            if (response.comment) {
                                renderComment($parent_elm, response, depth);
                                window.mode();

                                if(isReply) {
                                    $parent_elm.find('#reply-form-container-' + parent).remove();
                                } else {
                                    $("html, body").animate({ scrollTop: $(document).height() }, "slow");
                                }

                                showSuccess(response.message);
                            } else {
                                window.location.reload();
                            }
                        } else if (response.error) {
                            commentBtnText.html(prevText);
                            btn.prop("disabled", false);
                            showError(response.error);
                        }
                    },
                    error: function(xhr, text, err) {
                        commentBtnText.html(prevText);
                        console.error(xhr, text, err);
                        showError(err);
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
            container.find('.edit')[0].reset();
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
                            container.find('.edit-textarea').val(response.comment.text_og);
                            container.find('.edit').toggleClass('w3-hide');
                        } else if(response.error) {
                            showError(response.error);
                        }
                    },
                    error: function(xhr, text, err) {
                        console.error(xhr, text, err);
                        showError(err);
                    },
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
                    $(this).stop().remove();
                    $("#reply-box-" + comment).closest(".formatting-toolbar").stop().remove();
                });
            } else {
                let $clone = $($('#reply-box').html());

                $clone.attr('id', 'reply-form-container-' + comment);
                $clone.find('.reply-box').attr('id', 'reply-box-' + comment);
                $clone.find('.post-reply').attr('data-parent', comment);

                $clone.hide();
                container.append($clone);
                $clone.slideDown('fast');
                formatting_init('#reply-box-' + comment);
            }
        });

        var USER_MENTIONS = [];

        $(".user-mention").on("mouseover", function (event) {
            event.preventDefault();
            var mention = $(this);
            var userid = mention.attr('data-userid');
            var active = mention.attr('data-active') ? true : false;

            if(!userid || active) {
                return;
            }

            if(USER_MENTIONS[userid]) {
                success(USER_MENTIONS[userid], mention);
            } else {
                $.ajax({
                    url: "/ajax/profile",
                    method: "GET",
                    dataType: 'json',
                    data: {
                        profile: true,
                        actorId: userid,
                    },
                    success: function (response) {
                        if (response.success) {
                            success(response, mention);
                        } else if (response.message) {
                            error(response, mention);
                        }
                    },
                    error: function(xhr, text, err) {
                        error(JSON.parse(xhr.responseText), mention);
                        console.error("error:", text, err, xhr);
                    },
                });
            }

            function error(err, mention) {
                $(`<div class="mention-details-error w3-red w3-padding w3-margin-bottom w3-round w3-border w3-border-pink" style="display:none">
                    <h3>${err.title}</h3>
                    <h4>${err.message}</h4>
                </div>`).insertAfter(mention).slideToggle('fast').delay(5000).slideToggle('fast');
            }

            function success(response, mention) {
                mention.attr('data-active', true);
                USER_MENTIONS[userid] = response;

                $(`<div class="mention-details w3-card-2 w3-light-grey w3-round w3-padding-small w3-border w3-border-grey" data-userid="${userid}" style="display:none">
                    <img width="50px" height="50px" class="w3-round" src="${response.picture}">
                    <a href="${response.url}" class="w3-padding-small ${response.admin ? 'w3-text-red' : null}"><i class="fa fa-at" aria-hidden="true"></i>${response.username}</a>
                    ${response.badge ? "<i class='fa fa-check-circle w3-text-blue w3-padding-small' aria-hidden='true'></i>" : '' }
                    <span>${response.stats.followers} followers • ${response.stats.following} following</span>
                    <p>Member since ${response.age}</p>
                </div>`).insertAfter(mention).fadeIn('fast');

                $(".mention-details, .user-mention").on("mouseleave", function (event) {
                    event.preventDefault();

                    var next = event.relatedTarget || event.originalEvent.toElement;
                    var userid = $(this).attr('data-userid');
                    var popover = $(`.mention-details[data-userid=${userid}]`);
                    var mention = $(`.user-mention[data-userid=${userid}]`);
                    var active = mention.attr('data-active') ? true : false;

                    console.log(next);

                    if(active && $(next).hasClass('mention-details') || active && $(next).hasClass('user-mention')) {
                        return;
                    }

                    popover.remove();
                    mention.attr('data-active', null);
                    return;
                });
            }
        });
    });
})();