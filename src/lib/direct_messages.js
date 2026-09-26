$(document).ready(function () {
    function load_message_group() {
        $.ajax({
            url: 'messages.php',
            type: 'GET',
            data: {
                group: true
            },
            success: function (res) {
                let groups;

                groups = res.map(group => `
                    <div class='w3-light-grey w3-padding-small w3-round w3-text-black w3-hover-grey' style="cursor:pointer;" id="${group.id}" onclick='load_message(${group.id})'>
                        <p><img src="${group.pictureurl}" class="w3-circle" width="50px" height="50px"> ${group.user}</p>
                        <p class="w3-text-grey">${group.joined}</p>
                    </div><br />
                `);
                $("#groups").append(groups).fadeIn();
            },
            error: (xhr, text, err) => {
                console.error("error: " + text, err, xhr);
                let response = JSON.parse(xhr.responseText);
                $("#groups").append(response.error).fadeIn();
            }
        });
    }
    load_message_group();

    window.load_message = function (id) {
        $.ajax({
            url: 'messages.php',
            type: 'GET',
            data: {
                message: id
            },
            success: function (res) {
                let messages;
                let res2 = JSON.parse(res);

                var url = new URL(window.location.href);
                url.searchParams.set("m", id);
                window.history.pushState(null, '', url);

                let form = `
                        <div id="comment-form w3-half">
                        	<div id="post">
                            	<textarea name="comment-box" id="comment-box" class="w3-input w3-hover-light-grey" placeholder="Message... (bbcode supported)" rows="auto" cols="40"></textarea>
                        	</div>
                        	<button id="post-comment" class="w3-btn w3-white w3-hover-light-grey w3-padding-small w3-border w3-border-grey">
                        		<span>Send</span>
                        	</button></div>
                        `;

                if (res2) {
                    messages = res2.map(message => `
                            	<div style="background-color: ${message.color}; color: #000;" class='w3-padding-small w3-round w3-margin-bottom w3-margin-top w3-card-2' id="${message.id}">
                                	<p><a href="/${message.user}">${message.user}</a> sent ${message.timestamp}</p>
                                	<p>${message.message}</p>
                            	</div>
                        	`);
                } else {
                    messages = "<b>No messages yet</b>";
                }

                $("#messages").hide();
                $("#messages").html(form);
                $("#messages").fadeIn().append(messages);
                $("#post-comment").attr('onclick', `send_comment(${id})`);
            }
        });
    }

    window.send_comment = function (groupid) {
        event.preventDefault();

        const btn = $(this);
        const commentBox = $("#comment-box").val();
        const commentBtnText = $("#comment-btn-text");

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
                    url.searchParams.set("m", groupid);
                    window.location.href = url;
                } else {
                    commentBtnText.html('Send');
                    btn.prop("disabled", false);
                    alert(response.error);
                }
            },
            error: (xhr, text, err) => {
                commentBtnText.html('Send');
                btn.prop("disabled", false);
                console.error("error: " + text, err, xhr);
            }
        });
    }

    window.create_group = function () {
        event.preventDefault();

        const btn = $(this);
        const commentBox = $("[name='direct-box']").val();
        const commentBtnText = $("#comment-btn-text");

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
                    commentBtnText.html('Send');
                    btn.prop("disabled", false);

                    var url = new URL(window.location.href);
                    url.searchParams.set("m", response.groupid);
                    window.location.href = url;
                } else {
                    commentBtnText.html('Send');
                    btn.prop("disabled", false);
                    alert(response.error);
                }
            },
            error: (jqXHR, textStatus, errorThrown) => {
                commentBtnText.html('Send');
                btn.prop("disabled", false);
                console.error("error:", textStatus, errorThrown, jqXHR);
                const response = JSON.parse(jqXHR.responseText);
                alert(resonse.error);
            }
        });
    }

    var url = new URL(window.location.href);
    var message = url.searchParams.get("m");

    if (message) {
        load_message(message);
    }
});