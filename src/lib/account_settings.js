(function () {
    $(document).ready(function() {
        $("#username-input").on("input", function() {
            var newUsername = $(this).val();
            $("#u_change").attr('disabled', true);
            $("#data-username-available").text('');

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                dataType: 'json',
                data: { check_username_available: true, username: newUsername },
                success: function(response) {
                    if (response.available == true) {
                        $("#u_change").attr('disabled', false);
                        $("#data-username-available").removeClass('w3-red').addClass('w3-green');
                    } else if (response.available == false) {
                        $("#data-username-available").removeClass('w3-green').addClass('w3-red');
                    }

                    $("#data-username-available").show().text(response.reason).delay(5000).hide(0);
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    console.error(jqXHR, textStatus, errorThrown);
                    DOMerror("An error occurred. Please try again later.");
                }
            });
        });

        $("#u_change").click(function(event) {
            event.preventDefault();

            var username = $("#username input[name='username']").val();
            var token = $.cookie('token');
            if ($("#username input[name='username']").val().trim() === '') {
                var username = $("#username input[name='username']").attr('placeholder');
            }

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                dataType: 'json',
                data: {
                    username_change: true,
                    username: username,
                    token: token
                },
                success: function(response) {
                    DOMsuccess(response.success || "Unknown response");
                    $(".usertext").text(username);
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    DOMerror(response.error || "An error occurred. Please try again later.");
                }
            });
        });

        $("#password").submit(function(event) {
            event.preventDefault();
            var formData = $("#password").serialize() + "&change=true";

            $.ajax({
                url: "../ajax/account_settings",
                method: "POST",
                dataType: 'json',
                data: formData,
                success: function(response) {
                    if(response.success) {
                        DOMsuccess(response.success);
                    } else if(response.error) {
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    DOMerror(response.error || "An error occurred. Please try again later.");
                }
            });
        });

        $("#email").submit(function(event) {
            event.preventDefault();
            var formData = $("#email").serialize() + "&mail_change=true";

            $.ajax({
                url: "../ajax/account_settings",
                method: "POST",
                dataType: 'json',
                data: formData,
                success: function(response) {
                    if(response.success) {
                        DOMsuccess(response.success);
                    } else if(response.error) {
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    DOMerror(response.error || "An error occurred. Please try again later.");
                }
            });
        });

        $("#blog_user_change").click(function(event) {
            event.preventDefault();

            let blog_user = $("#blog_user_change input[name='user']").val();
            var token = $.cookie('token');
            if ($("#blog_user_change input[name='user']").val().trim() === '') {
                let blog_user = null;
            }

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                dataType: 'json',
                data: {
                    blog_user_change: true,
                    blog_user: blog_user,
                    token: token
                },
                success: function(response) {
                    DOMsuccess(response.success || "Unknown response");
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    DOMerror(response.error || "An error occurred. Please try again later.");
                }
            });
        });

        $("#a_change").click(function(event) {
            event.preventDefault();

            var description = $("#about textarea[name='description']").val();
            var token = $.cookie('token');

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                dataType: 'json',
                data: {
                    about_change: true,
                    description: encodeURIComponent(description),
                    token: token
                },
                success: function(response) {
                    DOMsuccess(response.success || "Unknown response");
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    DOMerror(response.error || "An error occurred. Please try again later.");
                }
            });
        });

        $("#twitter_change").click(function(event) {
            event.preventDefault();

            var handle = $("#twitter input[name='handle']").val();
            var token = $.cookie('token');

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                dataType: 'json',
                data: {
                    twitter_change: true,
                    handle: handle,
                    token: token
                },
                success: function(response) {
                    DOMsuccess(response.success || "Unknown response");
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    DOMerror(response.error || "An error occurred. Please try again later.");
                }
            });
        });

        $("#bsky_change").click(function(event) {
            event.preventDefault();

            var handle = $("#bsky input[name='handle']").val();
            var token = $.cookie('token');

            $.ajax({
                url: "../ajax/account_settings",
                method: "GET",
                dataType: 'json',
                data: {
                    bsky_change: true,
                    handle: handle,
                    token: token
                },
                success: function(response) {
                    if(response.success) {
                        DOMsuccess(response.success);
                    } else if(response.error) {
                        DOMerror(response.error || "An error occurred. Please try again later.");
                    }
                },

                error: function(jqXHR, textStatus, errorThrown) {
                    var response = JSON.parse(jqXHR.responseText);
                    DOMerror(response.error || "An error occurred. Please try again later.");
                }
            });
        });

        // Source - https://stackoverflow.com/a/23981045
        // Posted by bloodyKnuckles, modified by community. See post 'Timeline' for change history
        // Retrieved 2026-08-02, License - CC BY-SA 4.0

        $('#pictureForm #picture').on('click', function(event) {
            event.preventDefault();

            var file_data = $('#pictureForm #fileToUpload').prop('files')[0];
            var form_data = new FormData();
            form_data.append('fileToUpload', file_data);
            form_data.append('picture', 'true');

            $.ajax({
                url: "../ajax/account_settings",
                dataType: 'json',
                contentType: false,
                processData: false,
                data: form_data,
                type: 'POST',
                success: function(res) {
                    if (res && res.success && res.image) {
                        $('#pictureForm #fileToUpload').css('background-image', 'url(' + res.image + ')');
                    } else if (res && res.error) {
                        DOMerror(res.error);
                    } else {
                        DOMerror('An error occured while uploading the image');
                    }
                },
                error: function(xhr, text, error) {
                    var response = JSON.parse(xhr.responseText);
                    DOMerror(response.error || "An error occured while uploading the image");
                }
            });
        });

        $('#pictureForm #remove_picture').on('click', function(event) {
            event.preventDefault();

            $.ajax({
                url: "../ajax/account_settings",
                dataType: 'json',
                data: {
                    remove_picture: true
                },
                type: 'POST',
                success: function(res) {
                    if (res && res.success && res.image) {
                        $('#pictureForm #fileToUpload').css('background-image', 'url(' + res.image + ')');
                        $('#pictureForm #remove_picture').remove();
                    } else if (res && res.error) {
                        DOMerror(res.error);
                    } else {
                        DOMerror('An error occured while removing the image');
                    }
                },
                error: function(xhr, text, error) {
                    var response = JSON.parse(xhr.responseText);
                    DOMerror(response.error || "An error occured while removing the image");
                }
            });
        });

        $('#update_private_page').on('click', function(event) {
            event.preventDefault();
            var btn = $(this);

            $.ajax({
                url: "../ajax/account_settings",
                dataType: 'json',
                data: { private_profile: true },
                type: 'POST',
                success: function(res) {
                    if (res && res.success && res.message) {
                        DOMsuccess(res.message);

                        if(res.text) {
                            btn.text(res.text);
                        }
                    }
                },
                error: function(xhr, text, error) {
                    var response = JSON.parse(xhr.responseText);
                    DOMerror(response.message || 'Error privating or unprivating page.');
                }
            });
        });

        function DOMerror(error) {
            $(".error").show();
            if (error) {
                $(".error").text(error);
            }
            $('html, body').animate({
                scrollTop: 0
            }, 'slow');
        }

        function DOMsuccess(message) {
            $(".success").show();
            if (message) {
                $(".success").text(message);
            }
            $('html, body').animate({
                scrollTop: 0
            }, 'slow');
        }
    });
})();