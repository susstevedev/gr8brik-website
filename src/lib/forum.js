(function () {
    $(document).ready(function () {
		$(document).ready(function() {
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
		});
	});
})();