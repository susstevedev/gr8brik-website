(function () {
    $(document).ready(function () {
			$(document).on('click', '.edit-btn', function() {
				let btn = $(this);
				let commentId = btn.data('id');
				let container = $(`#comment-${commentId}`);

				container.find('.edit').toggleClass('w3-hide');
				container.find('.comment-text').toggleClass('w3-hide');
			});

			$(document).on('click', '.save-btn', function(event) {
				event.preventDefault();
				let container = $(this).closest('.reply');
				let commentId = container.find('.edit-btn').data('id');
				let newText = container.find('.edit-textarea').val().trim();

				if(newText === "") {
					alert("Comment cannot be empty");
					return;
				}

				$.ajax({
					url: '',
					type: 'POST',
					data: {
						comment_edit: true,
						commentid: commentId,
						content: newText
					},
					dataType: 'json',
					success: function(response) {
						if(response.success) {
							container.find('.comment-text').html(newText).toggleClass('w3-hide');
							container.find('.edit-textarea').val(newText);
							container.find('.edit').toggleClass('w3-hide');
						} else {
							alert(response.message || "Error updating message.");
						}
					}
				});
			});

			$(document).on('click', '.cancel-btn', function(event) {
				event.preventDefault();
				let container = $(this).closest('.reply');

				container.find('.comment-text').toggleClass('w3-hide');
				container.find('.edit').toggleClass('w3-hide');
			});
			
			$(document).on('click', '.delete-btn', function() {
				let btn = $(this);
				let commentId = btn.data('id');
				let container = $(`#comment-${commentId}`);

				container.find('.delete').toggleClass('w3-hide');
			});
			
			$(document).on('click', '.confirm-delete-btn', function(event) {
				event.preventDefault();
				let container = $(this).closest('.reply');
				let commentId = container.find('.delete-btn').data('id');

				$.ajax({
					url: '',
					type: 'POST',
					data: {
						comment_delete: true,
						commentid: commentId,
					},
					dataType: 'json',
					success: function(response) {
						if(response.success) {
							container.fadeOut();
						} else {
							alert(response.message || "Error updating message.");
						}
					}
				});
			});

			$(document).on('click', '.cancel-delete-btn', function(event) {
				event.preventDefault();
				let container = $(this).closest('.reply');

				container.find('.delete').toggleClass('w3-hide');
			});

			$(document).on('click', '#post-reply', function(event) {
				event.preventDefault();
				let newText = $('textarea[name="commentbox"]').val().trim();

				if(newText === "") {
					alert("Reply cannot be empty");
					return;
				}

				$.ajax({
					url: '',
					type: 'POST',
					data: {
						comment: true,
						commentbox: newText
					},
					dataType: 'json',
					success: function(response) {
						if(response.success && response.id) {
							window.location.hash = 'comment-' + response.id;
							window.location.reload();
						} else {
							alert(response.message || "Error updating message.");
						}
					}
				});
			});

			$(document).on("click", "#attach-image", function (event) {
				event.preventDefault();
				$("#attach-upload #imagefile").click();
			});

			$(document).on("change", "#attach-upload #imagefile", function (event) {
				event.preventDefault();

				var btn = $(this);
				var btntext = $('#attach-image #attach-image-btn-text');
				var prevbtntext = btntext.html();

				var comment_box = $("#commentboxcontainer [name='commentbox']");

				var file_data = $('#attach-upload #imagefile').prop('files')[0];
				var form_data = new FormData();

				form_data.append('imagefile', file_data);
				form_data.append('upload', 'true');

				btntext.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
				btn.prop("disabled", true);

				$.ajax({
					url: "../ajax/image",
					dataType: 'json',
					contentType: false,
					processData: false,
					data: form_data,
					type: 'POST',
					success: function(res) {
						if (res && res.success && res.image && res.insert) {
							comment_box.val(comment_box.val() + ($.trim(comment_box.val()).length === 0 ? "" : "\n") + res.insert);

							alert(res.message);
						} else if (res && res.message && !res.success) {
							alert(res.message);
						} else {
							alert('An error occured while uploading the image');
						}

						btntext.html(prevbtntext);
						btn.prop("disabled", false);
					},
					error: function(xhr, text, error) {
						btntext.html(prevbtntext);
						btn.prop("disabled", false);

						alert("An error occured while uploading the image");
					}
				});
			});

			$(document).on("click", ".gr8-comment-attachment", function (event) {
				event.preventDefault();

				var item = $(this);
				var id = item.attr('data-id');
				var active = item.attr('data-active') ? true : false;

				if(!id) {
					return;
				}

				if(active) {
					$('.image-details').fadeOut('fast', function() { $('.image-details').remove() });
					item.attr('data-active', null);
					return;
				}

				$.ajax({
					url: "../ajax/image",
					dataType: 'json',
					contentType: 'application/json',
					data: JSON.stringify({ image: true, id: id }),
					type: 'POST',
					success: function(res) {
						if (res && res.success && res.image) {
							var delete_url = `<li><a href='${res.image.remove}'>Delete</a></li>`;
							$(`<div class="image-details w3-margin" style="display:none">
									<span class="w3-half w3-card-2 w3-white w3-round w3-padding-small w3-border w3-border-grey">
										<b>Image details</b>
										<ul>
											<li class="uploaded">Uploaded at ${res.image.timestamp}</li>
											<li class="author">Uploaded by <b>${res.image.user ? ('<a href="/@' + res.image.user + '"><i class="fa fa-at" aria-hidden="true"></i>' + res.image.user + "</a>") : 'A Deleted User'}</b></li>
											<li class="mime">MIME type: ${res.image.mime}</li>
											<li class="url">Link: ${res.image.url}</li>
											<li class="bbcode">BBcode embed: ${res.image.bbcode}</li>
										${res.image.remove ? delete_url + '</ul>' : '</ul>'}
									</span>
								</div>`).insertAfter(item).fadeIn('fast');

							item.attr('data-active', true);
						} else if (res && res.message && !res.success) {
							alert(res.message);
						} else {
							alert('An error occured while viewing the image details');
						}
					},
					error: function(xhr, text, error) {
						console.log(xhr, text, error);
						alert("An error occured while viewing the image details");
					}
				});
			});
	});
})();